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


$cmdAction = $argv[1];

if (empty($cmdAction)) {
    $output->write("<info>Akari Framework 快速初始化工具</info>");
    
    $output->write("php init.php init 初始化工程");
    $output->write("php init.php init-model 初始化数据库模型");
    
    die;
}

if ($cmdAction == 'init-model') {
    
    // 输入目标目录
    $output->write("<info>即将开始Akari Framework项目的模型初始化工作</info>");
    $output->write("<question>请输入生成模型的目标目录: (默认: ../app/model/db)</question>");
    $modelDir = $input->getInput();
    
    if (empty($modelDir))   $modelDir = "../app/model/db/";
    $modelDir = realpath($modelDir);
    
    if (!$modelDir) {
        $output->write("<error>目标目录不存在!</error>");
        die;
    }
    
    // 按照数据库访问
    $output->write("请输入MYSQL访问目录");
    $dbHost = $input->getInput();
    
    
    
}

if ($cmdAction == 'init') {
    
    $