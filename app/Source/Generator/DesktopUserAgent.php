<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Generator;

use InvalidArgumentException;
use RangeException;
use TypeError;

/**
 * Class DesktopUserAgent
 * @package ArrayIterator\Api\Crypt\Source\Generator
 */
final class DesktopUserAgent
{
    const MAX_ITERATION = 20;
    const MIN_ITERATION = 3;
    const DEFAULT_ITERATION = 5;

    const MOZ_COMPAT = 'Mozilla/5.0';

    const OS_LINUX = 'linux';
    const OS_WIN   = 'win';
    const OS_MAC   = 'mac';

    const BROWSER_CHROME  = 'chrome';
    const BROWSER_IE      = 'ie';
    const BROWSER_EDGE    = 'edge';
    const BROWSER_FIREFOX = 'firefox';
    const BROWSER_SAFARI  = 'safari';
    const BROWSER_OPERA   = 'opera';

    /* -----------------------------------------------
     * BROWSER MINIMUM & MAXIMUM VERSION
     * -----------------------------------------------
     */
    // max IE is version 11
    const MAX_VERSION_IE  = 11;
    // const MIN_VERSION_IE  = 6;
    const MIN_VERSION_IE  = 9;

    // 2017
    const MAX_VERSION_EDGE  = 42;
    const MIN_VERSION_EDGE  = 30;
    //const MIN_VERSION_EDGE  = 12;

    // just add future release
    const MAX_VERSION_CHROME  = 74;
    const MIN_VERSION_CHROME  = 60;

    // just add future release
    const MAX_VERSION_FIREFOX  = 85;
    //const MIN_VERSION_FIREFOX  = 3;
    const MIN_VERSION_FIREFOX  = 50;

    // just add future release
    const MAX_VERSION_OPERA  = 60;
    // const MIN_VERSION_OPERA  = 30;
    const MIN_VERSION_OPERA  = 40;

    const MAX_VERSION_SAFARI  = 11;
    const MAX_VERSION_SAFARI_WINDOWS = 5.1;
    // const MIN_VERSION_SAFARI  = 1;
    const MIN_VERSION_SAFARI  = 6;

    /**
     * @var array
     */
    private $versionMinMax = [
        self::BROWSER_CHROME    => [
            self::MIN_VERSION_CHROME,
            self::MAX_VERSION_CHROME
        ],
        self::BROWSER_IE        => [
            // default is 9
            self::MIN_VERSION_IE,
            self::MAX_VERSION_IE
        ],
        self::BROWSER_FIREFOX   => [
            self::MIN_VERSION_FIREFOX,
            self::MAX_VERSION_FIREFOX
        ],
        self::BROWSER_SAFARI   => [
            self::MIN_VERSION_SAFARI,
            self::MAX_VERSION_SAFARI
        ],
        self::BROWSER_OPERA   => [
            self::MIN_VERSION_OPERA,
            self::MAX_VERSION_OPERA
        ],
        self::BROWSER_EDGE   => [
            self::MIN_VERSION_EDGE,
            self::MAX_VERSION_EDGE
        ],
    ];

    /**
     * Iteration for array rand
     * @var int
     */
    protected $randomizeIteration = 5;

    /**
     * @var array
     */
    protected $processors = [
        self::OS_LINUX => [
            'i686',
            'x86_64'
        ],
        self::OS_MAC   => [
            'Intel',
            'PPC',
            'U; Intel',
            'U; PPC'
        ],
        self::OS_WIN => [
            '',
            'WOW64',
            'Win64; x64'
        ]
    ];

    /**
     * @var array
     */
    protected $lang = [
        'AB', 'AF', 'AN', 'AR', 'AS', 'AZ', 'BE', 'BG', 'BN', 'BO', 'BR', 'BS', 'CA', 'CE', 'CO', 'CS',
        'CU', 'CY', 'DA', 'DE', 'EL', 'EN', 'EO', 'ES', 'ET', 'EU', 'FA', 'FI', 'FJ', 'FO', 'FR', 'FY',
        'GA', 'GD', 'GL', 'GV', 'HE', 'HI', 'HR', 'HT', 'HU', 'HY', 'ID', 'IS', 'IT', 'JA', 'JV', 'KA',
        'KG', 'KO', 'KU', 'KW', 'KY', 'LA', 'LB', 'LI', 'LN', 'LT', 'LV', 'MG', 'MK', 'MN', 'MO', 'MS',
        'MT', 'MY', 'NB', 'NE', 'NL', 'NN', 'NO', 'OC', 'PL', 'PT', 'RM', 'RO', 'RU', 'SC', 'SE', 'SK',
        'SL', 'SO', 'SQ', 'SR', 'SV', 'SW', 'TK', 'TR', 'TY', 'UK', 'UR', 'UZ', 'VI', 'VO', 'YI', 'ZH'
    ];

    /**
     * @var array
     */
    protected $versionString = [
        'net' => null,
        'nt' => null,
        'ie' => null,
        'trident' => null,
        'osx' => null,
        'chrome' => null,
        'chromeedge' => null,
        'edge' => null,
        'safari' => null,
        'default' => null,
    ];

    /**
     * @var string
     */
    protected $currentRandom;

    /**
     * DesktopUserAgent constructor.
     *
     * @param int $iteration for array rand iteration
     */
    public function __construct(int $iteration = self::DEFAULT_ITERATION)
    {
        $this->versionMinMax = [
            self::BROWSER_CHROME    => [
                static::MIN_VERSION_CHROME,
                static::MAX_VERSION_CHROME
            ],
            self::BROWSER_IE        => [
                // default is 9
                static::MIN_VERSION_IE,
                static::MAX_VERSION_IE
            ],
            self::BROWSER_FIREFOX   => [
                static::MIN_VERSION_FIREFOX,
                static::MAX_VERSION_FIREFOX
            ],
            self::BROWSER_SAFARI   => [
                static::MIN_VERSION_SAFARI,
                static::MAX_VERSION_SAFARI
            ],
            self::BROWSER_OPERA   => [
                static::MIN_VERSION_OPERA,
                static::MAX_VERSION_OPERA
            ],
            self::BROWSER_EDGE   => [
                static::MIN_VERSION_EDGE,
                static::MAX_VERSION_EDGE
            ],
        ];
        $this->randomizeIteration = $iteration < 0
            ? self::MIN_ITERATION
            : ($iteration > self::MAX_ITERATION ? self::MAX_ITERATION : $iteration);
        foreach ($this->versionString as $key => $value) {
            $this->versionString[$key] = $this->getGenerateVersionString($key);
        }
    }

    /**
     * @param string $type
     * @param int $min
     * @param int $max
     *
     * @return bool|mixed
     */
    public function setVersionRange(string $type, int $min, int $max)
    {
        $type = strtolower(trim($type));
        if ($type === '') {
            throw new InvalidArgumentException(
                'Browser type could not be empty or white space only',
                E_USER_WARNING
            );
        }
        if (!isset($type, $this->osBrowserVersions)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Browser type %s is not exists',
                    $type
                ),
                E_USER_WARNING
            );
        }
        if ($min < 0) {
            throw new RangeException(
                'Minimum version could not be zero values',
                E_USER_WARNING
            );
        }
        if ($min > $max) {
            throw new RangeException(
                'Maximum version must be greater than minimum version',
                E_USER_WARNING
            );
        }
        /*switch ($type) {
            case self::BROWSER_IE:
                $minVersion = self::MIN_VERSION_IE;
                $maxVersion = self::MAX_VERSION_IE;
                break;
            case self::BROWSER_CHROME:
                $minVersion = self::MIN_VERSION_CHROME;
                $maxVersion = self::MAX_VERSION_CHROME;
                break;
            case self::BROWSER_FIREFOX:
                $minVersion = self::MIN_VERSION_FIREFOX;
                $maxVersion = self::MAX_VERSION_FIREFOX;
                break;
            case self::BROWSER_SAFARI:
                $minVersion = self::MIN_VERSION_SAFARI;
                $maxVersion = self::MAX_VERSION_SAFARI;
                break;
            case self::BROWSER_EDGE:
                $minVersion = self::MIN_VERSION_EDGE;
                $maxVersion = self::MAX_VERSION_EDGE;
                break;
            case self::BROWSER_OPERA:
                $minVersion = self::MIN_VERSION_OPERA;
                $maxVersion = self::MAX_VERSION_OPERA;
                break;
        }
        if (!isset($minVersion) || !isset($maxVersion)) {
            return false;
        }
        // @todo validate
        $min = $min <= $minVersion
            ? $minVersion
            : $min;
        $max = $max <= $maxVersion
            ? $maxVersion
            : $max;
        //if ($min === self::MAX_VERSION_IE) {
        //    $min -= 1;
        //}*/
        $max = $min === $max ? $max+1 : $max;
        $this->versionMinMax[$type][0] = $min;
        $this->versionMinMax[$type][1] = $max;
        return $this->versionMinMax[$type];
    }

    /**
     * Random array iteration
     *
     * @param array $array
     * @param int $iteration
     *
     * @return mixed
     */
    private function arrayRand(array $array, int $iteration = null)
    {
        $iteration = !is_int($iteration)
            ? $this->randomizeIteration
            : ($iteration > self::MAX_ITERATION
                ? self::MAX_ITERATION
                : (
                $iteration < self::MIN_ITERATION
                    ? self::MIN_ITERATION
                    : $iteration
                )
            );
        do {
            $rand = array_rand($array);
            $iteration--;
        } while ($iteration <= 0);

        return $rand;
    }

    /**
     * @param string $type
     * @param string $osxDelimiter
     *
     * @return string
     */
    private function getGenerateVersionString(string $type, string $osxDelimiter = null) : string
    {
        $arrays  = [];
        $delimiter = '.';
        $type = strtolower($type);
        switch ($type) {
            case 'net':
                $arrays[] = rand(1, 4);
                $arrays[] = rand(0, 9);
                $arrays[] = rand(10000, 99999);
                $arrays[] = rand(0, 9);
                break;
            case 'nt':
                $arrays[] = rand(5, 6);
                $arrays[] = rand(0, 3);
                break;
            case 'ie':
                $arrays[] = rand(7, 11);
                break;
            case 'trident':
                $arrays[] = rand(3, 7);
                $arrays[] = rand(0, 1);
                break;
            case 'osx':
                $arrays[] = 10;
                // 8 mountain lion,
                // 9 mavericks,
                // 10 yoesmite,
                // 11 elcapitan,
                // 12 sierra,
                // 13 high sierra,
                $arrays[] = rand(8, 13);
                if (end($arrays) === 13) {
                    $arrays[] = rand(0, 5);
                } else {
                    $arrays[] = end($arrays) > 11 ? rand(0, 6) : rand(0, 9);
                }
                $osxDelimiter === null && $osxDelimiter = '_';
                $delimiter = trim($osxDelimiter)?: $delimiter;
                break;
            case 'chrome':
            case 'chromeedge':
                $min = isset($this->versionMinMax[self::BROWSER_CHROME])
                    ? min($this->versionMinMax[self::BROWSER_CHROME])
                    : static::MIN_VERSION_CHROME;
                $max = isset($this->versionMinMax[self::BROWSER_CHROME])
                    ? max($this->versionMinMax[self::BROWSER_CHROME])
                    : static::MAX_VERSION_CHROME;
                $min = $type === 'chromeedge' ? 39 : $min;
                $max = $type === 'chromeedge' ? 80 : $max;
                $arrays[] = rand($min, $max);
                $arrays[] = 0;
                $arrays[] = rand(800, 899);
                $arrays[] = 0;
                break;
            case 'edge':
                $min = isset($this->versionMinMax[self::BROWSER_EDGE])
                    ? min($this->versionMinMax[self::BROWSER_EDGE])
                    : static::MIN_VERSION_EDGE;
                $max = isset($this->versionMinMax[self::BROWSER_EDGE])
                    ? max($this->versionMinMax[self::BROWSER_EDGE])
                    : static::MAX_VERSION_EDGE;
                $major = rand($min, $max);
                $minor = rand(40, 999);
                $arrays[] = $minor < 100 ? "{$major}.{$major}0{$minor}" : "{$major}.{$major}{$minor}";
                break;
            case 'safari':
                $min = isset($this->versionMinMax[self::BROWSER_SAFARI])
                    ? min($this->versionMinMax[self::BROWSER_SAFARI])
                    : static::MIN_VERSION_SAFARI;
                $max = isset($this->versionMinMax[self::BROWSER_SAFARI])
                    ? max($this->versionMinMax[self::BROWSER_SAFARI])
                    : static::MAX_VERSION_SAFARI;
                $arrays[] = rand($min, $max);
                $arrays[] = rand(0, 2);
                $arrays[] = rand(0, 2);
                break;
            default:
                $arrays[] = rand(1, 20);
                $arrays[] = 0;
                $arrays[] = rand(0, 99);
                $osxDelimiter === null && $osxDelimiter = '_';
                $delimiter = trim($osxDelimiter)?: $delimiter;
        }

        return implode($delimiter, $arrays);
    }

    /**
     * Get Random Revisions
     *
     * @param int $length
     *
     * @return string
     */
    public function getRandomRevision(int $length) : string
    {
        $length = $length < 1 ? 1 : $length;
        $returnValue = '';
        while ($length > 0) {
            $returnValue .= '.' .rand(0, 9);
            $length--;
        }
        return $returnValue;
    }

    /**
     * Get Random float between 0 - 0.9999...
     *
     * @return float
     */
    public function getMathRandom() : float
    {
        return mt_rand() / mt_getrandmax();
    }

    /**
     * @param $start
     * @param null $end
     *
     * @return float|int|mixed
     * @throws \TypeError
     */
    protected function randomize($start = null, $end = null)
    {
        // fallback default to range(0, 100)
        if (func_num_args() === 0) {
            $start = 0;
            $end   = 100;
        }

        if ($start === null && is_int($end)) {
            $start = 0;
            $end   = $end === $start ? $start+1 : $end;
        }
        $start = is_numeric($start) && is_string($start)
            ? (strpos($start, '.') === false
                ? (int) $start
                : (float) $start
            ) : $start;
        $end = is_numeric($end) && is_string($end)
            ? (strpos($end, '.') === false
                ? (int) $end
                : (float) $end
            ) : $end;

        if (is_int($start) && is_int($end)) {
            if ($start > $end) {
                throw new RangeException(
                    'Start offset must be less or equal than end offset',
                    E_WARNING
                );
            }

            return floor($this->getMathRandom() * ($start-$end+1)) + $start;
        }

        if (!is_array($start)) {
            throw new TypeError(
                sprintf(
                    'Invalid arguments passed to %1$s(%2$s)',
                    __FUNCTION__,
                    (func_num_args() > 1 ? '$start, $end' : '$start')
                )
            );
        }

        if (is_array($start) && is_int(key($start))) {
            shuffle($start);
            return current($start);
        }

        $rand = $this->randomize(0, 100) / 100;
        $min = 0;
        $key = null;
        $returnValue = null;
        foreach ($start as $key => $value) {
            if (is_float($value)) {
                $max = $value + $min;
                $returnValue = $key;
                if ($rand >= $min && $rand <= $max) {
                    break;
                }
                $min = $min + $value;
            }
        }

        if ($returnValue === null) {
            $returnValue = $start[$this->arrayRand($start)];
        }

        return $returnValue;
    }

    /**
     * @return string
     */
    public function getRandomBrowser() : string
    {
        return $this->arrayRand($this->versionMinMax);
    }

    /**
     * @return string
     */
    public function getRandomOS() : string
    {
        return $this->arrayRand($this->processors);
    }

    /**
     * Get Random Processor
     *
     * @param string|null $os if invalid OS or not exists will be generated random
     *
     * @return string[] offset 0 is os and 1 is arch if
     */
    public function getRandomProcessor(string $os = null) : array
    {
        $os = !is_string($os) || !isset($this->processors[$os])
            ? $this->getRandomOS()
            : $os;
        $selectedOS = $this->processors[$os];
        $arch = $selectedOS[$this->arrayRand($selectedOS)];

        return [
            $os,
            $arch
        ];
    }

    /**
     * @param string $os
     * @param string $versionString
     * @param string $processor
     * @param string|null $default
     *
     * @return string
     */
    private function generateBrowserPrefix(
        string $os,
        string $versionString = '',
        string $processor = '',
        string $default = null
    ) : string {
        $returnValue = '';
        switch ($os) {
            case self::OS_WIN:
                $processor   = $processor ? "; {$processor}" : '';
                $returnValue .= "Windows NT {$versionString}{$processor}";
                break;
            case self::OS_MAC:
                $returnValue .= "Macintosh; {$processor} Mac OS X {$versionString}";
                break;
            case self::OS_LINUX:
                $versionString = $versionString ? " $versionString": '';
                $returnValue .= "X11; Linux{$versionString}";
                break;
            default:
                if ($default) {
                    $default = stripos($default, 'win') !== false
                        ? self::OS_WIN
                        : (
                        stripos($default, 'lin') !== false
                            ? self::OS_LINUX
                            : (
                        stripos($default, 'mac') !== false
                        || stripos($default, 'x') !== false
                            ? self::OS_MAC
                            : null
                        )
                        );
                }
                if (!$default) {
                    $default = $this->getRandomOS();
                }
                $default = strtolower($default);
                return $this->generateBrowserPrefix(
                    $default,
                    $versionString,
                    $processor,
                    // fallback to window to prevent infinite loop possibilities
                    self::OS_WIN
                );
        }

        return $returnValue;
    }

    /**
     * @param string|null $os
     * @param string|null $processor
     * @param string|null $firefoxVersion
     * @param string|null $versionString
     *
     * @return string
     */
    public function getFromFirefox(
        string $os = null,
        string $processor = null,
        string $firefoxVersion = null,
        string $versionString = null
    ) : string {
        $os   = !is_string($os) ? $this->getRandomOS() : $os;
        if (!is_string($processor) || trim($processor) === '' && $os !== self::OS_WIN) {
            $processorArray = $this->getRandomProcessor($os);
            $os        = array_shift($processorArray);
            $processor = array_shift($processorArray);
        }

        /**
         * see @link https://developer.mozilla.org/en-US/docs/Gecko_user_agent_string_reference
         */
        $firefoxVersion = !is_string($firefoxVersion) || trim($firefoxVersion) === ''
            ? rand(
                $this->versionMinMax[self::BROWSER_FIREFOX][0],
                $this->versionMinMax[self::BROWSER_FIREFOX][1]
            ) . $this->getRandomRevision(2)
            : trim($firefoxVersion);
        if (!is_string($versionString) || trim($versionString) === '') {
            $versionString = $os === self::OS_WIN
                ? $this->getGenerateVersionString('nt')
                : ($os === self::OS_MAC ? $this->getGenerateVersionString('osx') : '');
        }

        $firefoxVersionSL = substr($firefoxVersion, 0, -2);
        $versionString = trim($versionString);
        $osVersion = $this->generateBrowserPrefix($os, $versionString, $processor);
        return self::MOZ_COMPAT
            . " ({$osVersion}; rv:{$firefoxVersionSL}) Gecko/20100101 Firefox/{$firefoxVersion}";
    }

    /**
     * @param null $os
     * @param null $version
     * @param null $processor
     * @param string|null $chromeVersion
     * @param string|null $webkitVersion
     * @param string $name
     * @param string|null $lang
     *
     * @return string
     * @access internal
     */
    protected function generateByWebkit(
        $os = null,
        $version = null,
        $processor = null,
        string $chromeVersion = null,
        string $webkitVersion = null,
        string $name = '',
        string $lang = null
    ) : string {
        $os   = !is_string($os) ? $this->getRandomOS() : $os;
        $isEdge = false;
        $isOpera = false;
        $isSafari = false;
        $type = self::BROWSER_CHROME;
        $versionStringType = 'chrome';
        if ($name) {
            if (stripos($name, 'edge') !== false) {
                $os     = self::OS_WIN;
                $isEdge = true;
                $versionStringType = 'chromeedge';
                $type   = self::BROWSER_EDGE;
            } elseif (stripos($name, 'opr') !== false) {
                $type = self::BROWSER_OPERA;
                $isOpera = true;
            } elseif (stripos($name, 'saf') !== false) {
                $type = self::BROWSER_SAFARI;
                $isSafari = true;
                $versionStringType = 'osx';
            }
        }
        if ($isSafari) {
            $lang = !$lang ? $this->lang[$this->arrayRand($this->lang)] : strtoupper($lang);
        }
        $chromeType = $isSafari ? 'Version' : 'Chrome';
        if (!is_string($processor) || trim($processor) === '' && $os !== self::OS_WIN) {
            $processorArray = $this->getRandomProcessor($os);
            $os             = array_shift($processorArray);
            $processor      = array_shift($processorArray);
        }

        $version = !$version
            || ! is_numeric($version)
            || ! in_array(
                intval($version),
                range(
                    $this->versionMinMax[$type][0],
                    $this->versionMinMax[$type][1]
                )
            ) ? $this->getGenerateVersionString(
                $os === self::OS_MAC ? 'osx' : $versionStringType,
                $os === self::OS_MAC ? '_' : '.'
            ) : $version;
        if ($isSafari && $os === self::OS_MAC) {
            $version = trim(str_replace('.', '_', (string) $version), '_');
        }
        if (!is_string($processor) || trim($processor) === '') {
            $processorArray = $this->getRandomProcessor($os);
            $processor = array_pop($processorArray);
        }
        $chromeVersion = $chromeVersion && trim($chromeVersion) !== ''
            ? trim($chromeVersion)
            : $this->getGenerateVersionString('chrome');
        $processor = $processor ? $processor : '';
        $webkitVersion = !$webkitVersion || trim($webkitVersion)
            ? $this->getGenerateVersionString('safari')
            : trim($webkitVersion);
        $osVersion = $this->generateBrowserPrefix(
            $os,
            $isEdge
                ? '10.0'
                : ($isSafari
                    ? "{$version} rv: ".substr((string) $chromeVersion, 0, 2).".0 {$lang}"
                    : $version
            ),
            $processor
        );

        $returnValue = self::MOZ_COMPAT
            . " ($osVersion) AppleWebKit/{$webkitVersion} (KHTML, like Gecko) {$chromeType}/{$chromeVersion}"
            . " Safari/{$webkitVersion}";

        if ($isEdge) {
            $returnValue .= "Edge/{$version}";
        } elseif ($isOpera) {
            $returnValue .= "OPR/{$version}";
        }

        return $returnValue;
    }

    /**
     * @param string|null $os
     * @param null $version
     * @param null $processor
     * @param string|null $chromeVersion
     * @param string|null $webkitVersion
     *
     * @return string
     */
    public function getFromOpera(
        string $os = null,
        $version = null,
        $processor = null,
        string $chromeVersion = null,
        string $webkitVersion = null
    ) : string {
        return $this->generateByWebkit(
            $os,
            $version,
            $processor,
            $chromeVersion,
            $webkitVersion,
            'opera'
        );
    }

    /**
     * @param string|null $os
     * @param null $version
     * @param null $processor
     * @param string|null $chromeVersion
     * @param string|null $webkitVersion
     *
     * @return string
     */
    public function getFromChrome(
        string $os = null,
        $version = null,
        $processor = null,
        string $chromeVersion = null,
        string $webkitVersion = null
    ) : string {
        return $this->generateByWebkit(
            $os,
            $version,
            $processor,
            $chromeVersion,
            $webkitVersion
        );
    }

    /**
     * @param string|null $os
     * @param null $version
     * @param null $processor
     * @param string|null $chromeVersion
     * @param string|null $webkitVersion
     * @param string|null $lang
     *
     * @return string
     */
    public function getFromSafari(
        string $os = null,
        $version = null,
        $processor = null,
        string $chromeVersion = null,
        string $webkitVersion = null,
        string $lang = null
    ) : string {
        return $this->generateByWebkit(
            $os,
            $version,
            $processor,
            $chromeVersion,
            $webkitVersion,
            'safari',
            $lang
        );
    }

    /**
     * Get edge Browser
     *
     * @param int|string|null $version
     * @param string|null $processor
     * @param string|null $chromeVersion
     * @param string|null $webkitVersion
     *
     * @return string
     */
    public function getFromEdge(
        $version = null,
        $processor = null,
        string $chromeVersion = null,
        string $webkitVersion = null
    ) : string {
        return $this->generateByWebkit(
            self::OS_WIN,
            $version,
            $processor,
            $chromeVersion,
            $webkitVersion,
            'edge'
        );
    }

    /**
     * Generate from Internet Explorer
     *
     * @param mixed|null $version
     * @param string|null $ntVersion
     * @param bool $touch
     * @param string|null $tridentVersion
     * @param string|null $netVersion
     *
     * @return string
     */
    public function getFromIE(
        $version = null,
        string $ntVersion = null,
        $touch = null,
        string $tridentVersion = null,
        $netVersion = null
    ) : string {
        $version = ! is_numeric($version)
        || ! in_array(intval($version), range(
            $this->versionMinMax[self::BROWSER_IE][0],
            $this->versionMinMax[self::BROWSER_IE][1]
        )) ? $this->getGenerateVersionString('ie') : $version;
        $ntVersion = !is_numeric($ntVersion) || trim($ntVersion) === ''
            ? rand(1, 3)
            : $ntVersion;
        $touch = $touch === null
            ? $this->arrayRand(['Touch; ' => null, '' => null])
            : ($touch ? 'Touch; ': '');
        if ($version >= 11) {
            /**
             * see @link http://msdn.microsoft.com/en-us/library/ie/hh869301(v=vs.85).aspx
             */
            return self::MOZ_COMPAT . " (Windows NT 6.{$ntVersion}; Trident/7.0; {$touch}rv:11.0) like Gecko";
        }
        $tridentVersion = ! $tridentVersion || trim($tridentVersion) === ''
            ? $this->getGenerateVersionString('trident')
            : trim($tridentVersion);
        $netVersion = ! is_bool($netVersion) && !is_string($netVersion)
            ? rand(0, 1) == 1
            : $netVersion;
        if (is_bool($netVersion)) {
            $netVersion = is_bool($netVersion)
                ? (!$netVersion ? '' : $this->getGenerateVersionString('net'))
                : (trim($netVersion) ?: '');
        }

        $netVersion = $netVersion !== '' ? "; .NET CLR {$netVersion}" : '';

        return self::MOZ_COMPAT
            . " (compatible; MSIE {$version}.0; Windows NT {$ntVersion}; Trident/{$tridentVersion}{$netVersion})";
    }


    /**
     * Firefox support OS and version
     *
     * @param string|null $version
     * @param string|null $os
     *
     * @return string
     */
    public function firefox($version = null, string $os = null) : string
    {
        return $this->getFromFirefox($os, null, $version);
    }

    /**
     * Opera support OS and version
     *
     * @param string|null $version
     * @param string|null $os
     *
     * @return string
     */
    public function opera($version = null, string $os = null) : string
    {
        return $this->getFromOpera($os, null, $version);
    }

    /**
     * Chrome support OS and version
     *
     * @param string|null $version
     * @param string|null $os
     *
     * @return string
     */
    public function chrome($version = null, string $os = null) : string
    {
        return $this->getFromChrome($os, null, $version);
    }

    /**
     * Safari support OS and version
     *
     * @param string|null $version
     * @param string|null $os
     * @param string      $lang
     *
     * @return string
     */
    public function safari($version = null, string $os = null, string $lang = null) : string
    {
        return $this->getFromSafari($os, null, $version, $lang);
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public function ie($version = null) : string
    {
        return $this->getFromIE($version);
    }

    /**
     * @param string $version
     *
     * @return string
     */
    public function edge($version = null) : string
    {
        return $this->getFromEdge($version);
    }

    /**
     * Get Random
     *
     * @param bool $useIE use internet Explorer or edge
     *
     * @return string
     */
    public function getRandomUserAgent(bool $useIE = true) : string
    {
        $os = $this->getRandomOS();
        if (!$useIE) {
            $array = $this->versionMinMax;
            unset($array[self::BROWSER_IE], $array[self::BROWSER_EDGE]);
            $browser = $this->arrayRand($array);
        } else {
            $browser = $this->getRandomBrowser();
        }

        if ($useIE && in_array($browser, [self::BROWSER_IE, self::BROWSER_EDGE])) {
            $random = $this->{self::BROWSER_IE}();
        } else {
            $browser = ucfirst($browser);
            $random = $this->{"getFrom{$browser}"}($os);
        }
        if (!isset($this->currentRandom)) {
            $this->currentRandom = $random;
        }
        return $random;
    }

    /**
     * @return string
     */
    public function getCurrentRandomUserAgent(): string
    {
        if (!isset($this->currentRandom)) {
            $this->currentRandom = $this->getRandomUserAgent();
        }

        return $this->currentRandom;
    }

    /**
     * @param bool $useIE
     * @return string
     */
    public static function generateRandom(bool $useIE = true) : string
    {
        return (new self())->getRandomUserAgent($useIE);
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->currentRandom;
    }
}
