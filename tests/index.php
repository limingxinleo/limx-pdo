<?php
// +----------------------------------------------------------------------
// | Demo [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.lmx0536.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: limx <715557344@qq.com> <http://www.lmx0536.cn>
// +----------------------------------------------------------------------
// | Date: 2016/8/12 Time: 10:46
// +----------------------------------------------------------------------

namespace limx\tools;

require_once '../src/MyPDO.php';
require_once 'helper.php';

$config['pwd'] = '910123';
$M = MyPDO::getInstance($config);
$sql = 'select * from test where id = ? and username = ?;';
dump($M->query($sql, [3, 3]));
dump($M->getMaxValue('test', 'id'));
dump($M->getCount('test', 'id'));
dump($M->getTableEngine('my_db', 'test'));
dump($M->getTable('test'));
$time = time();
dump($M->execute('insert into test (username) values(?)', [$time]));

$config['dbname'] = 'db_test';
$M2 = MyPDO::getInstance($config);
dump($M2->getTableEngine('db_test', 'tb_test'));

dump(MyPDO::retInstances());
