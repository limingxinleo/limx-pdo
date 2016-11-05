<?php
// +----------------------------------------------------------------------
// | Demo [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.lmx0536.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: limx <715557344@qq.com> <http://www.lmx0536.cn>
// +----------------------------------------------------------------------
// | Date: 2016/7/28 Time: 16:01
// +----------------------------------------------------------------------
namespace limx\tools;

use PDO;

class MyPDO
{
    protected static $_instance = [];
    protected $dbName = '';
    protected $dsn;
    protected $dbh;
    // PDO连接参数
    protected $params = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,  // 返回的数据是否转化为string
    ];

    /**
     * 构造
     *
     * @return MyPDO
     */
    protected function __construct($dbType, $dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset, $dbParams)
    {
        try {
            switch ($dbType) {
                case 'sqlite':
                    $this->sqlite($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset, $dbParams);
                    break;
                case 'mysql':
                default:
                    $this->mysql($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset, $dbParams);
            }

        } catch (PDOException $e) {
            $this->outputError($e->getMessage());
        }
    }

    /**
     * [sqlite desc]
     * @desc    sqlite 初始化
     * @author limx
     * @param $dbHost
     * @param $dbUser
     * @param $dbPasswd
     * @param $dbName
     * @param $dbCharset
     * @param $dbParams
     */
    private function sqlite($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset, $dbParams)
    {
        $root = dirname($dbName);
        if (!is_dir($root)) {
            mkdir($root, 0777, true);
        }
        $this->dsn = 'sqlite:' . $dbName;
        $this->dbh = new PDO($this->dsn);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION);
    }

    /**
     * [mysql desc]
     * @desc mysql 初始化
     * @author limx
     * @param $dbType
     * @param $dbHost
     * @param $dbUser
     * @param $dbPasswd
     * @param $dbName
     * @param $dbCharset
     * @param $dbParams
     */
    private function mysql($dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset, $dbParams)
    {
        $this->dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName;
        $params = [];
        if ($dbParams) {
            $params = $dbParams + $this->params;
        } else {
            $params = $this->params;
        }
        $this->dbh = new PDO($this->dsn, $dbUser, $dbPasswd, $params);
        $this->dbh->exec('SET character_set_connection=' . $dbCharset . ', character_set_results=' . $dbCharset . ', character_set_client=binary');
    }

    public static function retInstances()
    {
        return self::$_instance;
    }

    public static function retInstanceKey($config = [])
    {
        if (file_exists(__DIR__ . '/config.php')) {
            $default = include('config.php');
            $config = $config + $default;
        }
        return md5(json_encode($config));
    }

    /**
     * 防止克隆
     *
     */
    private function __clone()
    {
    }

    /**
     * Singleton instance
     *
     * @return Object
     */
    public static function getInstance($config = [])
    {
        if (file_exists(__DIR__ . '/config.php')) {
            $default = include('config.php');
            $config = $config + $default;
        }

        $dbType = $config['type'];
        $dbHost = $config['host'];
        $dbUser = $config['user'];
        $dbPasswd = $config['pwd'];
        $dbName = $config['dbname'];
        $dbCharset = $config['charset'];
        $dbParams = $config['params'];

        $key = md5(json_encode($config));

        if (empty(self::$_instance[$key])) {
            self::$_instance[$key] = new self($dbType, $dbHost, $dbUser, $dbPasswd, $dbName, $dbCharset, $dbParams);
        }
        return self::$_instance[$key];
    }

    /**
     * Query 查询
     *
     * @param String $strSql SQL语句
     * @param String $queryMode 查询方式(All or Row)
     * @param Boolean $debug
     * @return Array
     */
    public function query($strSql, $bind = [], $queryMode = 'All', $debug = false)
    {
        if ($debug === true) $this->debug($strSql);

        $recordset = $this->dbh->prepare($strSql);
        $status = $recordset->execute($bind);
        //        $recordset = $this->dbh->query($strSql);
        if ($status) {
            $recordset->setFetchMode(PDO::FETCH_ASSOC);
            if ($queryMode == 'All') {
                $result = $recordset->fetchAll();
            } elseif ($queryMode == 'Row') {
                $result = $recordset->fetch();
            }
        } else {
            $result = null;
        }
        return $result;

    }

    /**
     * Update 更新
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param String $where 条件
     * @param Boolean $debug
     * @return Int
     */
    public function update($table, $arrayDataValue, $where = '', $debug = false)
    {
        $this->checkFields($table, $arrayDataValue);
        if ($where) {
            $strSql = '';
            foreach ($arrayDataValue as $key => $value) {
                $strSql .= ", `$key`='$value'";
            }
            $strSql = substr($strSql, 1);
            $strSql = "UPDATE `$table` SET $strSql WHERE $where";
        } else {
            $strSql = "REPLACE INTO `$table` (`" . implode('`,`', array_keys($arrayDataValue)) . "`) VALUES ('" . implode("','", $arrayDataValue) . "')";
        }
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * Insert 插入
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param Boolean $debug
     * @return Int
     */
    public function insert($table, $arrayDataValue, $debug = false)
    {
        $this->checkFields($table, $arrayDataValue);
        $strSql = "INSERT INTO `$table` (`" . implode('`,`', array_keys($arrayDataValue)) . "`) VALUES ('" . implode("','", $arrayDataValue) . "')";
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * Replace 覆盖方式插入
     *
     * @param String $table 表名
     * @param Array $arrayDataValue 字段与值
     * @param Boolean $debug
     * @return Int
     */
    public function replace($table, $arrayDataValue, $debug = false)
    {
        $this->checkFields($table, $arrayDataValue);
        $strSql = "REPLACE INTO `$table`(`" . implode('`,`', array_keys($arrayDataValue)) . "`) VALUES ('" . implode("','", $arrayDataValue) . "')";
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * Delete 删除
     *
     * @param String $table 表名
     * @param String $where 条件
     * @param Boolean $debug
     * @return Int
     */
    public function delete($table, $where = '', $debug = false)
    {
        if ($where == '') {
            $this->outputError("'WHERE' is Null");
        } else {
            $strSql = "DELETE FROM `$table` WHERE $where";
            if ($debug === true) $this->debug($strSql);
            $result = $this->dbh->exec($strSql);
            $this->getPDOError();
            return $result;
        }
    }

    /**
     * execSql 执行SQL语句
     *
     * @param String $strSql
     * @param Boolean $debug
     * @return Int
     */
    public function execSql($strSql, $debug = false)
    {
        if ($debug === true) $this->debug($strSql);
        $result = $this->dbh->exec($strSql);
        $this->getPDOError();
        return $result;
    }

    /**
     * [execute]
     * @desc 执行sql
     * @author limx
     * @param $strSql sql语句
     * @param array $bind 参数
     * @param bool $debug 是否DEBUG
     */
    public function execute($strSql, $bind = [], $debug = false)
    {
        if ($debug === true) $this->debug($strSql);
        $stmt = $this->dbh->prepare($strSql);
        $this->getPDOError();
        return $stmt->execute($bind);
    }

    /**
     * 获取字段最大值
     *
     * @param string $table 表名
     * @param string $field_name 字段名
     * @param string $where 条件
     */
    public function getMaxValue($table, $field_name, $where = '', $debug = false)
    {
        $strSql = "SELECT MAX(" . $field_name . ") AS MAX_VALUE FROM $table";
        if ($where != '') $strSql .= " WHERE $where";
        if ($debug === true) $this->debug($strSql);
        $arrTemp = $this->query($strSql, [], 'Row');
        $maxValue = $arrTemp["MAX_VALUE"];
        if ($maxValue == "" || $maxValue == null) {
            $maxValue = 0;
        }
        return $maxValue;
    }

    /**
     * 获取指定列的数量
     *
     * @param string $table
     * @param string $field_name
     * @param string $where
     * @param bool $debug
     * @return int
     */
    public function getCount($table, $field_name, $where = '', $debug = false)
    {
        $strSql = "SELECT COUNT($field_name) AS NUM FROM $table";
        if ($where != '') $strSql .= " WHERE $where";
        if ($debug === true) $this->debug($strSql);
        $arrTemp = $this->query($strSql, [], 'Row');
        return $arrTemp['NUM'];
    }

    /**
     * 获取表引擎
     *
     * @param String $dbName 库名
     * @param String $tableName 表名
     * @param Boolean $debug
     * @return String
     */
    public function getTableEngine($dbName, $tableName)
    {
        $strSql = "SHOW TABLE STATUS FROM $dbName WHERE Name='" . $tableName . "'";
        $arrayTableInfo = $this->query($strSql);
        $this->getPDOError();
        return $arrayTableInfo[0]['Engine'];
    }

    /**
     * [getTable 是否存在此表]
     * @author limx
     * @param $tableName 表名
     * @return Array
     */
    public function getTable($tableName)
    {
        $strSql = "SHOW TABLES LIKE '" . $tableName . "'";
        $arrayTableInfo = $this->query($strSql);
        $this->getPDOError();
        return $arrayTableInfo;
    }

    /**
     * [addTable 添加新表]
     * @author limx
     * @param $tableName 表名
     * @param $sql 添加新表用sql
     */
    public function addTable($tableName, $sql)
    {
        $table = $this->getTable($tableName);
        if (empty($table)) {
            return $this->execSql($sql);
        }
        return false;
    }

    /**
     * [addFieldbyTable 增加表的字段 ]
     * @author limx
     * @param $field 字段名
     * @param $table 表名
     * @param string $type 字段类型
     * @param int $len 长度
     * @param int $default 默认值
     * @param string $comment 注释
     * @return bool|Int
     */
    public function addFieldbyTable($field, $table, $type = 'int', $len = 11, $default = 0, $comment = "")
    {
        if (!$this->checkFieldByTable($field, $table)) {
            $sql = " ALTER TABLE $table ADD COLUMN $field ";
            switch ($type) {
                case 'int':
                    $sql .= " INT($len) ";
                    $sql .= " NOT NULL DEFAULT " . $default;
                    break;
                case 'varchar':
                    $sql .= " VARCHAR($len) ";
                    $sql .= " NOT NULL DEFAULT '" . $default . "'";
                    break;
                case 'datetime':
                    $sql .= " DATETIME ";
                    $sql .= " NOT NULL DEFAULT '1900-01-01 00:00:00'";
                    break;
                default:
                    $sql .= " VARCHAR($len) ";
                    $sql .= " NOT NULL DEFAULT '" . $default . "'";
                    break;
            }

            if (!empty($comment)) {
                $sql .= " COMMENT '" . $comment . "' ";
            }

            return $this->execSql($sql);
        }
        return false;
    }

    /**
     * beginTransaction 事务开始
     */
    public function beginTransaction()
    {
        $this->dbh->beginTransaction();
    }

    /**
     * [trans desc]
     * @desc 开启事务
     * @author limx
     */
    public function trans()
    {
        $this->dbh->beginTransaction();
    }

    /**
     * rollback 事务回滚
     */
    public function rollback()
    {
        $this->dbh->rollback();
    }

    /**
     * rollback 事务提交
     */
    public function commit()
    {
        $this->dbh->commit();
    }

    /**
     * transaction 通过事务处理多条SQL语句
     * 调用前需通过getTableEngine判断表引擎是否支持事务
     *
     * @param array $arraySql
     * @return Boolean
     */
    public function execTransaction($arraySql)
    {
        $retval = 1;
        $this->beginTransaction();
        foreach ($arraySql as $strSql) {
            if ($this->execSql($strSql) == 0) $retval = 0;
        }
        if ($retval == 0) {
            $this->rollback();
            return false;
        } else {
            $this->commit();
            return true;
        }
    }

    /**
     * checkFields 检查指定字段是否在指定数据表中存在
     *
     * @param String $table
     * @param array $arrayField
     */
    private function checkFields($table, $arrayFields)
    {
        $fields = $this->getFields($table);
        foreach ($arrayFields as $key => $value) {
            if (!in_array($key, $fields)) {
                $this->outputError("Unknown column `$key` in field list.");
            }
        }
    }

    /**
     * [checkFieldByTable 查看某表中是否存在某字段]
     * @author limx
     * @param $field 字段名
     * @param $table 表名
     */
    private function checkFieldByTable($field, $table)
    {
        $fields = $this->getFields($table);
        if (in_array($field, $fields)) {
            return true;
        }
        return false;
    }

    /**
     * getFields 获取指定数据表中的全部字段名
     *
     * @param String $table 表名
     * @return array
     */
    public function getFields($table)
    {
        $fields = array();
        $recordset = $this->dbh->query("SHOW COLUMNS FROM $table");
        $this->getPDOError();
        $recordset->setFetchMode(PDO::FETCH_ASSOC);
        $result = $recordset->fetchAll();
        foreach ($result as $rows) {
            $fields[] = $rows['Field'];
        }
        return $fields;
    }

    /**
     * getPDOError 捕获PDO错误信息
     */
    private function getPDOError()
    {
        if ($this->dbh->errorCode() != '00000') {
            $arrayError = $this->dbh->errorInfo();
            $this->outputError($arrayError[2]);
        }
    }

    /**
     * debug
     *
     * @param mixed $debuginfo
     */
    private function debug($debuginfo)
    {
        var_dump($debuginfo);
        exit();
    }

    /**
     * 输出错误信息
     *
     * @param String $strErrMsg
     */
    private function outputError($strErrMsg)
    {
        throw new Exception('MySQL Error: ' . $strErrMsg);
    }

    /**
     * destruct 关闭数据库连接
     */
    public function destruct()
    {
        $this->dbh = null;
    }
}

?>