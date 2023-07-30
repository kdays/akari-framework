<?php
use Akari\Core;

if (!class_exists("Akari\Core")) {
    include(__DIR__ . '/../Loader.php');
    if (!Core::inConsole()) {
        die('CLI Application must on CLI');
    }

    $output = new \Akari\system\console\Output();
    $input = new \Akari\system\console\Input();

    $output->write("<error>目前似乎项目没有初始化，或者你在错误的地方进行了执行</error>");

    // 这里可以写个初始向导 把东西都初始化出来
    return false;
}

if (!Core::inConsole()) {
    die('CLI Application must on CLI');
}

class CommandSetup {

    public $commands = [];

    public function register(string $name, string $pathCls) {
        $this->commands[$name] = $pathCls;
    }

    public function app() {
        if (empty(Core::$appDir)) return ;

        foreach (scandir(Core::$appDir . DIRECTORY_SEPARATOR . 'task') as $taskName) {
            if ($taskName == '.' || $taskName == '..') continue;

            if (strpos($taskName, 'Task.php') !== false) {
                $taskName = str_replace('.php', '', $taskName);
                $task = implode(NAMESPACE_SEPARATOR, [Core::$appNs, 'task', $taskName]);

                $taskCls = new $task();
                if (!empty($taskCls::$command)) {
                    $this->commands[$taskCls::$command] = $taskCls::class;
                }
            }
        }
    }

    public function getParams($vars) {
        $params = [];
        foreach ($vars as $key => $value) {
            if ($key < 2) continue;
            $value = trim($value);

            if (empty($value)) continue;

            if (substr($value, 0, 2) == '--') {
                $values = explode("=", substr($value, 2));
                $commandName = array_shift($values);
                $params[$commandName] = implode("=", $values);
            }
        }

        return $params;
    }
}

$setup = new CommandSetup();
$setup->register("ide:model", \Akari\command\IDEModelCommand::class);
$setup->register("cache:forget", \Akari\command\CacheForgetCommand::class);

$setup->app();

// 检查是否在command中 如果在的话
$argCommand = $argv[1] ?? NULL;
if (empty($argCommand)) {
    $output = new \Akari\system\console\Output();
    $output->write("Akari Framework Console Commands");

    foreach ($setup->commands as $command => $taskCls) {
        $output->write("<info>" . $command . "</info> \t" . $taskCls::$description);
    }
} else {
    if (isset($setup->commands[$argCommand])) {
        $params = $setup->getParams($argv);

        $taskCls = new $setup->commands[$argCommand]();
        $taskCls->handle($params);
    }  else {
        $core = Core::initApp(__DIR__, KOKORO_NS);
        $core->run($argv[1]);
    }
}