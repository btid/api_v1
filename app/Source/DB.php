<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Pentagonal\DatabaseDBAL\Database;
use Pentagonal\DatabaseDBAL\StringSanitizerTrait;

/**
 * Class DB
 * @package ArrayIterator\Api\Crypt\Source
 *
 * @link https://github.com/pentagonal/Database-DBAL
 *
 * @mixin Database
 *
 * @method static void beginTransaction()
 *
 * @method static void         close()
 * @method static void         commit()
 * @method static bool         connect()
 * @method static mixed        convertToDatabaseValue(mixed $value, $type)
 * @method static mixed        convertToPHPValue(mixed $value, $type)
 * @method static QueryBuilder createQueryBuilder()
 * @method static void         createSavepoint(string $savepoint)
 *
 * @method static int delete(string $tableExpression, array $identifier, array $types = [])
 *
 * @method static int       errorCode()
 * @method static array     errorInfo()
 * @method static void      exec(string $statement)
 * @method static Statement executeQuery(string $q, array $params=[], array $types = [], QueryCacheProfile $qcp = null)
 * @method static ResultStatement executeCacheQuery(string $query, $params, $types, QueryCacheProfile $qcp)
 * @method static int       executeUpdate(string $query, array $params = [], array $types = [])
 *
 * @method static int  insert(string $tableExpression, array $data, array $types = [])
 * @method static bool isAutoCommit()
 * @method static bool isConnected()
 * @method static bool isRollbackOnly()
 * @method static bool isTransactionActive()
 *
 * @method static array fetchAssoc(string $statement, array $params = [], array $types = [])
 * @method static array fetchArray(string $statement, array $params = [], array $types = [])
 * @method static array fetchColumn(string $statement, array $params = [], array $types = [])
 * @method static array fetchAll(string $sql, array $params = array(), $types = array())
 *
 * @method static Configuration         getConfiguration()
 * @method static Driver                getDriver()
 * @method static string                getDatabase()
 * @method static AbstractPlatform      getDatabasePlatform()
 * @method static EventManager          getEventManager()
 * @method static ExpressionBuilder     getExpressionBuilder()
 * @method static string                getHost()
 * @method static array                 getParams()
 * @method static string|null           getPassword()
 * @method static mixed                 getPort()
 * @method static AbstractSchemaManager getSchemaManager()
 * @method static int                   getTransactionIsolation()
 * @method static int                   getTransactionNestingLevel()
 * @method static string|null           getUsername()
 * @method static Connection            getWrappedConnection()
 *
 * @method static string lastInsertId(string|null $seqName)
 *
 * @method static bool      ping()
 * @method static Statement prepare(string $statement)
 * @method static array     project(string $query, array $params, \Closure $function)
 *
 * @method static void      releaseSavepoint(string $savePoint)
 * @method static array     resolveParams(array $params, array $types)
 * @method static bool|void rollBack()
 * @method static void      rollbackSavepoint(string $savePoint)
 *
 * @method static void setAutoCommit(bool $autoCommit)
 * @method static void setFetchMode(int $fetchMode)
 * @method static void setNestTransactionsWithSavePoints(bool $nestTransactionsWithSavePoints)
 * @method static void setRollbackOnly()
 * @method static int  setTransactionIsolation(int $level)
 *
 * @method static void transactional(\Closure $func)
 *
 * @method static int update(string $tableExpression, array $data, array $identifier, array $types = [])
 *
 * @method static string    quote(mixed $input, int $type = \PDO::PARAM_STR)
 * @method static string    quoteIdentifier(string $str)
 *
 * @uses \PDO::ATTR_DEFAULT_FETCH_MODE for (19)
 * @method static Statement query(string $sql, int $mode = 19, mixed $additionalArg = null, array $constructorArgs = [])
 *
 * @method static ForeignKeyConstraint[] listTableForeignKeys(string $tableName)
 * @method static ForeignKeyConstraint[] getListTableForeignKey(string $tableName)
 * @method static ForeignKeyConstraint[] getListTableForeignKeys(string $tableName)
 * @method static ForeignKeyConstraint[] getTableForeignKeys(string $tableName)
 * @method static View[] getListView()
 * @method static View[] getListViews()
 * @method static View[] listViews()
 * @method static array|null|string prefixTables($table, bool $use_identifier = false)
 * @method static array|null|string quotes($quoteStr, $type = \PDO::PARAM_STR)
 * @method static array|null|string quoteIdentifiers($quoteStr)
 * @method static mixed trimTableSelector($table)
 * @method static array getConnectionParams()
 * @method static array getUserParams()
 * @method static string getQuoteIdentifier()
 * @method static string getTablePrefix()
 * @method static Connection getConnection()
 * @method static string normalizeDatabaseDriver(string $driverName)
 * @method static string sanitizeSelectedAvailableDriver(string $driver)
 * @method static Table listTableDetails(string $tableName)
 * @method static Table getListTableDetail(string $tableName)
 * @method static Table getListTableDetails(string $tableName)
 * @method static Table getTableDetails(string $tableName)
 * @method static Table[] listTables()
 * @method static Table[] getListTable()
 * @method static Table[] getListTables()
 * @method static array|string[] listTableNames()
 * @method static array|string[] getListTableName()
 * @method static array|string[] getListTableNames()
 * @method static bool tablesExist()
 * @method static Index[] listTableIndexes(string $tableName)
 * @method static Index[] getListTableIndex(string $tableName)
 * @method static Index[] getTableIndexes(string $tableName)
 * @method static Index[] getListTableIndexes(string $tableName)
 * @method static Column[] listTableColumns(string $tableName)
 * @method static Column[] getListTableColumns(string $tableName)
 * @method static Column[] getListTableColumn(string $tableName)
 * @method static Column[] getTableColumns(string $tableName)
 * @method static Sequence[] listSequences()
 * @method static Sequence[] getListSequence()
 * @method static Sequence[] getListSequences()
 * @method static Sequence[] getSequences()
 * @method static array|string[] listNamespaceNames()
 * @method static array|string[] getNamespaceNames()
 * @method static array|string[] getListNamespaceName()
 * @method static array|string[] getListNamespaceNames()
 * @method static array|string[] listDatabases()
 * @method static array|string[] getDatabases()
 * @method static array|string[] getListDatabase()
 * @method static array|string[] getListDatabases()
 * @method static Statement executePrepare(string $query, array $bind = [])
 * @method static Statement queryBind(string $sql, $statement = null)
 * @method static mixed compileBindsQuestionMark(string $sql, $binds = null)
 * @method static mixed prefix($tables, bool $use_identifier = false)
 *
 * @method static string sanitizeInvalidUTF8(string $string)
 * @method static mixed maybeUnSerialize($original)
 * @method static mixed maybeSerialize($data, bool $doubleSerialize = false)
 */
class DB
{
    /**
     * @see Database::TRANSACTION_READ_UNCOMMITTED
     */
    const TRANSACTION_READ_UNCOMMITTED = Database::TRANSACTION_READ_UNCOMMITTED;

    /**
     * @see Database::TRANSACTION_READ_COMMITTED
     */
    const TRANSACTION_READ_COMMITTED   = Database::TRANSACTION_READ_COMMITTED;

    /**
     * @see Database::TRANSACTION_REPEATABLE_READ
     */
    const TRANSACTION_REPEATABLE_READ = Database::TRANSACTION_REPEATABLE_READ;

    /**
     * @see Database::TRANSACTION_SERIALIZABLE
     */
    const TRANSACTION_SERIALIZABLE = Database::TRANSACTION_SERIALIZABLE;

    /**
     * @see Database::PARAM_INT_ARRAY
     */
    const PARAM_INT_ARRAY = Database::PARAM_INT_ARRAY;

    /**
     * @see Database::PARAM_STR_ARRAY
     */
    const PARAM_STR_ARRAY = Database::PARAM_STR_ARRAY;

    /**
     * @see Database::ARRAY_PARAM_OFFSET
     */
    const ARRAY_PARAM_OFFSET = Database::ARRAY_PARAM_OFFSET;

    /**
     * @var string
     */
    const
        DRIVER_MYSQL   = Database::DRIVER_MYSQL,
        DRIVER_PGSQL   = Database::DRIVER_PGSQL,
        DRIVER_SQLITE  = Database::DRIVER_SQLITE,
        DRIVER_DRIZZLE = Database::DRIVER_DRIZZLE,
        DRIVER_DB2     = Database::DRIVER_DB2,
        DRIVER_SQLSRV  = Database::DRIVER_SQLSRV,
        DRIVER_OCI8    = Database::DRIVER_OCI8;

    /**
     * @var int default timeout
     */
    const DEFAULT_TIMEOUT = Database::DEFAULT_TIMEOUT;

    /**
     * @var DB[]
     */
    private static $dbInstance = [];

    /**
     * @var string
     */
    protected static $currentDB = 'default';

    /**
     * @var Database
     */
    protected $database;

    /**
     * DB constructor.
     *
     * @param Database $database
     * @param string $selector
     */
    public function __construct(
        Database $database = null,
        string $selector = 'default'
    ) {
        if ($database) {
            $this->database = $database;
        } elseif (isset(self::$dbInstance[$selector])) {
            $this->database =& self::$dbInstance[$selector]->database;
        } else {
            throw new \RuntimeException(
                sprintf(
                    'Database for application %s has not declared yet.',
                    $selector
                )
            );
        }

        self::$dbInstance[$selector] =& $this;
        self::$currentDB             = $selector;
    }

    /**
     * @return Database
     */
    public function getDatabaseObject() : Database
    {
        return $this->database;
    }

    /**
     * @param Database $database
     * @param string $selector
     *
     * @return DB
     */
    public static function add(
        Database $database,
        string $selector
    ) : DB {
        $db = new static($database, $selector);
        return $db;
    }

    /**
     * @param Database|null $db
     * @param string $selector
     *
     * @return DB|Database|static
     */
    public static function &instance(
        Database $db = null,
        string $selector = 'default'
    ) {
        if ($db) {
            $db = self::add($db, $selector);
        } else {
            if (isset(self::$dbInstance[$selector])) {
                $db =& self::$dbInstance[$selector];
            } else {
                $db = new static();
            }
        }

        self::$currentDB = $selector;
        return $db;
    }


    /**
     * @param string $selector
     *
     * @return bool
     */
    public static function exist(string $selector) : bool
    {
        return isset(self::$dbInstance[$selector]);
    }

    /**
     * @param string $selector
     *
     * @return DB
     */
    public static function choose(string $selector)
    {
        if (isset(self::$dbInstance[$selector])) {
            return self::$dbInstance[$selector];
        }
        throw new \RuntimeException(
            sprintf(
                'Database for application %s has not declared yet.',
                $selector
            )
        );
    }

    /**
     * @return DB
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function createFromLastParams() : DB
    {
        $instance = self::instance();
        $db = $instance->database->createFromLastParams();
        $instance->database =& $db;
        return $instance;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return self::instance()->__call($name, $arguments);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @uses Database::__call()
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array(
            [$this->getDatabaseObject(), $name],
            $arguments
        );
    }
}
