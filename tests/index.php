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

$config['dbname'] = __DIR__ . '/ss/sqlite.db';
$config['type'] = 'sqlite';
$M3 = MyPDO::getInstance($config);
$sql = "CREATE TABLE IF NOT EXISTS messages (
                    id INTEGER PRIMARY KEY,
                    title TEXT,
                    message TEXT,
                    time INTEGER)";
$M3->execute($sql);
$insert = "INSERT INTO messages (title, message, time) VALUES (?, ?, ?)";
$M3->execute($insert, ['t1', 'm1', time()]);
$M3->execute($insert, ['t2', 'm2', time()]);
$M3->execute($insert, ['t3', 'm3', time()]);
dump($M3->query("SELECT * FROM messages LIMIT 0,5;"));

dump(MyPDO::retInstances());
dump(MyPDO::retInstanceKey($config));
