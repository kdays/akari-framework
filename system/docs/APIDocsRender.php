<?php

namespace Akari\system\docs;

use Akari\Core;
use Akari\system\util\ArrayUtil;
use Akari\system\util\TextUtil;

class APIDocsRender {

    public static function render(array $data, $config) {
        $methods = [];
        $schemas = [];
        $parentMenu = [];

        foreach ($data['actions'] as $class) {
            foreach ($class['methods'] as $method) {
                $parentName = empty($method['data']['parent']) ? '未定' : $method['data']['parent'];
                if (!in_array($parentName, $parentMenu)) {
                    $parentMenu[] = $parentName;
                }

                unset($method['method']);
                foreach ($method['data'] as $key => $dataItem) {
                    if ($key == 'parent') $dataItem = $parentName;
                    $method[$key] = $dataItem;
                }
                unset($method['data']);

                $methods[] = $method;
            }
        }

        foreach ($data['schema'] as $schema) {
            $schemaData = [
                'id' => $schema['parent']['key'] ?? ArrayUtil::last(explode(NAMESPACE_SEPARATOR, $schema['class'])),
                'name' => $schema['parent']['name'],
            ];

            $props = [];
            foreach ($schema['props'] as $key => $dataItem) {
                $propValue = ['key' => $dataItem['prop']];

                foreach ($dataItem['data'] as $k => $v) {
                    $propValue[$k] = $v;
                }

                $props[] = $propValue;
            }

            $schemaData['data'] = $props;

            $schemas[] = $schemaData;
        }

        $dictMap = [];
        if ($config) {
            $dictMap = $config::getDict();
        }

        return [
            'methods' => $methods,
            'schemas' => $schemas,
            'dict' => $dictMap,
            'menu' => $parentMenu
        ];
    }

    public static function callFiles(string $dir, callable $fn) {
        $files = new \DirectoryIterator($dir);
        $result = [];

        foreach ($files as $file) {
            if ($file->isDot()) continue;
            if (TextUtil::getFileExtension($file->getFilename()) != 'php') {
                continue;
            }

            // 这里我们需要生成对应PHP下的路径和结果参数这些
            $parsed = $fn($file->getRealPath());
            if ($parsed) $result[] = $parsed;
        }

        return $result;
    }

    public static function parseFile(string $path) {
        $classPath = str_replace( APP_DIR, Core::$appNs . NAMESPACE_SEPARATOR , $path );
        $classPath = str_replace( DIRECTORY_SEPARATOR, NAMESPACE_SEPARATOR, $classPath );
        $classPath = trim($classPath, '.php');

        $ref = new \ReflectionClass($classPath);

        $parentData = [];
        foreach ($ref->getAttributes() as $attr) {
            $to = $attr->newInstance();

            if (method_exists($to, 'toJson')) {
                $parentData = $to->toJson($parentData);
            }
        }

        $methodResult = [];
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class != $classPath) continue; // 非基类的跳过

            $data = [];
            foreach ($ref->getMethod($method->name)->getAttributes() as $attr) {
                $to = $attr->newInstance();

                if (method_exists($to, 'toJson')) {
                    $data = $to->toJson($data, $parentData);
                }
            }

            if (empty($data)) continue;

            $methodResult[] = [
                'method' => $method->name,
                'data' => $data
            ];
        }

        $propResult = [];
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->class != $classPath) continue;

            $data = [];
            foreach ($ref->getProperty($property->name)->getAttributes() as $attr) {
                $to = $attr->newInstance();

                if (method_exists($to, 'toJson')) {
                    $data = $to->toJson($data, $parentData);
                }
            }

            if (empty($data)) continue;

            $propResult[] = [
                'prop' => $property->name,
                'data' => $data
            ];
        }

        if (empty($methodResult) && empty($propResult)) {
            return false;
        }

        return [
            'class' => $classPath,
            'parent' => $parentData,
            'methods' => $methodResult,
            'props' => $propResult
        ];
    }

}