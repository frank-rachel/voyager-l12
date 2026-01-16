<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Database\Types\Type;

abstract class SchemaManager
{
    /**
     * Cache for the schema manager instance.
     */
    protected static $managerCache = null;

    public static function __callStatic($method, $args)
    {
        return static::manager()->$method(...$args);
    }

    /**
     * Get the Doctrine Schema Manager, handling different Laravel/DBAL versions.
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public static function manager()
    {
        if (static::$managerCache !== null) {
            return static::$managerCache;
        }

        $connection = DB::connection();

        // Laravel 11+ / DBAL 4.0 compatibility
        if (method_exists($connection, 'getDoctrineSchemaManager')) {
            static::$managerCache = $connection->getDoctrineSchemaManager();
        } elseif (method_exists($connection, 'getSchemaManager')) {
            static::$managerCache = $connection->getSchemaManager();
        } else {
            // Fallback for newer versions - get doctrine connection first
            $doctrineConnection = static::getDatabaseConnection();
            static::$managerCache = $doctrineConnection->createSchemaManager();
        }

        return static::$managerCache;
    }

    /**
     * Get the Doctrine database connection, handling different Laravel/DBAL versions.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public static function getDatabaseConnection()
    {
        $connection = DB::connection();

        if (method_exists($connection, 'getDoctrineConnection')) {
            return $connection->getDoctrineConnection();
        }

        // For newer Laravel versions
        if (method_exists($connection, 'getPdo')) {
            // Build Doctrine connection from PDO
            $pdo = $connection->getPdo();
            $driver = $connection->getDriverName();

            $driverMap = [
                'mysql' => 'pdo_mysql',
                'pgsql' => 'pdo_pgsql',
                'sqlite' => 'pdo_sqlite',
                'sqlsrv' => 'pdo_sqlsrv',
            ];

            $params = [
                'pdo' => $pdo,
                'driver' => $driverMap[$driver] ?? 'pdo_mysql',
            ];

            return \Doctrine\DBAL\DriverManager::getConnection($params);
        }

        throw new \RuntimeException('Unable to get Doctrine database connection');
    }

    /**
     * Get the database platform, handling different DBAL versions.
     *
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    public static function getDatabasePlatform()
    {
        $manager = static::manager();

        // DBAL 4.0 uses createSchemaManager which has getDatabasePlatform
        // DBAL 3.x uses getSchemaManager
        if (method_exists($manager, 'getDatabasePlatform')) {
            return $manager->getDatabasePlatform();
        }

        // Try getting platform from connection
        $connection = static::getDatabaseConnection();
        if (method_exists($connection, 'getDatabasePlatform')) {
            return $connection->getDatabasePlatform();
        }

        throw new \RuntimeException('Unable to get database platform');
    }

    public static function tableExists($table)
    {
        // Use Laravel's Schema facade for better compatibility
        if (!is_array($table)) {
            return Schema::hasTable($table);
        }

        foreach ($table as $t) {
            if (!Schema::hasTable($t)) {
                return false;
            }
        }

        return true;
    }

    public static function listTables()
    {
        $tables = [];

        foreach (static::listTableNames() as $tableName) {
            $tables[$tableName] = static::listTableDetails($tableName);
        }

        return $tables;
    }

    /**
     * Get list of table names, handling different DBAL versions.
     *
     * @return array
     */
    public static function listTableNames()
    {
        $manager = static::manager();

        // DBAL 4.0 changed listTableNames to listTables which returns Table objects
        if (method_exists($manager, 'listTableNames')) {
            return $manager->listTableNames();
        }

        // DBAL 4.0 approach
        if (method_exists($manager, 'listTables')) {
            $tables = $manager->listTables();
            return array_map(function ($table) {
                return $table->getName();
            }, $tables);
        }

        // Fallback to Laravel's method
        return Schema::getTableListing();
    }

    /**
     * @param string $tableName
     *
     * @return \TCG\Voyager\Database\Schema\Table
     */
    public static function listTableDetails($tableName)
    {
        $manager = static::manager();
        $columns = $manager->listTableColumns($tableName);

        $foreignKeys = [];
        $platform = static::getDatabasePlatform();
        if (method_exists($platform, 'supportsForeignKeyConstraints') && $platform->supportsForeignKeyConstraints()) {
            $foreignKeys = $manager->listTableForeignKeys($tableName);
        }

        $indexes = $manager->listTableIndexes($tableName);

        return new Table($tableName, $columns, $indexes, [], $foreignKeys, []);
    }

    /**
     * Describes given table.
     *
     * @param string $tableName
     *
     * @return \Illuminate\Support\Collection
     */
    public static function describeTable($tableName)
    {
        Type::registerCustomPlatformTypes();

        $table = static::listTableDetails($tableName);

        return collect($table->columns)->map(function ($column) use ($table) {
            $columnArr = Column::toArray($column);

            $columnArr['field'] = $columnArr['name'];
            $columnArr['type'] = $columnArr['type']['name'];

            // Set the indexes and key
            $columnArr['indexes'] = [];
            $columnArr['key'] = null;
            if ($columnArr['indexes'] = $table->getColumnsIndexes($columnArr['name'], true)) {
                // Convert indexes to Array
                foreach ($columnArr['indexes'] as $name => $index) {
                    $columnArr['indexes'][$name] = Index::toArray($index);
                }

                // If there are multiple indexes for the column
                // the Key will be one with highest priority
                $indexType = array_values($columnArr['indexes'])[0]['type'];
                $columnArr['key'] = substr($indexType, 0, 3);
            }

            return $columnArr;
        });
    }

    public static function listTableColumnNames($tableName)
    {
        Type::registerCustomPlatformTypes();

        $columnNames = [];

        foreach (static::manager()->listTableColumns($tableName) as $column) {
            $columnNames[] = $column->getName();
        }

        return $columnNames;
    }

    public static function createTable($table)
    {
        if (!($table instanceof DoctrineTable)) {
            $table = Table::make($table);
        }

        static::manager()->createTable($table);
    }

    public static function getDoctrineTable($table)
    {
        $table = trim($table);

        if (!static::tableExists($table)) {
            throw SchemaException::tableDoesNotExist($table);
        }

        return static::manager()->introspectTable($table);
    }

    public static function getDoctrineColumn($table, $column)
    {
        return static::getDoctrineTable($table)->getColumn($column);
    }

    /**
     * Clear the manager cache.
     */
    public static function clearCache()
    {
        static::$managerCache = null;
    }
}
