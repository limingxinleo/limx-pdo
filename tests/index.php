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

use limx\func\Debug;

require_once '../src/MyPDO.php';
require_once __DIR__ . '/../../limx-func/src/Debug.php';
$config['pwd'] = '910123';
$M = MyPDO::getInstance($config);
$sql = 'select * from test where id = ?;';
Debug::dump($M->query($sql, [3]));
Debug::dump($M->getMaxValue('test', 'id'));
Debug::dump($M->getCount('test', 'id'));
Debug::dump($M->getTableEngine('my_db', 'test'));
Debug::dump($M->getTable('test'));


