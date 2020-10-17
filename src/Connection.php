<?php

declare(strict_types=1);

namespace atk4\dsql;

use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Class for establishing and maintaining connection with your database.
 */
abstract class Connection
{
    use \atk4\core\DiContainerTrait;

    /** @var string Query classname */
    protected $query_class = Query::class;

    /** @var string Expression classname */
    protected $expression_class = Expression::class;

    /** @var DbalConnection */
    protected $connection;

    /**
     * Stores the driverSchema => connectionClass array for resolving.
     *
     * @var array
     */
    protected static $connectionClassRegistry = [
        'sqlite' => Sqlite\Connection::class,
        'mysql' => Mysql\Connection::class,
        'pgsql' => Postgresql\Connection::class,
        'oci' => Oracle\Connection::class,
        'sqlsrv' => Mssql\Connection::class,
    ];

    /**
     * Specifying $properties to constructors will override default
     * property values of this class.
     *
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        if (!is_array($properties)) {
            throw (new Exception('Invalid properties for "new Connection()". Did you mean to call Connection::connect()?'))
                ->addMoreInfo('properties', $properties);
        }

        $this->setDefaults($properties);
    }

    /**
     * Normalize DSN connection string.
     *
     * Returns normalized DSN as array ['dsn', 'user', 'pass', 'driverSchema', 'rest'].
     *
     * @param array|string $dsn  DSN string
     * @param string       $user Optional username, this takes precedence over dsn string
     * @param string       $pass Optional password, this takes precedence over dsn string
     *
     * @return array
     */
    public static function normalizeDsn($dsn, $user = null, $pass = null)
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
                throw (new Exception('Your DSN format is invalid. Must be in "driverSchema:host;options" format'))
                    ->addMoreInfo('dsn', $dsn);
            }
            [$driverSchema, $rest] = explode(':', $dsn, 2);
            $driverSchema = strtolower($driverSchema);
        } else {
            // currently impossible to be like this, but we don't want ugly exceptions here
            $driverSchema = null;
            $rest = null;
        }

        return ['dsn' => $dsn, 'user' => $user ?: null, 'pass' => $pass ?: null, 'driverSchema' => $driverSchema, 'rest' => $rest];
    }

    /**
     * Connect to database and return connection class.
     *
     * @param string|\PDO|DbalConnection $dsn
     * @param string|null                $user
     * @param string|null                $password
     * @param array                      $args
     *
     * @return Connection
     */
    public static function connect($dsn, $user = null, $password = null, $args = [])
    {
        // If it's already PDO or DbalConnection object, then we simply use it
        if ($dsn instanceof \PDO) {
            $connectionClass = self::resolveConnectionClass(
                $dsn->getAttribute(\PDO::ATTR_DRIVER_NAME)
            );

            return new $connectionClass(array_merge([
                'connection' => $connectionClass::connectDbalConnection(['pdo' => $dsn]),
            ], $args));
        } elseif ($dsn instanceof DbalConnection) {
            $connectionClass = self::resolveConnectionClass(
                $dsn->getWrappedConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME)
            );

            return new $connectionClass(array_merge([
                'connection' => $dsn,
            ], $args));
        }

        $dsn = static::normalizeDsn($dsn, $user, $password);
        $connectionClass = self::resolveConnectionClass($dsn['driverSchema']);

        return new $connectionClass(array_merge([
            'connection' => $connectionClass::connectDbalConnection($dsn),
        ], $args));
    }

    /**
     * Adds connection class to the registry for resolving in Connection::resolve method.
     *
     * Can be used as:
     *
     * Connection::registerConnection(MySQL\Connection::class, 'mysql'), or
     * MySQL\Connection::registerConnectionClass()
     *
     * CustomDriver\Connection must be descendant of Connection class.
     *
     * @param string $connectionClass
     * @param string $driverSchema
     */
    public static function registerConnectionClass($connectionClass = null, $driverSchema = null)
    {
        if ($connectionClass === null) {
            $connectionClass = static::class;
        }

        if ($driverSchema === null) {
            /** @var static $c */
            $c = (new \ReflectionClass($connectionClass))->newInstanceWithoutConstructor();
            $driverSchema = $c->getDatabasePlatform()->getName();
        }

        self::$connectionClassRegistry[$driverSchema] = $connectionClass;
    }

    /**
     * Resolves the connection class to use based on driver type.
     *
     * @param string $driverSchema
     *
     * @return string
     */
    public static function resolveConnectionClass($driverSchema)
    {
        return self::$connectionClassRegistry[$driverSchema] ?? static::class;
    }

    /**
     * Establishes connection based on a $dsn.
     *
     * @return DbalConnection
     */
    protected static function connectDbalConnection(array $dsn)
    {
        if (isset($dsn['pdo'])) {
            $pdo = $dsn['pdo'];
        } else {
            $pdo = new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass']);
        }

        $connectionParams = ['pdo' => $pdo];

        return DriverManager::getConnection($connectionParams);
    }

    /**
     * Returns new Query object with connection already set.
     *
     * @param string|array $properties
     */
    public function dsql($properties = []): Query
    {
        $c = $this->query_class;
        $q = new $c($properties);
        $q->connection = $this;

        return $q;
    }

    /**
     * Returns Expression object with connection already set.
     *
     * @param string|array $properties
     * @param array        $arguments
     */
    public function expr($properties = [], $arguments = null): Expression
    {
        $c = $this->expression_class;
        $e = new $c($properties, $arguments);
        $e->connection = $this;

        return $e;
    }

    /**
     * @return DbalConnection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * Execute Expression by using this connection.
     *
     * @return \PDOStatement
     */
    public function execute(Expression $expr)
    {
        if ($this->connection === null) {
            throw new Exception('Queries cannot be executed through this connection');
        }

        return $expr->execute($this->connection);
    }

    /**
     * Atomic executes operations within one begin/end transaction, so if
     * the code inside callback will fail, then all of the transaction
     * will be also rolled back.
     */
    public function atomic(\Closure $fx, ...$args)
    {
        $this->beginTransaction();
        try {
            $res = $fx(...$args);
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
     */
    public function beginTransaction(): void
    {
        try {
            $this->connection->beginTransaction();
        } catch (\Doctrine\DBAL\ConnectionException $e) {
            throw new Exception('Begin transaction failed', 0, $e);
        }
    }

    /**
     * Will return true if currently running inside a transaction.
     * This is useful if you are logging anything into a database. If you are
     * inside a transaction, don't log or it may be rolled back.
     * Perhaps use a hook for this?
     */
    public function inTransaction(): bool
    {
        return $this->connection->isTransactionActive();
    }

    /**
     * Commits transaction.
     *
     * Each occurrence of beginTransaction() must be matched with commit().
     * Only when same amount of commits are executed, the actual commit will be
     * issued to the database.
     */
    public function commit(): void
    {
        try {
            $this->connection->commit();
        } catch (\Doctrine\DBAL\ConnectionException $e) {
            throw new Exception('Commit failed', 0, $e);
        }
    }

    /**
     * Rollbacks queries since beginTransaction and resets transaction depth.
     */
    public function rollBack()
    {
        try {
            $this->connection->rollBack();
        } catch (\Doctrine\DBAL\ConnectionException $e) {
            throw new Exception('Rollback failed', 0, $e);
        }
    }

    /**
     * Return last inserted ID value.
     *
     * Drivers like PostgreSQL need to receive sequence name to get ID because PDO doesn't support this method.
     */
    public function lastInsertId(string $sequence = null): string
    {
        return $this->connection()->lastInsertId($sequence);
    }

    abstract public function getDatabasePlatform(): AbstractPlatform;
}
