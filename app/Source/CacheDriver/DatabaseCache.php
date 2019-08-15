<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\CacheDriver;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class DatabaseCache
 * @package ArrayIterator\Api\Crypt\Source\CacheDriver
 */
class DatabaseCache extends CacheProvider
{
    /**
     * The ID field will store the cache key.
     */
    const ID_FIELD = 'k';

    /**
     * The data field will store the serialized PHP value.
     */
    const DATA_FIELD = 'd';

    /**
     * The expiration field will store a date value indicating when the
     * cache entry should expire.
     */
    const EXPIRATION_FIELD = 'e';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $table;

    /**
     * Database constructor.
     *
     * @param Connection $connection
     * @param $table
     *
     * @throws DBALException
     * @throws \Throwable
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;
        $this->table      = (string) $table;

        list($id, $data, $exp) = $this->getFields();

        $schema = new Schema();
        $table = $schema->createTable($table);
        $table->addColumn($id, Type::TEXT)->setNotnull(true);
        $table->addColumn($data, Type::TEXT)->setLength(1024 *1024 * 1024);
        $table->addColumn($exp, Type::INTEGER);
        $table->setPrimaryKey([$id]);
        $compare = Comparator::compareSchemas($connection->getSchemaManager()->createSchema(), $schema);
        $sql = $compare->toSql($connection->getDatabasePlatform());
        if (!empty($sql) && !empty(($sql = preg_filter('/^(\s*(?:CREATE|ALTER)\s+.+)$/smi', '$1', $sql)))) {
            try {
                $connection->beginTransaction();
                foreach ($sql as $key => $v) {
                    $connection->exec($v);
                }
                $connection->commit();
            } catch (DBALException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $item = $this->findById($id);

        if (!$item) {
            return false;
        }
        if (is_resource($item[self::DATA_FIELD])) {
            $item[self::DATA_FIELD] = stream_get_contents($item[self::DATA_FIELD]);
        }

        if (!is_string($item[self::DATA_FIELD])
            || trim($item[self::DATA_FIELD]) === ''
        ) {
            return false;
        }

        $item = strpos($item[self::DATA_FIELD], ';') !== false
                || strpos($item[self::DATA_FIELD], '{')
            ? $item[self::DATA_FIELD]
            : base64_decode($item[self::DATA_FIELD]);
        if ($item !== 'b:0;' && ($item = @unserialize($item)) === false) {
            $this->doDelete($id);
            return false;
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return null !== $this->findById($id, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {

        list($selector, $dataSelector, $exp) = $this->getFields();
        $exp = $exp === null || !is_numeric($lifeTime) ? 0 : $exp;
        $qb = $this->connection->createQueryBuilder();
        $stmt = $qb
            ->select($selector)
            ->from($this->table)
            ->where(sprintf('%s = :id', $selector))
            ->setParameter(':id', $id)
            ->execute();
        $di  = $stmt
            ? $stmt->fetch(\PDO::FETCH_ASSOC)
            : null;
        $qb = $this->connection->createQueryBuilder();
        if (!empty($di[$selector])) {
            $stmt->closeCursor();
            $qb->update($this->table)
               ->set($dataSelector, ':data')
               ->set($exp, ':expire')
               ->where(sprintf('%s=:id', $selector));
        } else {
            $qb->insert($this->table)
               ->setValue($selector, ':id')
               ->setValue($dataSelector, ':data')
               ->setValue($exp, ':expire');
        }

        $qb->setParameters([
            ':id' => $id,
            ':data' => base64_encode(serialize($data)),
            ':expire' => $lifeTime > 0 ? $this->currentTime() + $lifeTime : 0
        ]);

        return (bool) $qb->execute();
    }

    /**
     * @return false|int
     */
    protected function currentTime()
    {
        return strtotime(gmdate('Y-m-d H:i:s'));
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        list($idField) = $this->getFields();
        $qb = $this->connection->createQueryBuilder();
        $qb->delete(
            $this->table
        )->where(sprintf('%s=:id', $idField))
           ->setParameter(':id', $id);
        return (bool) $qb->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return (bool) $this
            ->connection
            ->createQueryBuilder()
            ->delete($this->table)
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        // no-op.
    }

    /**
     * Find a single row by ID.
     *
     * @param mixed $id
     * @param bool $includeData
     *
     * @return array|null
     */
    private function findById($id, bool $includeData = true)
    {
        $fields = $this->getFields();
        list($idField) = $fields;
        if (!$includeData) {
            $key = array_search(static::DATA_FIELD, $fields);
            unset($fields[$key]);
        }
        $stmt = $this
            ->connection
            ->createQueryBuilder()
            ->select(implode(', ', $fields))
            ->from($this->table)
            ->where(sprintf('%s=:id', $idField))
            ->setParameter(':id', $id)
            ->setMaxResults('1');
        $stmt = $stmt->execute();
        if (!$stmt || !($item = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            if ($stmt) {
                $stmt->closeCursor();
            }
            return null;
        }
        if (empty($item)) {
            return null;
        }
        if ($this->isExpired($item)) {
            $this->doDelete($id);

            return null;
        }

        return $item;
    }

    /**
     * Gets an array of the fields in our table.
     *
     * @return array
     */
    private function getFields() : array
    {
        return [static::ID_FIELD, static::DATA_FIELD, static::EXPIRATION_FIELD];
    }

    /**
     * Check if the item is expired.
     *
     * @param array $item
     *
     * @return bool
     */
    private function isExpired(array $item) : bool
    {
        return isset($item[static::EXPIRATION_FIELD]) &&
               (
                   $item[self::EXPIRATION_FIELD] !== null
                   && (
                       ! is_numeric($item[self::EXPIRATION_FIELD])
                       || (
                           abs($item[static::EXPIRATION_FIELD]) !== 0
                           && abs($item[self::EXPIRATION_FIELD]) <= $this->currentTime()
                       )
                   )
               );
    }
}
