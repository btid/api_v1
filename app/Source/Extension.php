<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

/**
 * Class Extension
 * @package ArrayIterator\Api\Crypt\Source
 */
abstract class Extension
{
    /**
     * @var ExtensionLoader
     */
    protected $extensionLoader;

    /**
     * @var string
     */
    protected $extensionName = '';

    /**
     * @var string|int|bool
     */
    protected $extensionVersion;

    /**
     * @var string
     */
    protected $extensionDescription = '';

    /**
     * @var string
     */
    private $extensionClass = __CLASS__;

    /**
     * @var string
     */
    private $extensionFilePath;
    /**
     * @var string
     */
    private $extensionSelectorBaseName;

    /**
     * @var bool
     */
    private $extensionHasInit = false;

    /**
     * Extension constructor.
     * @param ExtensionLoader $loader
     * @throws \ReflectionException
     */
    final public function __construct(ExtensionLoader $loader)
    {
        if (true === $this->extensionHasInit) {
            return;
        }

        $this->extensionLoader = $loader;
        $this->extensionClass = get_called_class();
        $this->extensionFilePath = (new \ReflectionClass($this))->getFileName();
        $ex = explode('\\', $this->extensionClass);
        $this->extensionSelectorBaseName = $loader->sanitizeName(array_pop($ex));
    }

    /**
     * @return ExtensionLoader
     */
    final public function getExtensionLoader(): ExtensionLoader
    {
        return $this->extensionLoader;
    }

    /**
     * @return string
     */
    final public function getExtensionClass(): string
    {
        return $this->extensionClass;
    }

    /**
     * @return string
     */
    final public function getExtensionSelectorBaseName(): string
    {
        return $this->extensionSelectorBaseName;
    }

    /**
     * @return string
     */
    public function getExtensionName(): string
    {
        if (!is_string($this->extensionName) || trim($this->extensionName) === '') {
            $this->extensionName = explode('\\', $this->getExtensionClass());
            $this->extensionName = array_pop($this->extensionName);
        }

        return $this->extensionName;
    }

    /**
     * @return bool|int|string
     */
    public function getExtensionVersion()
    {
        return $this->extensionVersion;
    }

    /**
     * @return string
     */
    public function getExtensionDescription(): string
    {
        if (!is_string($this->extensionDescription)) {
            $this->extensionDescription = '';
        }

        return $this->extensionDescription;
    }

    /**
     * @return bool
     */
    final public function isExtensionHasInit(): bool
    {
        return $this->extensionHasInit;
    }

    /**
     * @return string
     */
    public function getExtensionFilePath(): string
    {
        return $this->extensionFilePath;
    }

    /**
     * @return Extension|$this
     * @final
     */
    final public function extensionInit()
    {
        if (!$this->isExtensionHasInit()) {
            $this->extensionHasInit = true;
            // instantiate
            $this->getExtensionName();
            $this->afterInit();
        }

        return $this;
    }

    public static function extensionHelperCreateAutoLoaderLower()
    {
        static $called;
        if ($called) {
            return;
        }

        $called = true;
        /**
         * Autoloader Case Insensitive
         */
        spl_autoload_register(function ($className) {
            static $map;

            $className = strtolower($className);
            if (!isset($map)) {
                $autoLoadDir = dirname(dirname(__DIR__));
                $autoLoadFileBase = '/vendor/autoload.php';
                $autoLoadFile = $autoLoadDir. $autoLoadFileBase;
                if (!is_file($autoLoadFile)) {
                    if (is_file(dirname($autoLoadDir).$autoLoadFileBase)) {
                        $autoLoadFile = dirname($autoLoadDir).$autoLoadFileBase;
                    }
                }
                /** @noinspection PhpIncludeInspection */
                $map = (require $autoLoadFile)->getClassMap();
                $map = array_change_key_case($map, CASE_LOWER);
            }
            if (isset($map[$className]) && file_exists($map[$className])) {
                /** @noinspection PhpIncludeInspection */
                require $map[$className];
            }
        });
    }

    /**
     * @param string|null $path
     * @param string|null $nameSpace
     * @return $this
     */
    final protected function extensionHelperRegisterObjectAutoloader(
        string $path = null,
        string $nameSpace = null
    ) : Extension {
        $this->extensionHelperCreateAutoLoaderLower();
        if (!$nameSpace) {
            $class = get_class($this);
            $nameSpace = preg_replace('/^(.+)\\\[^\/]+$/', '$1', $class);
        }

        $autoLoadDir = dirname(dirname(__DIR__));
        $autoLoadFileBase = '/vendor/autoload.php';
        $autoLoadFile = $autoLoadDir. $autoLoadFileBase;
        if (!is_file($autoLoadFile)) {
            if (is_file(dirname($autoLoadDir).$autoLoadFileBase)) {
                $autoLoadFile = dirname($autoLoadDir).$autoLoadFileBase;
            }
        }
        $path = $path?: dirname($this->getExtensionFilePath());
        /** @noinspection PhpIncludeInspection */
        $autoload = require $autoLoadFile;
        $autoload->addPsr4(
            $nameSpace .'\\',
            [$path]
        );

        return $this;
    }

    /**
     * Call Method After initialize
     */
    protected function afterInit()
    {
        // bypass
    }
}
