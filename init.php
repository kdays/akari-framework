<?php
/**
 * Akari Framework init 
 */
error_reporting(E_ERROR | E_PARSE | E_USER_ERROR);

require("./akari.php");

use Akari\utility\FileHelper;

// 启动基础Builder
$input = new \Akari\system\console\Input();
$output = new \Akari\system\console\Output();

$output->write("<info>即将开始Akari Framework项目的初始化工作</info>");
$output->write("<question>请输入你要创建的应用名字:</question>");
$appName = $input->getInput();
if (empty($appName)) {
    $output->write("你输入的名字不合法");
}

$output->write("<question>请输入你要创建的应用的Namespace的名字(如Rin, nico):</question>");

$ns = $input->getInput();
if (empty($ns) || $ns == 'Akari') {
    $output->write("你输入的命名空间不合法");
}

//
$output->write("<question>请输入你要创建的项目应用文件夹路径 (默认: ../app/)</question>");
$appPath = $input->getInput();

if (empty($appPath)) {
    $appPath = "../app/";
}

if (!is_dir($appPath)) {
    mkdir($appPath);
}
$appPath = realpath($appPath);

$output->write("<info>你选择的目录是:" . $appPath . "\n确定的话请输入Y继续</info>");
$next = $input->getInput();

if ($next != 'Y') {
    die("用户终止了");
}

$appDirNames = [
    'action',
    'config',
    'dao',
    'exception',
    'language',
    'lib',
    'model',
    'model/db',
    'model/req',
    'service',
    'sql',
    'task',
    'template',
    'template/layout',
    'template/widget',
    'template/block',
    'template/view',
    'trigger',
    'widget'
];

foreach ($appDirNames as $name) {
    $fDir = $appPath. DIRECTORY_SEPARATOR. $name;
    
    if (!is_dir($fDir)) {
        $output->write("<success>创建文件夹: $name</success>");
        mkdir($appPath. DIRECTORY_SEPARATOR. $name);
    } 
}

// 创建基础的配置
$baseConfig = <<<'EOT'
<?php

namespace %ns\config;

use Akari\config\BaseConfig;

class Config extends BaseConfig {
    
    public $appName = "%appName";
        
    %database
    
}
EOT;

$baseConfig = str_replace('%ns', $ns, $baseConfig);
$baseConfig = str_replace('%appName', $appName, $baseConfig);

$output->write("<info>开始配置文件创建</info>");
$output->write("<question>应用是否要使用MYSQL数据库? (默认=Y / 否=N)</question>");

$allowUseDb = $input->getInput();
if ($allowUseDb != 'N') {
    
    $output->write("<question>MySQL host ip (默认: 127.0.0.1)</question>");
    $databaseHost = $input->getInput();
    if (empty($databaseHost))   $databaseHost = '127.0.0.1';

    $output->write("<question>MySQL 数据库名 (默认: app)</question>");
    $databaseName = $input->getInput();
    if (empty($databaseName))   $databaseName = 'app';

    $output->write("<question>MySQL 用户名 (默认: root)</question>");
    $databaseUser = $input->getInput();
    if (empty($databaseUser))   $databaseUser = 'root';

    $output->write("<question>MySQL 密码</question>");
    $databasePass = $input->getInput();

    $output->write("<question>MySQL 数据库编码 (默认 utf8mb4)</question>");
    $databaseCharset = $input->getInput();
    if (empty($databaseCharset))   $databaseUser = 'utf8mb4';
    
    $databaseCfg = <<<'EOT'
public $database = [
        'dsn' => 'mysql:host=%host;port=3306;dbname=%name',
        'username' => "%user",
        'password' => "%pass",
        'options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names "%charset"'
        ]
    ];
EOT;
    
    $databaseCfg = str_replace([
        "%name", "%user", "%pass", "%charset", "%host"
    ], [
        $databaseName, $databaseUser, $databasePass, $databaseCharset, $databaseHost
    ], $databaseCfg);

    $baseConfig = str_replace('%database', $databaseCfg, $baseConfig);
} else {
    $baseConfig = str_replace('%database', '', $baseConfig);
}

FileHelper::write($appPath . DIRECTORY_SEPARATOR. "/config/Config.php", $baseConfig);

$output->write("<success>配置文件已写入/config/Config.php</success>");


// 创建默认基础的一些方法
$baseActionFile = <<<'EOT'
<?php
namespace %ns\action;

use Akari\action\BaseAction;

abstract class BaseFrontAction extends BaseAction {

}
EOT;

$baseActionFile = str_replace('%ns', $ns, $baseActionFile);
FileHelper::write($appPath . DIRECTORY_SEPARATOR. "/action/BaseFrontAction.php", $baseActionFile);

$output->write("<success>已创建默认文件: /action/BaseFrontAction.php</success>");


$baseDAO = <<<'EOT'
<?php
namespace %ns\dao;

use Akari\system\conn\BaseSQLMap;
use Akari\system\conn\DBConnection;
use Akari\system\conn\DBConnFactory;
use Akari\system\conn\SQLMapBuilder;
use Akari\system\Plugin;

abstract class BaseDAO extends Plugin {

    /** @var  SQLMapBuilder $builder */
    protected $builder;
    
    /** @var  DBConnection $connection */
    protected $connection;
    protected static $m;

    protected function initConnection($cfg = 'default') {
        if ($this->connection) {
            return $this->connection;
        }

        $conn = DBConnFactory::get($cfg);
        return $this->connection = $conn;
    }

    public function initBuilder(BaseSQLMap $SQLMap) {
        if (!$this->builder) {
            $this->builder = new SQLMapBuilder($SQLMap, $this->connection);
        }

        return $this->builder;
    }

    /**
     * 单例公共调用，不应在action中调用本方法
     * @return static
     */
    public static function getInstance() {
        $class = get_called_class();
        if (!isset(self::$m[$class])) {
            self::$m[ $class ] = new $class;
        }

        return self::$m[ $class ];
    }

}
EOT;

$baseDAO = str_replace('%ns', $ns, $baseDAO);
FileHelper::write($appPath . DIRECTORY_SEPARATOR. "/dao/BaseDAO.php", $baseDAO);

$output->write("<success>已创建默认文件: /dao/BaseDAO.php</success>");


$baseDbModel = <<<'EOT'
<?php
namespace %ns\model\db;

use Akari\model\DatabaseModel;

abstract class BaseModel extends DatabaseModel{
    
}
EOT;
$baseDbModel = str_replace('%ns', $ns, $baseDbModel);
FileHelper::write($appPath . DIRECTORY_SEPARATOR. "/model/db/BaseModel.php", $baseDbModel);


$baseService = <<<'EOT'
<?php
namespace %ns\service;

use Akari\system\ioc\DIHelper;

abstract class BaseService {

    use DIHelper;
    
}
EOT;
$baseService = str_replace('%ns', $ns, $baseService);
FileHelper::write($appPath . DIRECTORY_SEPARATOR. "/service/BaseService.php", $baseService);

$output->write("<success>已创建默认文件: /service/BaseService.php</success>");



/// 创建默认演示文件
$defaultControlFile = <<<'EOT'
<?php
namespace %ns\action;

class IndexAction extends BaseFrontAction {

    public function indexAction() {
        return self::_genTEXTResult('Hello, Akari Framework');
    }

}
EOT;
$defaultControlFile = str_replace('%ns', $ns, $defaultControlFile);
FileHelper::write($appPath . DIRECTORY_SEPARATOR. "/action/IndexAction.php", $defaultControlFile);

$output->write("<success>已创建默认文件: /action/IndexAction.php</success>");


// 执行完成
$output->write("<info>你可以随时删除init.php</info>");
$output->write("<info>Enjoy :)</info>");
