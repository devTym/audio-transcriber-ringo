<?php

namespace System\Redis;

use Redis;
use RuntimeException;

class RedisClient
{
    private ?Redis $redis = null;

    private string $host;
    private int    $port;
    private string $password;
    private int    $db;
    private float  $timeout;

    public function __construct()
    {
        $this->host     = getenv('REDIS_HOST')     ?: '127.0.0.1';
        $this->port     = (int)(getenv('REDIS_PORT')    ?: 6379);
        $this->password = getenv('REDIS_PASSWORD') ?: '';
        $this->db       = (int)(getenv('REDIS_DB')      ?: 0);
        $this->timeout  = (float)(getenv('REDIS_TIMEOUT') ?: 2.0);
    }

    public function getConnection(): Redis
    {
        if ($this->redis === null) {
            $this->redis = $this->connect();
        }

        return $this->redis;
    }

    public function rpush(string $key, string $value): int|false
    {
        return $this->getConnection()->rPush($key, $value);
    }

    public function lpop(string $key): string|false|null
    {
        return $this->getConnection()->lPop($key);
    }

    public function blpop(string $key, int $timeout = 0): array|false|null
    {
        return $this->getConnection()->blPop([$key], $timeout);
    }

    public function sadd(string $key, string ...$members): int|false
    {
        return $this->getConnection()->sAdd($key, ...$members);
    }

    public function srem(string $key, string ...$members): int|false
    {
        return $this->getConnection()->sRem($key, ...$members);
    }

    public function sismember(string $key, string $member): bool
    {
        return (bool) $this->getConnection()->sIsMember($key, $member);
    }

    public function llen(string $key): int|false
    {
        return $this->getConnection()->lLen($key);
    }

    private function connect(): Redis
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException(
                'PHP Redis extension is not loaded'
            );
        }

        $redis = new Redis();

        $connected = $redis->connect($this->host, $this->port, $this->timeout);

        if (!$connected) {
            throw new RuntimeException(
                "Failed connect to Redis {$this->host}:{$this->port}"
            );
        }

        if ($this->password !== '') {
            $redis->auth($this->password);
        }

        if ($this->db !== 0) {
            $redis->select($this->db);
        }

        return $redis;
    }
}