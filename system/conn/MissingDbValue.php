<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/2/15
 * Time: 上午11:48
 */

namespace Akari\system\conn;


class MissingDbValue extends DBException {

    private $key;
    
    private $mapId;
    
    public function getKey() {
        return $this->key;
    }
    
    public function getMapId() {
        return $this->mapId;
    }
    
    public function __construct($mapId, $missingKey) {
        $this->message = "SQLMap [ $mapId ] require [ $missingKey ]";
        $this->key = $missingKey;
        $this->mapId = $mapId;
    }

}