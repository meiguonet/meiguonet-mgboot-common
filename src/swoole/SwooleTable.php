<?php

namespace mgboot\common\swoole;

use Throwable;

final class SwooleTable
{
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_FLOAT = 2;
    const COLUMN_TYPE_STRING = 3;

    /**
     * @var string
     */
    private static $_cacheTableName = 'cache';

    /**
     * @var string
     */
    private static $_poolTableName = 'pool';

    /**
     * @var string
     */
    private static $_wsTableName = 'wsConnections';

    private function __construct()
    {
    }

    public static function cacheTableName(?string $name = null): string
    {
        if (is_string($name) && $name !== '') {
            self::$_cacheTableName = $name;
            return '';
        }

        return self::$_cacheTableName;
    }

    public static function poolTableName(?string $name = null): string
    {
        if (is_string($name) && $name !== '') {
            self::$_poolTableName = $name;
            return '';
        }

        return self::$_poolTableName;
    }

    public static function wsTableName(?string $name = null): string
    {
        if (is_string($name) && $name !== '') {
            self::$_wsTableName = $name;
            return '';
        }

        return self::$_wsTableName;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function buildTable(array $columns, int $size = 1024): \Swoole\Table
    {
        $table = new \Swoole\Table($size);

        foreach ($columns as $col) {
            list($name, $type, $dataSize) = $col;
            $type = self::parseColumnType($type);

            if (is_int($dataSize) && $dataSize > 0) {
                $table->column($name, $type, $dataSize);
            } else {
                $table->column($name, $type);
            }
        }

        $table->create();
        return $table;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function getTable(string $name): ?\Swoole\Table
    {
        try {
            $table = Swoole::getServer()->$name;
        } catch (Throwable $ex) {
            $table = null;
        }

        return $table instanceof \Swoole\Table ? $table : null;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function exists(string $tableName, string $key): bool
    {
        $table = self::getTable($tableName);
        return $table instanceof \Swoole\Table ? $table->exist($key) : false;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function remove(string $tableName, string $key): void
    {
        $table = self::getTable($tableName);

        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->del($key);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function setValue(string $tableName, string $key, array $value): void
    {
        $table = self::getTable($tableName);

        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->set($key, $value);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    public static function incr(string $tableName, string $key, string $columnName, $num): void
    {
        $table = self::getTable($tableName);

        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->incr($key, $columnName, $num);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    public static function decr(string $tableName, string $key, string $columnName, $num): void
    {
        $table = self::getTable($tableName);

        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->decr($key, $columnName, $num);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function getValue(string $tableName, string $key): ?array
    {
        $table = self::getTable($tableName);

        if (!($table instanceof \Swoole\Table)) {
            return null;
        }

        $value = $table->get($key);
        return is_array($value) ? $value : null;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private static function parseColumnType(int $type): int
    {
        switch ($type) {
            case self::COLUMN_TYPE_INT:
                return \Swoole\Table::TYPE_INT;
            case self::COLUMN_TYPE_FLOAT:
                return \Swoole\Table::TYPE_FLOAT;
            default:
                return \Swoole\Table::TYPE_STRING;
        }
    }
}
