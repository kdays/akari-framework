<?php

namespace Akari\command;

use Akari\Core;
use Akari\system\container\BaseTask;
use Akari\system\db\DBConnection;

class IDEModelCommand extends BaseTask {

    public static $command = 'ide:model';
    public static $description = "根据数据库配置，更新模型的注入注释\n--db=数据库设置名(默认default) --table=表名(不指定则为确定模型生成)";

    public function handle($params) {
        $db = DBConnection::init($params['db'] ?? 'default');
        $table = $params['table'] ?? NULL;

        if (!empty($table)) {
            $modelPath = $this->matchModel($table);
            if (empty($modelPath)) {
                $this->output->write("<error>没有找到模型设置</error>");
                return false;
            }

            $columnsInfos = $db->getConn()->select("SHOW FULL COLUMNS FROM " . $table);

            $docBlock = [];
            foreach ($columnsInfos as $columnsInfo) {
                $docBlock[] = " * @property $" . $columnsInfo->Field . " " . $columnsInfo->Comment;
            }
;
            $this->updateDocBlockComment($modelPath, $docBlock);
            $this->output->write("<success>" . $modelPath . " ... ok</success>");
        } else {
            $tables = $db->getConn()->select("SHOW TABLES");

            foreach ($tables as $table) {
                $column = "Tables_in_" . $db->getDbName();
                $name = $table->$column;

                $modelPath = $this->matchModel($name);
                if ($modelPath) {
                    $columnsInfos = $db->getConn()->select("SHOW FULL COLUMNS FROM " . $name);

                    $docBlock = [];
                    foreach ($columnsInfos as $columnsInfo) {
                        $docBlock[] = " * @property $" . $columnsInfo->Field . " " . $columnsInfo->Comment;
                    }

                    $this->updateDocBlockComment($modelPath, $docBlock);
                    $this->output->write("<success>" . $modelPath . " ... ok</success>");
                }
            }
        }
    }

    public function getDocComments($file) {
        // 匹配class定义，提取注释块
        preg_match_all('/\/\*\*(.*)\*\//sU', $file, $matches);

        // 对于每个匹配到的注释块，找到最近的class头部
        foreach ($matches[0] as $key => $value) {
            $pos = strrpos($file, $value) - 1; // 获取注释块前一个字符的位置
            $class_pos = strrpos(substr($file, 0, $pos), 'class '); // 获取最近class头部的位置

            if ($class_pos !== false) {
                // 获取class名称
                $class_name = substr($file, $class_pos + 6, $pos - $class_pos - 5);

                // 输出注释和class名称
                echo "Class: $class_name\nComment: $value\n\n";
            }
        }
    }

    public function updateDocBlockComment(string $modelPath, array $docBlock) {
        $modelContent = file_get_contents($modelPath);
        $begin = '-begin auto generated';
        $end = '-end auto generated';
        $classPos = stripos($modelContent, 'class');

        // 从class这边往后找
        $prefix = substr($modelContent, 0, $classPos);
        $content = $prefix;
        $after = substr($modelContent, $classPos);

        $blocks = [];
        if (strpos($content, '/**') !== false) {
            for ($i = 0; $i < 3; $i++) {
                if (strpos($content, '/*') !== false) {
                    $block = substr($content, strpos($content, '/*'), strpos($content, '*/') - 4);
                    $content = str_replace($block, '', $content);
                    $blocks[] = $block;
                } else {
                    break;
                }
            }
        }

        //var_dump($blocks);die;

        $isUpdate = false;
        if (count($blocks) > 0) {
            foreach ($blocks as $block) {
                // 这里找最近的介绍 看是否要并入
                if (stripos($block, $begin) !== false) {
                    $isUpdate = true;

                    // 如果有找到的话 这里将这部分换掉
                    $newDoc = substr($block, 0, strpos($block, $begin));
                    $newDoc .=  $begin . "\n";
                    $newDoc .= implode("\n", $docBlock);
                    $newDoc .= "\n * " . $end;
                    $newDoc .= substr($block, strpos($block, $end) + strlen($end));

                    $prefix = str_replace($block, $newDoc, $prefix);

                    break;
                }
            }
        }

        if (!$isUpdate) {
            $prefix .= "\n";
            $prefix .= "/**\n";
            $prefix .= " * \n";
            $prefix .= " * " . $begin . "\n";
            $prefix .= implode("\n", $docBlock);
            $prefix .= "\n * " . $end . "\n";
            $prefix .= "**/\n";
        }

        file_put_contents($modelPath, $prefix . $after);
    }

    //下划线命名到驼峰命名
    function toCamelCase($str) {
        $array = explode('_', $str);
        $result = $array[0];
        $len=count($array);
        if($len>1)  {
            for($i=1;$i<$len;$i++) {
                $result.= ucfirst($array[$i]);
            }
        }
        return $result;
    }

    protected function matchModel(string $modelName) {
        $modelDirs = [
            Core::$appDir . '/model/db/',
            Core::$appDir . '/model/'
        ];

        $modelName = $this->toCamelCase($modelName);

        foreach ($modelDirs as $modelDir) {
            // 先检查是否有 没有的话可能是s复数被转换了
            if (file_exists($modelDir . $modelName . '.php')) {
                return $modelDir . $modelName . '.php';
            }

            if (substr($modelName, -1) == 's') {
                $baseNames = [];
                if (substr($modelName, -3) == 'ies') {
                    // y过来的这里做一次处理
                    $baseNames[] = substr($modelName, 0, -3) . 'y';
                }
                $baseNames[] = substr($modelName, 0, -1);

                foreach ($baseNames as $baseName) {
                    if (file_exists($modelDir . $baseName . '.php')) {
                        return $modelDir . $baseName . '.php';
                    }
                }
            }
        }
    }

}