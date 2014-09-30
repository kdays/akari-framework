<?php
if (php_sapi_name() != 'cli') {
	exit("only php cli");
}

if(count($argv) < 2){
	exit("usage: php init.php [app_namespace]\n");
}

define("DS", DIRECTORY_SEPARATOR);

$appName = $argv[1];
if (is_numeric($appName)) {
	exit("error: AppName 不能为纯数字\n");
}

if (strtolower($appName) == "akari") {
	exit("error: AppName 不能为Akari，与框架相同");
}

// 如果没有core文件 然后目录下发现akari.php 说明需要将这些文件都移动过去
if (file_exists("akari.php") && !is_dir("core/")) {
	echoTo("framework to core..");

	toDir(__DIR__);
	$fkFw = [
		"config", 
		"external/PHPMailer",
		"external", 
		"model", 
		"system/console",
		"system/data",
		"system/db",
		"system/exception",
		"system/http",
		"system/log",
		"system/module",
		"system/security/Cipher",
		"system/security",
		"system", 
		"template", 
		"utility"
	];
	foreach ($fkFw as $dir) {
		echoTo("delete empty fkdir: $dir");
		rmdir($dir);
	}

	echoTo("fk to core ..done\n");
}

echoTo("make project ..\n");

$indexFileData = <<<'INDEX'
<?php
namespace __APPNAME__;
error_reporting(E_ERROR | E_PARSE);

include("core/akari.php");

use Akari\akari;

if(!CLI_MODE){
	akari::getInstance()->initApp(__DIR__, __NAMESPACE__)->run();
}else{
	if(count($argv) < 2){
	    echo("Akari Framework (".AKARI_BUILD.") \n");
	    echo("CLI模式时，执行至少需要指定task的名称.  (no task command)\n");
	    echo("\nusage: php -f index.php taskURI parameter\n\n");
	    exit();
	}
	akari::getInstance()->initApp(__DIR__, __NAMESPACE__)->run($argv[1], FALSE);
}
INDEX;

file_put_contents("index.php", str_replace('__APPNAME__', $appName, $indexFileData));
echoTo("index.php..success");

echoTo("\nmkdir..");
$dirList = [
	"app",
	"app/config",
	"app/action",
	"app/exception",
	"app/language",
	"app/lib",
	"app/model",
	"app/service",
	"app/task",
	"app/template",
	"app/template/layout",
	"app/template/partial",
	"app/template/view",
	"app/trigger"
];

foreach ($dirList as $value) {
	if (is_dir($value)) {
		echoTo("dir: $value ..exists");
	} else {
		mkdir($value, 0755, TRUE);
		echoTo("dir: $value ..done");
	}
}

// 创建Config文件
$confData = <<<'CONF'
<?php
namespace __APPNAME__\config;

use Akari\config\BaseConfig;
use Akari\Context;
use Akari\utility\DataHelper;
use \PDO;

Class Config extends BaseConfig{
	public $appName = "DEMO";
	public $appBaseURL = "http://127.0.0.1/";
    public $triggerRule = Array(
        "pre" => Array(
        )
    );

    public $URLRewrite = [];
	public $uploadDir = "/attachment";

	public $database = Array(
		'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=',
        'username' => '',
        'password' => '',
        'options' => Array(
        	PDO::MYSQL_ATTR_INIT_COMMAND => 'set names "utf8"'
        )
	);
}
CONF;

if (!file_exists("app/config/Config.php")) {
	file_put_contents("app/config/Config.php", str_replace('__APPNAME__', $appName, $confData));
	echoTo("config.php ..done");
}


// 默认触发器处理
$defaultTrigger = [
	"ApplicationStart" => "本部分在触发器之前执行，而AfterInit则是在所有触发器结束时执行",
	"ApplicationEnd" => "在Action调用结束时，会调用本触发器
可以用来处理如繁体化等操作

注意 异常处理时不会执行这个文件，要进行的额外操作请用Event类
监听coreException.exception和coreException.fatal处理

如果纯粹是错误信息，应该使用language方法来处理，而非这里判断",
	"AfterInit" => "在调用action前，框架会调用本文件执行
可以进行一些应用全局的处理操作"
];

foreach ($defaultTrigger as $value => $descrip) {
	$path = "app/trigger/$value.php";

	$file = "<?php\n";
	$file .= "!defined('AKARI_PATH') && exit;\n";
	$file .= "\n";
	$descrip = "/**\n".$descrip."\n*/\n";

	if (!file_exists($path)) {
		file_put_contents($path, $file.$descrip);
		echoTo("defaultTrigger: $value ..done");
	}
}


echoTo("\ninit success, please delete this file");

function echoTo($msg) {
	echo $msg."\n";
}

function toDir($dirName) {
	$list = scandir($dirName);

	foreach ($list as $value) {
		if ($value == '.' || $value == '..' || $value == 'init.php' || $value == 'index.php') {
			continue;
		}

		$source = $dirName.DS.$value;
		$target = __DIR__.DS."core".DS.str_replace(__DIR__, '', $dirName).DS.$value;

		if (is_dir($source)) {
			toDir($dirName.DS.$value);
		} else {
			echoTo("fk move: $source");

			if (!is_dir(dirname($target))) {
				mkdir(dirname($target), 0755, TRUE);
			}
			rename($source, $target);
		}
	}
}