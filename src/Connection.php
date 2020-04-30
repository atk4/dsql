<?php

namespace atk4\dsql;

/**
 * Class for establishing and maintaining connection with your database.
 */
class Connection
{
    use \atk4\core\DIContainerTrait;

    /**
     * Database driver abbreviation, for example mysql, sqlite, pgsql, oci etc.
     * This is filled automatically while connection database.
     *
     * @var string
     */
    public $driverType;

    /** @var int Current depth of transaction */
    public $transactionDepth = 0;

    /**
     * @deprecated use $transactionDepth instead
     */
    public $transaction_depth;

    /**
     * @deprecated use $queryClass instead
     */
    protected $query_class;

    /** @var string Query classname */
    protected $queryClass = Query::class;

    /**
     * @deprecated use $expressionClass instead
     */
    protected $expression_class;

    /** @var string Expression classname */
    protected $expressionClass = Expression::class;

    /** @var Connection|\PDO Connection or PDO object */
    protected $driver;

    /**
     * @deprecated use $driver instead
     */
    protected $connection;

    /**
     * Stores the driverType => driverClass array for resolving.
     *
     * @var array
     */
    protected static $driverRegistry = [
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
     * Specifying $properties to constructors will override default property values of this class.
     *
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        if (!is_array($properties)) {
            throw new Exception([
                'Invalid properties for "new Connection()". Did you mean to call Connection::create()?',
                'properties' => $properties,
            ]);
        }

        $this->setDefaults($properties);

        // backward compatibility
        $this->driver = $this->driver ?? $this->connection;
        $this->transactionDepth = $this->transactionDepth ?? $this->transaction_depth;
        $this->queryClass = $this->queryClass ?? $this->query_class;
        $this->expressionClass = $this->expressionClass ?? $this->expression_class;
    }

    /**
     * Normalize DSN connection string.
     *
     * Returns normalized DSN as array ['dsn', 'user', 'pass', 'driverType', 'rest'].
     *
     * @param array|string $dsn  DSN string
     * @param string       $user Optional username, this takes precedence over dsn string
     * @param string       $pass Optional password, this takes precedence over dsn string
     *
     * @return array
     */
    public static function normalizeDSN($dsn, $user = null, $pass = null)
    {
        // Try to dissect DSN into parts
        $parts = is_array($dsn) ? $dsn : parse_url($dsn);

        // If parts are usable, convert DSN format
        if ($parts !== false && isset($parts['host'], $parts['path'])) {
            // DSN is using URL-like format, so we need to convert it
            $dsn = $parts['scheme'] . ':host=' . $parts['host']
                . (isset($parts['port']) ? ';port=' . $parts['port'] : '')
                . ';dbname=' . substr($parts['path'], 1);
            $user = $user ?? ($parts['user'] ?? null);
            $pass = $pass ?? ($parts['pass'] ?? null);
        }

        // If it's still array, then simply use it
        if (is_array($dsn)) {
            return $dsn;
        }

        // If it's string, then find driver
        if (is_string($dsn)) {
            if (strpos($dsn, ':') === false) {
                throw new Exception([
                    "Your DSN format is invalid. Must be in 'driverType:host;options' format",
                    'dsn' => $dsn,
                ]);
            }
            list($driverType, $rest) = explode(':', $dsn, 2);
            $driverType = strtolower($driverType);
        } else {
            // currently impossible to be like this, but we don't want ugly exceptions here
            $driverType = $rest = null;
        }

        return ['dsn' => $dsn, 'user' => $user ?: null, 'pass' => $pass ?: null, 'driverType' => $driverType, 'rest' => $rest];
    }

    /**
     * @deprecated use Connection::create instead
     */
    public static function connect($dsn, $user = null, $password = null, $args = [])
    {
        return static::create(...func_get_args());
    }

    /**
     * Connect to database.
     *
     * @param string|\PDO $dsn
     * @param string|null $user
     * @param string|null $password
     * @param array       $args
     *
     * @return Connection
     */
    public static function create($dsn, $user = null, $password = null, $args = [])
    {
        // If it's already PDO object, then we simply use it
        if ($dsn instanceof \PDO) {
            $driverType = $dsn->getAttribute(\PDO::ATTR_DRIVER_NAME);

            /**
             * @var Connection $driverClass
             */
            $driverClass = self::resolveDriver($driverType);

            return new $driverClass(array_merge([
                'driver' => $dsn,
            ], $args));
        }

        // If it's some other object, then we simply use it trough proxy connection
        if (is_object($dsn)) {
            return new ProxyConnection(array_merge([
                'driver' => $dsn,
            ], $args));
        }

        // Process DSN string
        $dsn = static::normalizeDSN($dsn, $user, $password);

        /**
         * @var Connection $driverClass
         */
        $driverClass = self::resolveDriver($dsn['driverType']);

        return new $driverClass(array_merge([
            'driver' => $driverClass::createDriver($dsn),
        ], $args));
    }

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
    public static function registerDriver($driverType = null, $driverClass = null)
    {
        if (!$driverClass && is_a($driverType, self::class, true)) {
            $driverClass = $driverType;
            $driverType = null;
        }

        $driverClass = $driverClass ?? static::class;

        $driverType = $driverType ?? $driverClass::defaultDriverType();

        if (is_array($driverTypes = $driverType)) {
            foreach ($driverTypes as $driverType => $driverClass) {
                if (is_numeric($driverType)) {
                    $driverType = $driverClass::defaultDriverType();
                }

                static::registerDriver($driverType, $driverClass);
            }
        }

        self::$driverRegistry[$driverType] = $driverClass;
    }

    /**
     * Resolves the connection class to use based on driver type.
     *
     * @param string $driverType
     *
     * @return string
     */
    public static function resolveDriver($driverType)
    {
        return self::$driverRegistry[$driverType] ?? static::class;
    }

    /**
     * Resolves $dsn to a driver
     * By default the driver is new PDO object which can be overridden in child classes.
     *
     * This does not silence PDO errors.
     */
    public static function createDriver(array $dsn)
    {
        return new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    /**
     * Returns new Query object with connection already set.
     *
     * @param string|array $properties
     */
    public function dsql($properties = []): Query
    {
        $query = new $this->queryClass($properties);

        $query->connection = $this->driver();

        return $query;
    }

    /**
     * Returns Expression object with connection already set.
     *
     * @param string|array $properties
     * @param array        $args
     */
    public function expr($properties = [], $args = null): Expression
    {
        $expression = new $this->expressionClass($properties, $args);

        $expression->connection = $this->driver();

        return $expression;
    }

    /**
     * @deprecated use Connection::driver() instead
     */
    public function connection()
    {
        return $this->driver();
    }

    /**
     * Returns the connection driver.
     *
     * @return Connection|\PDO
     */
    public function driver()
    {
        return $this->driver ?? $this;
    }

    /**
     * Returns the default driver type set for the connection in $driverType.
     *
     * @return string|null
     */
    public static function defaultDriverType()
    {
        return (new \ReflectionClass(static::class))->getDefaultProperties()['driverType'] ?? null;
    }

    /**
     * Execute Expression by using this connection.
     *
     * @return \PDOStatement
     */
    public function execute(Expression $expr)
    {
        // If custom connection is set, execute again using that
        if ($this->driver && $this->driver !== $this) {
            return $expr->execute($this->driver);
        }

        throw new Exception('Queries cannot be executed through this connection');
    }

    /**
     * Atomic executes operations within one begin/end transaction, so if
     * the code inside callback will fail, then all of the transaction
     * will be also rolled back.
     */
    public function atomic(callable $fx, ...$args)
    {
        $this->beginTransaction();

        try {
            $res = call_user_func_array($fx, $args);
            $this->commit();

            return $res;
        } catch (\Exception $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * Starts new transaction.
     *
     * Database driver supports statements for starting and committing
     * transactions. Unfortunately most of them don't allow to nest
     * transactions and commit gradually.
     * With this method you have some implementation of nested transactions.
     *
     * When you call it for the first time it will begin transaction. If you
     * call it more times, it will do nothing but will increase depth counter.
     * You will need to call commit() for each execution of beginTransactions()
     * and only the last commit will perform actual commit in database.
     *
     * So, if you have been working with the database and got un-handled
     * exception in the middle of your code, everything will be rolled back.
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function beginTransaction()
    {
        // transaction starts only if it was not started before
        $r = $this->inTransaction()
            ? false
            : $this->driver->beginTransaction();

        ++$this->transactionDepth;

        return $r;
    }

    /**
     * Will return true if currently running inside a transaction
     * This is useful if you are logging anything into a database. If you are
     * inside a transaction, don't log or it may be rolled back.
     * Perhaps use a hook for this?
     *
     * @see beginTransaction()
     *
     * @return bool if in transaction
     */
    public function inTransaction()
    {
        return $this->transactionDepth > 0;
    }

    /**
     * Commits transaction.
     *
     * Each occurrence of beginTransaction() must be matched with commit().
     * Only when same amount of commits are executed, the actual commit will be
     * issued to the database.
     *
     * @see beginTransaction()
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function commit()
    {
        // check if transaction is actually started
        if (!$this->inTransaction()) {
            throw new Exception('Using commit() when no transaction has started');
        }

        --$this->transactionDepth;

        if ($this->transactionDepth === 0) {
            return $this->driver->commit();
        }

        return false;
    }

    /**
     * Rollbacks queries since beginTransaction and resets transaction depth.
     *
     * @see beginTransaction()
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function rollBack()
    {
        // check if transaction is actually started
        if (!$this->inTransaction()) {
            throw new Exception('Using rollBack() when no transaction has started');
        }

        --$this->transactionDepth;

        if ($this->transactionDepth === 0) {
            return $this->driver->rollBack();
        }

        return false;
    }

    /**
     * Return last inserted ID value.
     *
     * Few Connection drivers need to receive Model to get ID because PDO doesn't support this method.
     *
     * @param \atk4\data\Model Optional data model from which to return last ID
     *
     * @return mixed
     */
    public function lastInsertID($model = null)
    {
        return $this->driver->lastInsertID();
    }
}
