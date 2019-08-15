<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\App\Extensions\Market\Schema;

use ArrayIterator\Api\Crypt\Source\Generator\SchemaProvider;
use Doctrine\DBAL\Types\Type;

/**
 * Class IndodaxSchema
 * @package ArrayIterator\Api\Crypt\App\Extensions\Market\Schema
 */
class IndodaxSchema extends SchemaProvider
{
    public $tableName = 'indodax';
    public $tableSchema = [
        'id' => [
            self::TYPE => Type::BIGINT,
            self::NOT_NULL => true,
            self::LENGTH => 50,
            self::AUTO_INCREMENT => true
        ]
    ];
    protected $properties = [
        self::PRIMARY_KEY => [
            'id'
        ]
    ];
}
