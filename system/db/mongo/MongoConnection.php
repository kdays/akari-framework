<?php


namespace Akari\system\db\mongo;

use Akari\Core;

class MongoConnection {

    protected $options;
    protected $connection;
    protected static $instances = [];

    public static function init(string $config = 'default') {
        if (!isset(self::$instances[$config])) {
            self::$instances[$config] = new self( Core::env('mongo')[$config] );
        }

        return self::$instances[$config];
    }

    public function __construct(array $options) {
        $this->options = $options;
    }

    private function connect(array $config) {
        if (isset($config['url'])) {
            $dsn = $config['url'];
        } elseif (empty($config['username']) || empty($config['password'])) {
            $dsn = 'mongodb://' . $config['host']. ":". $config['port'];
        } else {
            $dsn = sprintf(
                'mongodb://%s:%s@%s:%d',
                $config['username'],
                $config['password'],
                $config['host'],
                $config['port']
            );
        }

        $drvOpts = ($config['driverOptions'] ?? []) + [
            'typeMap' => [
                'array' => 'array',
                'document' => 'array'
            ]
        ];

        $client = new \MongoDB\Client($dsn, [], $drvOpts);
        return $client->selectDatabase($config['database']);
    }

    /**
     * @return \MongoDB\Database
     */
    public function getQuery(): \MongoDB\Database {
        if (empty($this->connection)) {
            $this->connection = $this->connect($this->options);
        }

        return $this->connection;
    }

    public function migration() {
        return new MongoMigration($this);
    }
}
