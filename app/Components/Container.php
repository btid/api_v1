<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Components;

use Apatis\Config\Config;
use Apatis\Config\ConfigInterface;
use Apatis\Config\Factory;
use ArrayIterator\Api\Crypt\Source\Cache;
use ArrayIterator\Api\Crypt\Source\DB;
use ArrayIterator\Api\Crypt\Source\ExtensionLoader;
use ArrayIterator\Api\Crypt\Source\Handler\Error;
use ArrayIterator\Api\Crypt\Source\Handler\Expired;
use ArrayIterator\Api\Crypt\Source\Handler\Forbidden;
use ArrayIterator\Api\Crypt\Source\Handler\NotAllowed;
use ArrayIterator\Api\Crypt\Source\Handler\NotFound;
use ArrayIterator\Api\Crypt\Source\Handler\PhpError;
use ArrayIterator\Api\Crypt\Source\Handler\Unauthorized;
use Exception;
use Pentagonal\DatabaseDBAL\Database;
use Psr\Container\ContainerInterface;
use Slim\Container;
use Slim\Handlers\AbstractError;
use Slim\Handlers\AbstractHandler;

return new Container([
    'settings' => [
        'displayErrorDetails' => true,
    ],
    // Set Custom Handler to server as JSON Object
    'notFoundHandler' => function () : AbstractHandler {
        return new NotFound();
    },
    'notAllowedHandler' => function () : AbstractHandler {
        return new NotAllowed();
    },
    'unauthorizedHandler' => function () : AbstractHandler {
        return new Unauthorized();
    },
    'forbiddenHandler' => function () : AbstractHandler {
        return new Forbidden();
    },
    'expiredHandler' => function () : AbstractHandler {
        return new Expired();
    },
    'errorHandler' => function (ContainerInterface $container) : AbstractError {
        return new Error($container->get('settings')['displayErrorDetails']);
    },
    'phpErrorHandler' => function (ContainerInterface $container) : AbstractError {
        return new PhpError($container->get('settings')['displayErrorDetails']);
    },
    'config' => function () : ConfigInterface {
        $default = __DIR__ .'/../../config.yml';
        $cli = __DIR__ .'/../../config-cli.yml';
        $cliOverride = __DIR__ .'/../../.config-cli.yml';
        try {
            $config = is_file($default) ? Factory::fromFile($default) : new Config();
            if (file_exists(dirname($default).'/.config.yml')) {
                $config->merge(Factory::fromFile(dirname($default).'/.config.yml'));
            }
        } catch (Exception $e) {
            $config = $config ?? new Config();
        }
        try {
            if (file_exists($cli)) {
                $config->merge(Factory::fromFile($cli));
            }
        } catch (Exception $e) {
            // pass
        }
        try {
            if (file_exists($cli)) {
                $config->merge(Factory::fromFile($cliOverride));
            }
        } catch (Exception $e) {
            // pass
        }
        /** // @todo Sentry
        $dsn = null;
        $sentryConfig = $config['sentry'];
        if ($sentryConfig instanceof ConfigAdapterInterface) {
            $dsn = $sentryConfig['dsn'];
            if (!is_string($dsn)
                || !preg_match(
                    '/^(https?|[a-z]{2,10}):\/\/[0-9a-f]+\@sentry\.io[\/]+[0-9]+([\/]+)?$/i',
                    $dsn
                )
            ) {
                $dsn = null;
            }
        }
        if (!App::isLocalHost()) {
            if ($dsn && $sentryConfig instanceof ConfigAdapterInterface) {
                Sentry\init($sentryConfig->toArray());
            }
        }
         */

        return $config;
    },
    'extension' => function (ContainerInterface $container) : ExtensionLoader {
        return new ExtensionLoader(
            __DIR__ .'/../Extensions',
            $container,
            'ArrayIterator\\Api\\Crypt\\App\\Extensions'
        );
    },
    'db' => function (ContainerInterface $container) : DB {
        /**
         * @var ConfigInterface[] $container
         */
        $settings = $container['config']->toArray();
        $config = isset($settings['db']) && is_array($settings['db'])
            ? $settings['db']
            : (
            isset($settings['database']) && is_array($settings['database'])
                ? $settings['database']
                : null
            );

        $tempDir = sys_get_temp_dir()?: '/tmp';
        $tempDir = rtrim($tempDir, '\\\\/');
        if (!$config || (
                empty($config['dbname'])
                && empty($config['name'])
                && empty($config['path'])
                && empty($config['dbpath'])
            )
        ) {
            $storagePath = dirname(dirname(__DIR__)) . '/storage';
            !file_exists($storagePath)
            && is_writable(dirname($storagePath))
            && @mkdir($storagePath, 0755);

            $storagePath = !is_writable($storagePath) ? $tempDir : $storagePath;
            $config = [
                'driver' => 'sqlite',
                'path' => "{$storagePath}/database/database.sqlite"
            ];
        }

        $db = DB::instance(new Database($config));
        if ($db->getDriver()->getDatabasePlatform()->getName() === 'sqlite') {
            $dbFile = $db->getDatabase();
            if (!file_exists($dbFile)) {
                if (!is_dir(dirname($dbFile))) {
                    mkdir(dirname($dbFile), 0755, true);
                }
                if (!file_exists($dbFile)) {
                    touch($dbFile);
                }
            }
        }

        $db->connect();
        return $db;
    },
    'cache' => function (ContainerInterface $container) : Cache {
        /**
         * @var ConfigInterface[] $container
         */
        $settings = $container['config']->toArray();
        $tempDir = sys_get_temp_dir()?: '/tmp';
        $tempDir = rtrim($tempDir, '\\\\/');
        $storagePath = dirname(dirname(__DIR__)) . '/storage';
        if (!file_exists($storagePath) && is_writable(dirname($storagePath))) {
            mkdir($storagePath, 0755);
        }
        $storagePath = !is_writable($storagePath) ? $tempDir : $storagePath;

        if (empty($settings['cache'])
            || !is_array($settings['cache'])
            || !isset($settings['cache']['driver'])
            || empty($settings['cache']['driver'])
        ) {
            $config = [
                'driver' => Cache::FILE_SYSTEM,
                'path' => "{$storagePath}/cache/"
            ];
            if (extension_loaded('redis')) {
                try {
                    $redis = new \Redis();
                    $redis->connect('127.0.0.1');
                    $config = [
                        'driver' => Cache::REDIS,
                        'host' => '127.0.0.1',
                        'redis' => $redis
                    ];
                } catch (Exception $e) {
                }
            }
        } else {
            $config = $settings['cache'];
            switch ($settings['cache']['driver']) {
                case Cache::SQLITE:
                case 'sqlite':
                case 'sqlite3':
                    $config['dbname'] = "{$storagePath}/database/cache.sqlite";
                    if (!isset($config['table']) || !is_string($config['table'])) {
                        $config['table'] = 'cache';
                    }
                    if (!is_dir(dirname($config['dbname']))) {
                        mkdir($config['dbname'], 0755, true);
                    }
                    if (!file_exists($config['dbname'])) {
                        touch($config['dbname']);
                    }

                    break;
                case Cache::DATABASE:
                case 'database':
                    /**
                     * @var Database[] $container
                     */
                    $config['database'] = $container['db']->getConnection();
                    if (!isset($config['table']) || !is_string($config['table'])) {
                        $config['table'] = 'cache';
                    }
                    break;
            }
        }

        $cache = new Cache($config);
        if ($cache->getDriver() === Cache::ARRAYS
            && is_writable("{$storagePath}/cache/")
        ) {
            $cache = new Cache([
                'driver' => Cache::FILE_SYSTEM,
                'path' => "{$storagePath}/cache/"
            ]);
        }
        return $cache;
    },
]);
