<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Generator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use Pentagonal\DatabaseDBAL\Database;

/**
 * Class SchemaMerger
 * @package ArrayIterator\Api\Crypt\Source\Generator
 */
final class SchemaMerger implements SchemaProviderInterface
{
    /**
     * \Doctrine\DBAL\Platforms\AbstractPlatform::getListSequencesSQL
     * @var SchemaProvider[]
     */
    protected $schemaProviders = [];

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;
    protected $configuration;
    /**
     * SchemaMerger constructor.
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->connection = $configuration->getConnection();
    }

    /**
     * @param SchemaProvider $schemaProvider
     */
    public function addSchema(SchemaProvider $schemaProvider)
    {
        $this->schemaProviders[] = $schemaProvider;
    }

    /**
     * @param SchemaProvider[]|string[] $schemas
     * @param Database|null $database
     * @return SchemaProvider[]
     */
    protected function sanitySchema(array $schemas, Database $database = null) : array
    {
        foreach ($schemas as $key => $schema) {
            if (is_string($schema)) {
                if (!class_exists($schema) || ! is_subclass_of($schema, SchemaProvider::class)) {
                    throw new \RuntimeException(
                        sprintf(
                            '%s is not instance of %s',
                            $schema,
                            SchemaProvider::class
                        )
                    );
                }

                $schema = $database ? new $schema($database) : new $schema();
            }
            if (!is_object($schema)) {
                throw new \RuntimeException(
                    sprintf(
                        'Schema must be as an object instance of %s, %s given',
                        SchemaProvider::class,
                        gettype($schema)
                    )
                );
            }
            if (!$schema instanceof SchemaProvider) {
                throw new \RuntimeException(
                    sprintf(
                        'Schema object %s is not instance of %s',
                        get_class($schema),
                        SchemaProvider::class
                    )
                );
            }
            $schemas[$key] = $schema;
        }

        return $schemas;
    }

    /**
     * @return Schema
     */
    public function createSchema() : Schema
    {
        if ($this->connection) {
            return $this->buildSchema($this->connection);
        }

        return $this->buildSchema();
    }

    /**
     * @param Connection|null $connection
     * @return Schema
     */
    public function buildSchema(Connection $connection = null)
    {
        $sequence = [];
        $namespace = [];
        try {
            $sequence = $connection->getSchemaManager()->listSequences();
        } catch (\Exception $e) {
            // pass
        }
        try {
            $namespace = $connection->getSchemaManager()->listNamespaceNames();
        } catch (\Exception $e) {
            // pass
        }
        $schema = $connection ? new Schema(
            [],
            $sequence,
            $connection->getSchemaManager()->createSchemaConfig(),
            $namespace
        ) : new Schema();

        foreach ($this->sanitySchema($this->schemaProviders) as $key => $schemaProvider) {
            if ($connection !== null && !$schemaProvider->getConnection()) {
                $schemaProvider->setConnection($this->connection);
            }

            $schemaProvider->buildSchema($schema);
        }

        return $schema;
    }

    /**
     * @param Database $database
     * @return Schema[]
     */
    public function getSeparateScheme(Database $database) : array
    {
        $dbSchema = clone $database->getSchemaManager()->createSchema();
        $currentSchema = clone $this->createSchema();
        $migrationName = $database->prefix($this->configuration->getMigrationsTableName());
        if ($dbSchema->hasTable($migrationName)
            && ! $currentSchema->hasTable($migrationName)
        ) {
            try {
                $tables = $currentSchema->getTables();
                $tables[$migrationName] = $dbSchema->getTable($migrationName);
                $currentSchema = new Schema($tables);
            } catch (\Exception $e) {
                //
            }
        }

        return [
            $dbSchema,
            $currentSchema
        ];
    }

    /**
     * @param Database $database
     * @return SchemaDiff
     */
    public function getSchemaDiff(Database $database) : SchemaDiff
    {
        $schemes = $this->getSeparateScheme($database);
        $comparator = new Comparator();
        return $comparator->compare(
            $schemes[0],
            $schemes[1]
        );
    }
}
