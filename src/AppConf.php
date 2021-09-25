<?php

namespace mgboot\common;

use mgboot\common\swoole\Swoole;
use mgboot\common\util\ArrayUtils;
use mgboot\common\util\StringUtils;

final class AppConf
{
    /**
     * @var string
     */
    private static $env = 'dev';

    /**
     * @var array
     */
    private static $data = [];

    public static function setEnv(string $env): void
    {
        defined('_ENV_') && define('_ENV_', $env);
        self::$env = $env;
    }

    public static function getEnv(): string
    {
        return self::$env;
    }
    
    public static function setData(array $data): void
    {
        if (is_object(Swoole::getServer()) && !Swoole::inTaskWorker()) {
            $key = 'worker' . Swoole::getWorkerId();
        } else {
            $key = 'noworker';
        }

        self::$data[$key] = $data;
    }

    public static function get(string $key)
    {
        if (strpos($key, '.') === false) {
            return self::getValueInternal($key);
        }

        $lastKey = StringUtils::substringAfterLast($key, '.');
        $keys = explode('.', StringUtils::substringBeforeLast($key, '.'));
        $map1 = [];

        foreach ($keys as $i => $key) {
            if ($i === 0) {
                $map1 = self::getValueInternal($key);
                continue;
            }

            if (!is_array($map1) || empty($map1)) {
                break;
            }

            $map1 = self::getValueInternal($key, $map1);
        }

        return self::getValueInternal($lastKey, $map1);
    }

    public static function getAssocArray(string $key): array
    {
        $map1 = self::get($key);
        return ArrayUtils::isAssocArray($map1) ? $map1 : [];
    }

    public static function getInt(string $key, int $defaultValue = PHP_INT_MIN): int
    {
        return Cast::toInt(self::get($key), $defaultValue);
    }

    public static function getFloat(string $key, float $defaultValue = PHP_FLOAT_MIN): float
    {
        return Cast::toFloat(self::get($key), $defaultValue);
    }

    public static function getString(string $key, string $defaultValue = ''): string
    {
        return Cast::toString(self::get($key), $defaultValue);
    }

    public static function getBoolean(string $key, bool $defaultValue = false): bool
    {
        return Cast::toBoolean(self::get($key), $defaultValue);
    }

    public static function getDuration(string $key): int
    {
        return StringUtils::toDuration(self::getString($key));
    }

    public static function getDataSize(string $key): int
    {
        return StringUtils::toDataSize(self::getString($key));
    }

    /**
     * @param string $key
     * @return int[]
     */
    public static function getIntArray(string $key): array
    {
        return Cast::toIntArray(self::get($key));
    }

    /**
     * @param string $key
     * @return string[]
     */
    public static function getStringArray(string $key): array
    {
        return Cast::toStringArray(self::get($key));
    }

    public static function getMapList(string $key): array
    {
        return Cast::toMapList(self::get($key));
    }

    private static function getValueInternal(string $mapKey, ?array $data = null)
    {
        if (empty($data)) {
            $data = self::getData();
        }

        if (!is_array($data) || empty($data)) {
            return null;
        }

        $mapKey = strtolower(strtr($mapKey, ['-' => '', '_' => '']));

        foreach ($data as $key => $val) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $compareKey = strtolower(strtr($key, ['-' => '', '_' => '']));

            if ($compareKey === $mapKey) {
                return $val;
            }
        }

        return null;
    }

    private static function getData(): array
    {
        if (is_object(Swoole::getServer()) && !Swoole::inTaskWorker()) {
            $key = 'worker' . Swoole::getWorkerId();
        } else {
            $key = 'noworker';
        }

        $data = self::$data[$key];
        return is_array($data) ? $data : [];
    }
}
