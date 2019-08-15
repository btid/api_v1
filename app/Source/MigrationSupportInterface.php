<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

use Doctrine\DBAL\Schema\Schema;

/**
 * Interface MigrationSupportInterface
 * @package ArrayIterator\Api\Crypt\Source
 */
interface MigrationSupportInterface
{
    /**
     * @return string
     */
    public function getMigrationPath() : string;

    /**
     * @return Schema[]
     */
    public function getDatabaseSchema() : array;
}
