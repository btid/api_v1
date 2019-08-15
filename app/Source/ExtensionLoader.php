<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

/**
 * Class ExtensionLoader
 * @package ArrayIterator\Api\Crypt\Source
 */
class ExtensionLoader
{
    const DEFAULT_NAMESPACE = 'ArrayIterator\\Api\\Crypt\\App\\Extensions';

    /**
     * @var string
     */
    protected $path;

    /**
     * @var Extension[]
     */
    protected $extensions;
    /**
     * @var string[]
     */
    protected $invalidExtensions = [];

    /**
     * @var string
     */
    protected $extensionNameSpace;
    /**
     * @var bool
     */
    protected $initialized = false;
    /**
     * ExtensionLoader constructor.
     * @param string $path
     * @param string $extensionNameSpace
     */
    public function __construct(string $path, string $extensionNameSpace = self::DEFAULT_NAMESPACE)
    {
        $this->path = realpath($path)?:$path;
        $this->extensionNameSpace = $extensionNameSpace;
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not valid extension directory.', $path)
            );
        }
        $this->extensionNameSpace = preg_replace('~[/\\\]+~', '\\', $this->extensionNameSpace);
        $this->extensionNameSpace = trim($this->extensionNameSpace, '\\');

        if (!preg_match('/^([A-Z_]+(?:[A-Z_0-9]?\\\)?)+$/i', $this->extensionNameSpace)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not valid extension namespace.', $path)
            );
        }
        $this->extensions = null;
    }

    /**
     * @return string
     */
    public function getExtensionNameSpace(): string
    {
        return $this->extensionNameSpace;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function initialize()
    {
        if (is_array($this->extensions) && $this->initialized) {
            return;
        }

        $this->extensions = [];
        $this->initialized = true;
        foreach (new \DirectoryIterator($this->getPath()) as $spl) {
            if ($spl->isDot() || !$spl->isDir()) {
                continue;
            }
            $name = $spl->getBasename();
            $path = $spl->getRealPath();
            $nameLower = strtolower($name);
            if (!preg_match('/^[A-Za-z_]+([A-Za-z_0-9]+)?$/', $name)) {
                $this->invalidExtensions[$nameLower] = $path;
                continue;
            }
            $className = "{$this->getExtensionNameSpace()}\\{$name}\\{$name}";
            try {
                try {
                    if (class_exists($className) && is_subclass_of($className, Extension::class)) {
                        $class = new $className($this);
                        $this->extensions[$this->sanitizeName($name)] = $class;
                        continue;
                    }
                } catch (\Throwable $e) {
                    // pass
                }

                $includeFile = null;
                if (file_exists("{$path}/{$name}.php")) {
                    $includeFile = "{$path}/{$name}.php";
                    $includeFile = realpath($includeFile)?:$includeFile;
                    try {
                        set_error_handler(function () {
                        });
                        /** @noinspection PhpIncludeInspection */
                        include_once $includeFile;
                        if (class_exists($className) && is_subclass_of($className, Extension::class)) {
                            $this->extensions[$this->sanitizeName($name)] = $className;
                            restore_error_handler();
                            continue;
                        }
                        restore_error_handler();
                    } catch (\Throwable $e) {
                        // pass
                        restore_error_handler();
                    }
                }

                // crawl sub
                $hasExtension = false;
                foreach (new \DirectoryIterator($spl->getRealPath()) as $subSpl) {
                    if ($hasExtension) {
                        break;
                    }
                    if (!$subSpl->isFile()
                        || $subSpl->getExtension() !== 'php'
                        || $includeFile && $subSpl->getRealPath() === $includeFile
                        || strtolower($spl->getBasename()) !== $nameLower
                    ) {
                        continue;
                    }

                    try {
                        if (class_exists($className) && is_subclass_of($className, Extension::class)) {
                            $this->extensions[$this->sanitizeName($name)] = $className;
                            $hasExtension = true;
                            break;
                        }

                        set_error_handler(function () {
                        });
                        /** @noinspection PhpIncludeInspection */
                        include_once $spl->getRealPath();
                        if (class_exists($className) && is_subclass_of($className, Extension::class)) {
                            $this->extensions[$this->sanitizeName($name)] = $className;
                            $hasExtension = true;
                            restore_error_handler();
                            break;
                        }
                        restore_error_handler();
                    } catch (\Throwable $e) {
                        // pass
                        restore_error_handler();
                    }
                }

                if (!$hasExtension) {
                    $this->invalidExtensions[$this->sanitizeName($name)] = $spl->getRealPath();
                }
            } catch (\Throwable $e) {
                // pass
            }
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public function sanitizeName(string $name) : string
    {
        return strtolower($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    final public function exists(string $name) : bool
    {
        $this->initialize();
        return isset($this->extensions[$this->sanitizeName($name)]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isLoaded(string $name)
    {
        if (!$this->exists($name)) {
            return false;
        }

        $extension = $this->extensions[$this->sanitizeName($name)];
        return is_string($extension) || $extension->isExtensionHasInit();
    }

    /**
     * @return Extension[]
     */
    public function getExtensions(): array
    {
        if (!$this->isInitialized()) {
            $this->initialize();
        }
        foreach ($this->extensions as $key => $v) {
            if (is_string($v)) {
                $this->extensions[$key] = new $v($this);
            }
        }
        return $this->extensions;
    }

    /**
     * @return string[]
     */
    public function getInvalidExtensions(): array
    {
        return $this->invalidExtensions;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @param string $name
     * @return Extension
     * @throws \InvalidArgumentException
     */
    public function load(string $name) : Extension
    {
        if (!$this->exists($name)) {
            throw new \InvalidArgumentException(
                sprintf('Extension %s is not exist.', $name)
            );
        }

        $name = $this->sanitizeName($name);
        if (is_string($this->extensions[$name])) {
            $this->extensions[$name] = new $this->extensions[$name]($this);
        }
        return $this->extensions[$name]->extensionInit();
    }
}
