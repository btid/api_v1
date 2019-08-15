#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Bin;

use ArrayIterator\Api\Crypt\Source\Cli\Command\CheckCommand;
use ArrayIterator\Api\Crypt\Source\Cli\Command\InstallCommand;
use ArrayIterator\Api\Crypt\Source\Cli\Command\UpdateCommand;
use ArrayIterator\Api\Crypt\Source\ExtensionLoader;
use ArrayIterator\Api\Crypt\Source\Generator\SchemaMerger;
use ArrayIterator\Api\Crypt\Source\Generator\SchemaProvider;
use ArrayIterator\Api\Crypt\Source\MigrationSupportInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Pentagonal\DatabaseDBAL\Database;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

if (php_sapi_name() !== 'cli') {
    echo "Application only for Console command.";
    exit(255);
}

// require Configurator
require_once dirname(__DIR__) . '/vendor/autoload.php';
$container = require_once dirname(__DIR__) . '/app/Components/Container.php';

try {
    /**
     * @var Database $database
     * @var ExtensionLoader $loader
     */
    $database = $container['db']->getDatabaseObject();
    $loader = $container['extension'];
    $configuration = new Configuration($database->getConnection());
    $helper = new ConfigurationHelper(
        $configuration->getConnection(),
        $configuration
    );

    $tableMigration = $database->prefix('doctrine_migrations');
    $baseNameSpace = 'ArrayIterator\\Api\\Crypt\\Migrations';

    $migrationDir = dirname(__DIR__) . '/app/Migrations/';
    $configuration->setMigrationsDirectory($migrationDir);
    $configuration->setName('Doctrine Database for Migration');
    $configuration->setMigrationsTableName($tableMigration);
    $configuration->setMigrationsNamespace($baseNameSpace);
    $configuration->setMigrationsExecutedAtColumnName('executed_at');
    $configuration->setAllOrNothing(true);
    $configuration->setCheckDatabasePlatform(false);

    $helperSet = new HelperSet();
    $helperSet->set($helper, $helper->getName());
    $helperSet->set(new QuestionHelper(), 'question');
    $helperSet->set(new ConnectionHelper($database->getConnection()), 'db');
    $console = ConsoleRunner::createApplication($helperSet);
    $console->setCatchExceptions(true);

    $schemaProvider = new SchemaMerger($configuration);
    foreach ($loader as $ext) {
        $ext = $loader->load($ext);
        if ($ext instanceof MigrationSupportInterface) {
            if (is_dir($ext->getMigrationPath())) {
                $configuration->registerMigrationsFromDirectory($ext->getMigrationPath());
            }
            foreach ($ext->getDatabaseSchema() as $scheme) {
                if (is_object($scheme) && $scheme instanceof Schema) {
                    $schemaProvider->addSchema($scheme);
                    continue;
                }
                if (is_string($scheme) && @class_exists($scheme) && is_subclass_of($scheme, SchemaProvider::class)) {
                    $schemaProvider->addSchema(new $scheme($database));
                }
            }
        }
    }

    $console->add(new CheckCommand($schemaProvider, $database));
    $console->add(new UpdateCommand($schemaProvider, $database));
    $console->add(new InstallCommand($schemaProvider, $database));
    $console->add(new StatusCommand());
    // diff
    $console->add(new DiffCommand($schemaProvider));
    $console->run();
} catch (\Exception $e) {
    echo $e;
    exit($e->getCode() ?: 255);
}
