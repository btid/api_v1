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
     */
    final public function __construct(ExtensionLoader $loader)
    {
        if (true === $this->extensionHasInit) {
            return;
        }

        $this->extensionLoader = $loader;
        $this->extensionFilePath = __FILE__;
        $this->extensionClass = get_called_class();
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
        if (!is_string($this->extensionName)) {
            $this->extensionName = ltrim(preg_replace('~[\\\]~', ' ', $this->getExtensionClass()), '\\');
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
            $this->afterInit();
        }

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
