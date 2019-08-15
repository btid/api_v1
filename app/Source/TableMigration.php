<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

use Doctrine\DBAL\Schema\Schema;

abstract class TableMigration
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function manageSchema(Schema &$schema)
    {
        // @todo schema
    }
}
