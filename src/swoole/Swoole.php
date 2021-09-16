<?php

namespace mgboot\common\swoole;

use Closure;
use mgboot\common\Cast;
use Throwable;

final class Swoole
{
    private static $server = null;

    private function __construct()
    {
    }

    public static function setServer($server): void
    {
        self::$server = $server;
    }

    public static function getServer()
    {
        return self::$server;
    }

    public static function withTable($server, string $tableName): void
    {
        if (!is_object($server) || $tableName === '') {
            return;
        }

        switch ($tableName) {
            case SwooleTable::cacheTableName():
                $columns = [
                    ['value', SwooleTable::COLUMN_TYPE_STRING, 1024 * 4],
                    ['expiry', SwooleTable::COLUMN_TYPE_INT]
                ];

                try {
                    $server->$tableName = SwooleTable::buildTable($columns, 4096);
                } catch (Throwable $ex) {
                }

                break;
            case SwooleTable::poolTableName():
                $columns = [
                    ['poolId', SwooleTable::COLUMN_TYPE_STRING, 128],
                    ['currentActive', SwooleTable::COLUMN_TYPE_INT],
                    ['idleCheckRunning', SwooleTable::COLUMN_TYPE_INT],
                    ['lastUsedAt', SwooleTable::COLUMN_TYPE_STRING, 64]
                ];

                try {
                    $server->$tableName = SwooleTable::buildTable($columns, 2048);
                } catch (Throwable $ex) {
                }

                break;
            case SwooleTable::wsTableName():
                $columns = [
                    ['fd', SwooleTable::COLUMN_TYPE_INT],
                    ['jwtClaims', SwooleTable::COLUMN_TYPE_STRING, 512],
                    ['lastPongAt', SwooleTable::COLUMN_TYPE_STRING, 64]
                ];

                try {
                    $server->$tableName = SwooleTable::buildTable($columns, 16 * 1024);
                } catch (Throwable $ex) {
                }

                break;
        }
    }

    public static function isSwooleHttpRequest($arg0): bool
    {
        if (!is_object($arg0)) {
            return false;
        }

        return strpos(get_class($arg0), "Swoole\\Http\\Request") !== false;
    }

    public static function isSwooleHttpResponse($arg0): bool
    {
        if (!is_object($arg0)) {
            return false;
        }

        return strpos(get_class($arg0), "Swoole\\Http\\Response") !== false;
    }

    public static function getWorkerId(): int
    {
        $server = self::$server;

        if (!is_object($server) || !property_exists($server, 'worker_id')) {
            return -1;
        }

        $workerId = Cast::toInt($server->worker_id);
        return $workerId >= 0 ? $workerId : -1;
    }

    public static function inTaskWorker(): bool
    {
        $workerId = self::getWorkerId();

        if ($workerId < 0) {
            return false;
        }

        $server = self::$server;

        if (!is_object($server) || !property_exists($server, 'taskworker')) {
            return false;
        }

        return Cast::toBoolean($server->taskworker);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function getCoroutineId(): int
    {
        try {
            $cid = \Swoole\Coroutine::getCid();
            return is_int($cid) && $cid >= 0 ? $cid : -1;
        } catch (Throwable $ex) {
            return -1;
        }
    }

    public static function inCoroutineMode(bool $notTaskWorker = false): bool
    {
        if (self::getCoroutineId() < 0) {
            return false;
        }

        return !$notTaskWorker || !self::inTaskWorker();
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function newWaitGroup(): \Swoole\Coroutine\WaitGroup
    {
        return new \Swoole\Coroutine\WaitGroup();
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function newAtomic(?int $value = null): \Swoole\Atomic
    {
        return is_int($value) && $value > 0 ? new \Swoole\Atomic($value) : new \Swoole\Atomic();
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function atomicGet($atomic)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->get();
        }

        return null;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function atomicSet($atomic, int $value)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->set($value);
        }

        return null;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function atomicAdd($atomic, int $value)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->add($value);
        }

        return null;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function atomicSub($atomic, int $value)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->sub($value);
        }

        return null;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function atomicCompareAndSet($atomic, int $cmpValue, int $setValue): bool
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->cmpset($cmpValue, $setValue);
        }

        return false;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function defer(Closure $fn): void
    {
        \Swoole\Coroutine::defer($fn);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function runInCoroutine(callable $call, ...$args): void
    {
        \Swoole\Coroutine\run($call, ...$args);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function timerTick(int $ms, callable $call, ...$args): int
    {
        $id = \Swoole\Timer::tick($ms, $call, ...$args);
        return Cast::toInt($id);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function timerClear(int $timerId): void
    {
        if ($timerId < 0) {
            return;
        }

        \Swoole\Timer::clear($timerId);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function newChannel(?int $size = null): \Swoole\Coroutine\Channel
    {
        return new \Swoole\Coroutine\Channel($size);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function chanIsEmpty($ch): bool
    {
        if ($ch instanceof \Swoole\Coroutine\Channel) {
            return $ch->isEmpty();
        }

        return true;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function chanPush($ch, $data, ?float $timeout = null): void
    {
        if ($ch instanceof \Swoole\Coroutine\Channel) {
            $ch->push($data, $timeout);
        }
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function chanPop($ch, ?float $timeout = null)
    {
        if ($ch instanceof \Swoole\Coroutine\Channel) {
            return $ch->pop($timeout);
        }

        return null;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function sleep(float $seconds): void
    {
        \Swoole\Coroutine::sleep($seconds);
    }

    public static function buildGlobalVarKey(?int $workerId = null): string
    {
        if (!is_int($workerId) || $workerId < 0) {
            $workerId = self::getWorkerId();
        }

        return $workerId >= 0 ? "worker$workerId" : 'noworker';
    }
}
