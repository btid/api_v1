<?php
declare(strict_types=1);
// root index

// autoload
use ArrayIterator\Api\Crypt\Source\App;
use ArrayIterator\Api\Crypt\Source\Route;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

require __DIR__ . '/../vendor/autoload.php';
return (function () : App {
    if (!PHP_SAPI !== 'cli') {
        if (class_exists(Run::class)) {
            $woo = new Run();
            $woo->appendHandler(new PrettyPageHandler());
            $woo->register();
        }
    }

    require __DIR__.'/../app/Components/Middleware.php';
    require __DIR__.'/../app/Components/Routes.php';
    return $this;
})->call(Route::setApp(new App(require __DIR__ .'/../app/Components/Container.php')))
    ->run($responseOnly??false);
