# limx-pdo
> 对php扩展PDO进一步进行封装

## 安装
> 推荐使用composer安装

~~~
composer require limingxinleo/limx-pdo
~~~

## 使用
* 首先引入自动加载类
~~~
require_once "vendor/autoload.php";
~~~

* 使用
~~~
$M = \limx\tools\MyPDO::getInstance();
$sql='select * from test;';
var_dump($M->query($sql));
~~~
