# limx-pdo
php pdo 工具
require_once "vendor/autoload.php";
$M = Limx\Tools\MyPDO::getInstance();
$sql='select * from test;';
var_dump($M->query($sql));
