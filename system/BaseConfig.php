<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:53
 */

namespace Akari\system;

use Akari\system\router\Router;
use Akari\system\storage\Storage;
use Akari\system\security\cipher\AESCipher;
use Akari\system\security\filter\RawFilter;
use Akari\system\security\filter\StrFilter;
use Akari\system\storage\handler\FileStorageHandler;

abstract class BaseConfig {

    public $defaultURI;
    public $parameterDefaultFilter = RawFilter::class;
    public $uriMode = Router::URI_MODE_AUTO;
    public $uriSuffix = '';

    public $uriRewrite = [];
    public $timeOffset = 0;
    public $timeZone = 'Asia/shanghai';

    public $storage = [
        Storage::KEY_DEFAULT => [
            'handler' => FileStorageHandler::class,
            'host' => '/attachments/',
            'baseDir' => AKARI_PATH . "/../public/attachments/"
        ]
    ];

    public $filters = [
        'default' => StrFilter::class
    ];

    public $encrypt = [
        'default' => [
            'cipher' => AESCipher::class
        ]
    ];

}
