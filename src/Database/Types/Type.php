<?php

namespace TCG\Voyager\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;
use Doctrine\DBAL\Types\Type as DoctrineType;
use TCG\Voyager\Database\Platforms\Platform;
use TCG\Voyager\Database\Schema\SchemaManager;

abstract class Type extends DoctrineType
{
    protected static $customTypesRegistered = false;
    protected static $platformTypeMapping = [];
    protected static $allTypes = [];
    protected static $platformTypes = [];
    protected static $customTypeOptions = [];
    protected static $typeCategories = [];

    public const NAME = 'UNDEFINED_TYPE_NAME';
    public const NOT_SUPPORTED = 'notSupported';
    public const NOT_SUPPORT_INDEX = 'notSupportIndex';

    public function getName(): string
    {
        return static::NAME;
    }

    public static function toArray(DoctrineType $type)
    {
        $customTypeOptions = $type->customOptions ?? [];

        return array_merge([
            'name' => $type->getName(),
        ], $customTypeOptions);
    }

    /**
     * Get the platform name, handling different DBAL versions.
     *
     * @param DoctrineAbstractPlatform $platform
     * @return string
     */
    protected static function getPlatformName(DoctrineAbstractPlatform $platform): string
    {
        // DBAL 4.0 removed getName() method
        if (method_exists($platform, 'getName')) {
            return $platform->getName();
        }

        // Extract platform name from class name for DBAL 4.0+
        $className = get_class($platform);
        $parts = explode('\\', $className);
        $platformClass = end($parts);

        // Convert class name to platform name (e.g., MySQLPlatform -> mysql)
        $platformName = str_replace('Platform', '', $platformClass);
        return strtolower($platformName);
    }

    public static function getPlatformTypes()
    {
        if (static::$platformTypes) {
            return static::$platformTypes;
        }

        if (!static::$customTypesRegistered) {
            static::registerCustomPlatformTypes();
        }

        $platform = SchemaManager::getDatabasePlatform();
        $platformName = static::getPlatformName($platform);

        static::$platformTypes = Platform::getPlatformTypes(
            $platformName,
            static::getPlatformTypeMapping($platform)
        );

        static::$platformTypes = static::$platformTypes->map(function ($type) {
            return static::toArray(static::getType($type));
        })->groupBy('category');

        return static::$platformTypes;
    }

    public static function getPlatformTypeMapping(DoctrineAbstractPlatform $platform)
    {
        if (static::$platformTypeMapping) {
            return static::$platformTypeMapping;
        }

        // Try to get the type mapping using different methods for DBAL compatibility
        $mapping = static::extractPlatformTypeMapping($platform);

        static::$platformTypeMapping = collect($mapping);

        return static::$platformTypeMapping;
    }

    /**
     * Extract platform type mapping, handling different DBAL versions.
     *
     * @param DoctrineAbstractPlatform $platform
     * @return array
     */
    protected static function extractPlatformTypeMapping(DoctrineAbstractPlatform $platform): array
    {
        // Try the public method first (if available)
        if (method_exists($platform, 'getDoctrineTypeMapping')) {
            return $platform->getDoctrineTypeMapping();
        }

        // Try reflection for older DBAL versions
        try {
            $reflection = new \ReflectionClass($platform);

            // Try different property names used in different DBAL versions
            $propertyNames = ['doctrineTypeMapping', 'typeMapping', '_doctrineTypeMapping'];

            foreach ($propertyNames as $propertyName) {
                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    $value = $property->getValue($platform);
                    if (is_array($value)) {
                        return $value;
                    }
                }
            }
        } catch (\ReflectionException $e) {
            // Reflection failed, fall back to default mapping
        }

        // Return a sensible default mapping based on common database types
        return static::getDefaultTypeMapping();
    }

    /**
     * Get default type mapping as fallback.
     *
     * @return array
     */
    protected static function getDefaultTypeMapping(): array
    {
        return [
            'bigint' => 'bigint',
            'binary' => 'binary',
            'blob' => 'blob',
            'boolean' => 'boolean',
            'char' => 'string',
            'date' => 'date',
            'datetime' => 'datetime',
            'decimal' => 'decimal',
            'double' => 'float',
            'enum' => 'string',
            'float' => 'float',
            'int' => 'integer',
            'integer' => 'integer',
            'json' => 'json',
            'longblob' => 'blob',
            'longtext' => 'text',
            'mediumblob' => 'blob',
            'mediumint' => 'integer',
            'mediumtext' => 'text',
            'numeric' => 'decimal',
            'real' => 'float',
            'set' => 'simple_array',
            'smallint' => 'smallint',
            'string' => 'string',
            'text' => 'text',
            'time' => 'time',
            'timestamp' => 'datetime',
            'tinyblob' => 'blob',
            'tinyint' => 'boolean',
            'tinytext' => 'text',
            'varbinary' => 'binary',
            'varchar' => 'string',
            'year' => 'date',
        ];
    }

    public static function registerCustomPlatformTypes($force = false)
    {
        if (static::$customTypesRegistered && !$force) {
            return;
        }

        $platform = SchemaManager::getDatabasePlatform();
        $platformName = ucfirst(static::getPlatformName($platform));

        $customTypes = array_merge(
            static::getPlatformCustomTypes('Common'),
            static::getPlatformCustomTypes($platformName)
        );

        foreach ($customTypes as $type) {
            $name = $type::NAME;

            if (static::hasType($name)) {
                static::overrideType($name, $type);
            } else {
                static::addType($name, $type);
            }

            $dbType = defined("{$type}::DBTYPE") ? $type::DBTYPE : $name;

            $platform->registerDoctrineTypeMapping($dbType, $name);
        }

        static::addCustomTypeOptions($platformName);

        static::$customTypesRegistered = true;
    }

    protected static function addCustomTypeOptions($platformName)
    {
        static::registerCommonCustomTypeOptions();

        Platform::registerPlatformCustomTypeOptions($platformName);

        // Add the custom options to the types
        foreach (static::$customTypeOptions as $option) {
            foreach ($option['types'] as $type) {
                if (static::hasType($type)) {
                    static::getType($type)->customOptions[$option['name']] = $option['value'];
                }
            }
        }
    }

    protected static function getPlatformCustomTypes($platformName)
    {
        $typesPath = __DIR__.DIRECTORY_SEPARATOR.$platformName.DIRECTORY_SEPARATOR;
        $namespace = __NAMESPACE__.'\\'.$platformName.'\\';
        $types = [];

        foreach (glob($typesPath.'*.php') as $classFile) {
            $types[] = $namespace.str_replace(
                '.php',
                '',
                str_replace($typesPath, '', $classFile)
            );
        }

        return $types;
    }

    public static function registerCustomOption($name, $value, $types)
    {
        if (is_string($types)) {
            $types = trim($types);

            if ($types == '*') {
                $types = static::getAllTypes()->toArray();
            } elseif (strpos($types, '*') !== false) {
                $searchType = str_replace('*', '', $types);
                $types = static::getAllTypes()->filter(function ($type) use ($searchType) {
                    return strpos($type, $searchType) !== false;
                })->toArray();
            } else {
                $types = [$types];
            }
        }

        static::$customTypeOptions[] = [
            'name'  => $name,
            'value' => $value,
            'types' => $types,
        ];
    }

    protected static function registerCommonCustomTypeOptions()
    {
        static::registerTypeCategories();
        static::registerTypeDefaultOptions();
    }

    protected static function registerTypeDefaultOptions()
    {
        $types = static::getTypeCategories();

        // Numbers
        static::registerCustomOption('default', [
            'type' => 'number',
            'step' => 'any',
        ], $types['numbers']);

        // Date and Time
        static::registerCustomOption('default', [
            'type' => 'date',
        ], 'date');
        static::registerCustomOption('default', [
            'type' => 'time',
            'step' => '1',
        ], 'time');
        static::registerCustomOption('default', [
            'type' => 'number',
            'min'  => '0',
        ], 'year');
    }

    protected static function registerTypeCategories()
    {
        $types = static::getTypeCategories();

        static::registerCustomOption('category', 'Numbers', $types['numbers']);
        static::registerCustomOption('category', 'Strings', $types['strings']);
        static::registerCustomOption('category', 'Date and Time', $types['datetime']);
        static::registerCustomOption('category', 'Lists', $types['lists']);
        static::registerCustomOption('category', 'Binary', $types['binary']);
        static::registerCustomOption('category', 'Geometry', $types['geometry']);
        static::registerCustomOption('category', 'Network', $types['network']);
        static::registerCustomOption('category', 'Objects', $types['objects']);
    }

    public static function getAllTypes()
    {
        if (static::$allTypes) {
            return static::$allTypes;
        }

        static::$allTypes = collect(static::getTypeCategories())->flatten();

        return static::$allTypes;
    }

    public static function getTypeCategories()
    {
        if (static::$typeCategories) {
            return static::$typeCategories;
        }

        $numbers = [
            'boolean',
            'tinyint',
            'smallint',
            'mediumint',
            'integer',
            'int',
            'bigint',
            'decimal',
            'numeric',
            'money',
            'float',
            'real',
            'double',
            'double precision',
        ];

        $strings = [
            'char',
            'character',
            'varchar',
            'character varying',
            'string',
            'guid',
            'uuid',
            'tinytext',
            'text',
            'mediumtext',
            'longtext',
            'tsquery',
            'tsvector',
            'xml',
        ];

        $datetime = [
            'date',
            'datetime',
            'year',
            'time',
            'timetz',
            'timestamp',
            'timestamptz',
            'datetimetz',
            'dateinterval',
            'interval',
        ];

        $lists = [
            'enum',
            'set',
            'simple_array',
            'array',
            'json',
            'jsonb',
            'json_array',
        ];

        $binary = [
            'bit',
            'bit varying',
            'binary',
            'varbinary',
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'bytea',
        ];

        $network = [
            'cidr',
            'inet',
            'macaddr',
            'txid_snapshot',
        ];

        $geometry = [
            'geometry',
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection',
        ];

        $objects = [
            'object',
        ];

        static::$typeCategories = [
            'numbers'  => $numbers,
            'strings'  => $strings,
            'datetime' => $datetime,
            'lists'    => $lists,
            'binary'   => $binary,
            'network'  => $network,
            'geometry' => $geometry,
            'objects'  => $objects,
        ];

        return static::$typeCategories;
    }
}
