<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Generator;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Pentagonal\DatabaseDBAL\Database;
use RuntimeException;

/**
 * Class SchemaProvider
 * @package ArrayIterator\Api\Crypt\Source\Generator
 */
abstract class SchemaProvider implements SchemaProviderInterface
{
    const DEFAULT_TYPE = Type::STRING;

    /**
     * Identified AS CURRENT_TIME*
     */
    const CURRENT_TIME = 'CURRENT';

    const DEFAULT_EMPTY_DATETIME = '1970-01-01 00:00:00';

    const TYPE = 'type';
    const NOT_NULL = 'notNull';
    const LENGTH = 'length';
    const DEFAULT = 'default';
    const AUTO_INCREMENT = 'autoIncrement';
    const COMMENT = 'comment';

    // add
    const UNIQUE = 'uniqueIndex';
    const INDEX = 'index';
    const PRIMARY_KEY = 'primaryKey';
    const CONSTRAINT = 'constraint';

    // params
    const OPTIONS = 'options';
    const COLUMNS = 'columnName';
    const FLAGS = 'flags';
    const INDEX_NAME = 'indexName';

    /**
     * @var string
     */
    protected $currentTimeOverride;

    /**
     * @var string
     */
    public $tableName;

    /**
     * @var array[]
     */
    protected $tableSchema = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var Database
     */
    protected $connection;

    /**
     * @var array
     */
    protected static $dateType = [
        Type::DATETIME             => 'getCurrentTimestampSQL',
        Type::DATETIMETZ           => 'getCurrentTimestampSQL',
        Type::DATETIME_IMMUTABLE   => 'getCurrentTimestampSQL',
        Type::DATETIMETZ_IMMUTABLE => 'getCurrentTimestampSQL',
    ];

    /**
     * @var array
     */
    protected static $timeType = [
        Type::TIME => 'getCurrentTimeSQL',
        Type::TIME_IMMUTABLE => 'getCurrentTimeSQL',
        Type::DATE => 'getCurrentDateSQL',
        Type::DATEINTERVAL => 'getCurrentDateSQL',
        Type::DATETIME => 'getCurrentTimestampSQL',
        Type::DATETIMETZ => 'getCurrentTimestampSQL',
        Type::DATETIME_IMMUTABLE => 'getCurrentTimestampSQL',
        Type::DATETIMETZ_IMMUTABLE => 'getCurrentTimestampSQL',
    ];

    /**
     * SchemaProvider constructor.
     * @param Database|null $database
     */
    public function __construct(Database $database = null)
    {
        $this->connection = $database;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return null|Database
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function getTableSchemaArray() : array
    {
        return $this->tableSchema;
        /**
         * $dataBase = $this->getDatabase();
         * $tableSchema = $this->tableSchema;
         * if ($dataBase
         * && $dataBase->getDatabasePlatform()->getName() === 'postgresql'
         * ) {
         * if ($dataBase->getDatabasePlatform()->getDateTimeFormatString() !== 'Y-m-d H:i:s.u') {
         * return $tableSchema;
         * }
         *
         * foreach ($tableSchema as $key => $v) {
         * if (isset($v[self::TYPE])
         * && is_string($v[self::TYPE])
         * && isset(self::$dateType[$v[self::TYPE]])
         * && isset($v[self::DEFAULT])
         * && $v[self::DEFAULT] == self::DEFAULT_EMPTY_DATETIME
         * ) {
         * $tableSchema[$key][self::DEFAULT] = '1970-01-01 00:00:00.000000';
         * if (isset($v[self::COMMENT]) && is_string($v[self::COMMENT])) {
         * $tableSchema[$key][self::COMMENT] = str_replace(
         * self::DEFAULT_EMPTY_DATETIME .' ',
         * '1970-01-01 00:00:00.000000 ',
         * $v[self::COMMENT]
         * );
         * }
         * }
         * }
         * }
         *
         * return $tableSchema;
         */
    }

    /**
     * @return string
     */
    public function getTableName() : string
    {
        if ($this->connection) {
            return $this->connection->prefix($this->tableName);
        }

        return $this->tableName;
    }

    /**
     * @param Schema $schema
     * @return Table
     */
    public function createTable(Schema $schema) : Table
    {
        return $schema->createTable($this->getTableName());
    }

    /**
     * @param string $type
     * @param $value
     * @return mixed
     */
    public function currentConversionPossible(string $type, $value)
    {
        if ($value !== self::CURRENT_TIME
            || ! isset(self::$timeType[$type])
            || !is_string(($method = self::$timeType[$type]))
        ) {
            return $value;
        }

        $platform = $this->getConnection()->getDatabasePlatform();
        if (!method_exists($platform, $method)) {
            return $value;
        }
        return $platform->$method();
    }

    /**
     * @param Schema $schema
     * @return Schema
     */
    public function buildSchema(Schema $schema) : Schema
    {
        $table = $this->createTable($schema);
        $tableScheme = $this->getTableSchemaArray();
        if (!empty($tableScheme)) {
            foreach ($tableScheme as $columnName => $definition) {
                if (!is_string($columnName)) {
                    throw new RuntimeException(
                        sprintf(
                            'Column name must be as a string %s given',
                            $columnName
                        )
                    );
                }
                if (!is_array($definition)) {
                    throw new RuntimeException(
                        sprintf(
                            'Definition for column %s must be as array %s given',
                            $columnName,
                            gettype($definition)
                        )
                    );
                }
                $type = isset($definition[self::TYPE])
                    ? $definition[self::TYPE]
                    : static::DEFAULT_TYPE;
                $column = $table->addColumn($columnName, $type);
                unset($definition[self::TYPE]);
                foreach ($definition as $method => $value) {
                    if (method_exists($column, "set{$method}")) {
                        $value = $this->currentConversionPossible($type, $value);
                        $column->{"set{$method}"}($value);
                    }
                }
            }
        }

        if (!empty($this->properties) && is_array($this->properties)) {
            foreach ($this->properties as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $realKey = $key;
                $key = strtolower($key);
                if ($key === 'unique') {
                    $key = self::UNIQUE;
                }
                if ($key === self::CONSTRAINT) {
                    $key = 'ForeignKeyConstraint';
                }

                if (method_exists($table, "add{$key}")) {
                    if (is_string($value)) {
                        $table->{"add{$key}"}([$value]);
                        continue;
                    }

                    // pass if is not an array
                    if (!is_array($value)) {
                        throw new RuntimeException(
                            sprintf(
                                '%s parameters must be as array',
                                $key
                            )
                        );
                    }
                    if (empty($value)) {
                        continue;
                    }
                    $args = [null, null, null, null];
                    if (array_key_exists(self::COLUMNS, $value)) {
                        $args[0] = $value[self::COLUMNS];
                        if (is_string($args[0])) {
                            $args[0] = [$args[0]];
                        }
                        unset($value[self::COLUMNS]);
                    } else {
                        foreach ($value as $k => $v) {
                            if (is_array($v)) {
                                unset($value[$k]);
                                $args[0] = $v;
                                break;
                            }
                        }
                    }

                    if (array_key_exists(self::INDEX_NAME, $value)) {
                        $args[1] = $value[self::INDEX_NAME];
                        unset($value[self::INDEX_NAME]);
                    } else {
                        foreach ($value as $k => $v) {
                            if (is_string($v)) {
                                unset($value[$k]);
                                $args[1] = $v;
                                break;
                            }
                        }
                    }
                    if (array_key_exists(self::FLAGS, $value)) {
                        $args[2] = $value[self::FLAGS];
                        unset($value[self::FLAGS]);
                    } else {
                        foreach ($value as $k => $v) {
                            if (is_array($v) && !isset($existingKey[$k])) {
                                unset($value[$k]);
                                $args[2] = $v;
                                break;
                            }
                        }
                    }

                    if (array_key_exists(self::OPTIONS, $value)) {
                        $args[3] = $value[self::OPTIONS];
                        unset($value[self::OPTIONS]);
                    } else {
                        foreach ($value as $k => $v) {
                            if (is_array($v)) {
                                $args[3] = $v;
                                break;
                            }
                        }
                    }

                    if (!is_array($args[0])) {
                        throw new RuntimeException(
                            sprintf(
                                '%s has no columns definition',
                                ucfirst($realKey)
                            )
                        );
                    }

                    $hasUnset = false;
                    foreach (array_reverse($args) as $k => $v) {
                        if ($hasUnset && $v !== null) {
                            break;
                        }
                        if ($v === null) {
                            $hasUnset = true;
                            array_pop($args);
                        }
                    }
                    call_user_func_array([$table, "add{$key}"], $args);
                } elseif ($key === 'primarykey') {
                    if (is_string($value)) {
                        $table->setPrimaryKey([$value]);
                        continue;
                    }

                    // pass if is not an array
                    if (!is_array($value)) {
                        throw new RuntimeException(
                            sprintf(
                                '%s parameters must be as array',
                                $key
                            )
                        );
                    }

                    $args = [];
                    $containsArray = false;
                    foreach ($value as $val) {
                        if (is_array($val)) {
                            $containsArray = true;
                            $args[0] = $val;
                            continue;
                        }
                        if (is_string($val)) {
                            $args[1] = $val;
                            continue;
                        }
                    }
                    if (!$containsArray) {
                        $table->setPrimaryKey($value);
                    } else {
                        call_user_func_array([$table, 'setPrimaryKey'], $args);
                    }
                } elseif (method_exists($table, "set{$key}")) {
                    if (!is_array($value)) {
                        $table->{"set{$key}"}($value);
                        continue;
                    }

                    call_user_func_array([$table, "set{$key}"], $value);
                }
            }
        }

        return $schema;
    }

    /**
     * @return Schema
     */
    public function createSchema() : Schema
    {
        return $this->buildSchema(new Schema());
    }

    /**
     * @param AbstractPlatform $platform
     * @return array
     */
    public function toSqlArrayFromPlatform(AbstractPlatform $platform)
    {
        $object = clone $this;
        $schema = $object->createSchema();
        return $schema->toSql($platform);
    }

    /**
     * @param Connection $connection
     * @return array
     * @throws DBALException
     */
    public function toSqlArrayFromConnection(Connection $connection)
    {
        $object = clone $this;
        $object->setConnection($connection);
        return $this->toSqlArrayFromPlatform($connection->getDatabasePlatform());
    }
}
