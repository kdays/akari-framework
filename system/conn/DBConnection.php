<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/30
 * Time: 下午6:22
 */

namespace Akari\system\conn;

use \PDO;

class DBConnection {
    
    protected $options;
    
    private $readConn;
    private $writeConn;

    public function __construct(array $options) {
        $this->options = $options;
    }
    
    public function connect(array $options) {
        if (!class_exists("PDO")) {
            throw new DBException("PDO Extension not installed!");
        }
        
        try {
            $connection = new PDO($options['dsn'], $options['username'], $options['password'], $options['options']);
        } catch (\PDOException $e) {
            throw new DBException("Connect Failed: ". $e->getMessage());
        }
        
        return $connection;
    }
    
    public function getReadConnection() {
        if (!$this->readConn) {
            $opts = $this->options;

            // 主从分离存在从机时优先选择从机
            if (array_key_exists("slaves", $opts)) {
                $opts = $this->options['slaves'][ array_rand($opts['slaves']) ];
                $this->readConn = $this->connect($opts);
            } else {
                $this->readConn = $this->getWriteConnection();   
            }
        }

        return $this->readConn;
    }
    
    public function getWriteConnection() {
        if (!$this->writeConn) {
            $opts = $this->options;
            $this->writeConn = $this->connect($opts);
        }

        return $this->writeConn;
    }

}