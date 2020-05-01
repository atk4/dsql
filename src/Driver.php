<?php

namespace atk4\dsql;

/**
 * Class for establishing and maintaining connection with your database.
 */
class Driver extends Connection
{
    /**
     * Database driver abbreviation, for example mysql, sqlite, pgsql, oci etc.
     * This is filled automatically while connection database.
     *
     * @var string
     */
    public $type;

    /**
     * Stores the driverType => driverClass array for resolving.
     *
     * @var array
     */
    protected static $registry = [
        'sqlite' => SQLite\Driver::class,
        'mysql' => MySQL\Driver::class,
        'pgsql' => PgSQL\Driver::class,
        'oci' => Oracle\Driver::class,
        'dumper' => Debug\Stopwatch\Driver::class, // backward compatibility
        'stopwatch' => Debug\Stopwatch\Driver::class,
        'counter' => Debug\Profiler\Driver::class, // backward compatibility
        'profile' => Debug\Profiler\Driver::class,
    ];

    /**
     * Adds connection class to the registry for resolving in Connection::resolve method.
     *
     * Can be used as:
     *
     * Connection::register('mysql', MySQL\Connection::class), or
     * MySQL\Connection::register()
     *
     * CustomDriver\Connection must be descendant of Connection class.
     *
     * @param string $driverType
     * @param string $driverClass
     */
    public static function register($driverType = null, $driverClass = null)
    {
        if (!$driverClass && is_a($driverType, parent::class, true)) {
            $driverClass = $driverType;
            $driverType = null;
        }

        $driverClass = $driverClass ?? static::class;

        $driverType = $driverType ?? $driverClass::defaultType();

        if (is_array($driverTypes = $driverType)) {
            foreach ($driverTypes as $driverType => $driverClass) {
                if (is_numeric($driverType)) {
                    $driverType = $driverClass::defaultType();
                }

                static::register($driverType, $driverClass);
            }
        }

        self::$registry[$driverType] = $driverClass;
    }

    /**
     * Resolves the connection class to use based on driver type.
     *
     * @param string $driverType
     *
     * @return string
     */
    public static function resolve($driverType)
    {
        return self::$registry[$driverType] ?? static::class;
    }

    /**
     * Resolves $dsn to a driver
     * By default the driver is new PDO object which can be overridden in child classes.
     *
     * This does not silence PDO errors.
     */
    public static function factory(array $dsn)
    {
        return new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    /**
     * Returns the default driver type set for the connection in $driverType.
     *
     * @return string|null
     */
    public static function defaultType()
    {
        return (new \ReflectionClass(static::class))->getDefaultProperties()['type'] ?? null;
    }

    public function getType()
    {
        return $this->getDriverType();
    }
}
