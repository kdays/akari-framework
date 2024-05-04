<?php

namespace Akari\command;


use Akari\Core;
use Akari\system\container\BaseTask;
use Akari\system\docs\APIDocsRender;
use Akari\system\docs\IAPIDocsConfig;
use Akari\system\storage\Storage;
use Akari\system\util\TextUtil;

class CreateApiDocsCommand extends BaseTask {

    public static $command = 'make:api-docs';
    public static $description = "创建Api文档";

    public function handle($params) {
        /** @var IAPIDocsConfig $config */
        $config = Core::env('apiDocs');
        if (!$config) {
            $this->msg("<error>Api-Docs未开启</error>");
            return false;
        }

        $apiDir = $config::getActionDir();
        $apiDir = str_replace('./', APP_ROOT_DIR . '/', $apiDir);

        $actionResult = APIDocsRender::callFiles($apiDir, function($path)  {
            $this->msg($path);

            $result = APIDocsRender::parseFile($path);

            if ($result) {
                foreach ($result['methods'] as $methodKey => $method) {
                    $method['url'] = $this->url->getApiUrl($result['class'], $method['method']);

                    $result['methods'][$methodKey] = $method;
                }
            }

            return $result;
        });


        $schemaResult = $config ? $config::getSchema() : [];

        $output = json_encode([
            'actions' => $actionResult,
            'schema' => $schemaResult
        ]);
        file_put_contents(Core::$baseDir.'/data/api-docs.json', $output);
    }


}