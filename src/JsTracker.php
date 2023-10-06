<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Yii\Matomo;

use GuzzleHttp\Utils;
use InvalidArgumentException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\View\WebView;

/**
 * The Matomo Tracking Component adds {@link https://matomo.org/ Matomo Analytics Platform} tracking code to every
 * page and adds additional code for tracking function calls made in the application.
 *
 * See the {@link https://developer.matomo.org/guides/tracking-javascript-guide Matomo JavaScript Tracking Client}
 * and {@link https://developer.matomo.org/api-reference/tracking-javascript Matomo JavaScript Tracking API}
 * documentation for details on tracking functions.
 */
final class JsTracker
{
    /** @var int No consent required */
    public const CONSENT_NONE = 0;
    /** @var int Cookie consent. When required, tracking requests are sent but cookies are not set */
    public const CONSENT_COOKIE = 1;
    /** @var int Tracking consent. When required, tracking requests are not sent and cookies are not set */
    public const CONSENT_TRACKING = 2;
    /** @var string Character denoting a string literal */
    public const STRING_LITERAL = ':';

    /**
     * @var array<string> Matomo JavaScript Tracking API function calls
     */
    private array $functions = [];

    public function __construct(
        private string $url,
        private int $siteId,
        private bool $imageTracking = false,
        private string $jsFile = 'matomo.js',
        private string $phpFile = 'matomo.php'
    )
    {
    }

    /**
     * Adds a Matomo tracker function to the trackers array
     *
     * @param string $name Function name
     * @param array|scalar $parameters If a scalar it is used as is.
     * If there is one parameter it is a scalar value and used as is.
     * If there are two parameters and the first is an array or model, the second is an array of parameters in the order
     * required by the function. Array values are one of:
     *   * a string: if the string begins with self::STRING_LITERAL it is a string literal, else it is the
     * attribute/key name in the model/array,
     *   * an anonymous function returning the value. The anonymous function signature is: function($model)
     *   * any other type - integer, float, or boolean is used as is. Use boolean false for unused parameters
     * Make sure that the parameters are the type required by the JavaScript Tracking API; in particular, make sure
     * booleans, floats, and integers are not strings
     * If there are two parameters and the first is a scalar or there are more than two, the parameters are used as is
     *
     * Example 'addEcommerceItem' function parameters:
     * ```php
        * [
            * 'sku',
            * 'name',
            * ':Category',
            * function($model){return (float)$model->getTotalPrice();},
            * function($model){return (int)$model->getQuantity();}
        * ]
     * ```
     * @return self
     * @throws InvalidArgumentException|\Exception
     */
    public function addFunction(string $name, float|array|bool|int|string $parameters): self
    {
        $new = clone $this;

        $function = [$name];

        if (is_scalar($parameters)) {
            $function[] = $parameters;
        } else {
            switch (count($parameters)) {
                case 1: // scalar value
                    $function[] = $parameters[0];
                    break;
                case 2:
                    [$model, $params] = $parameters;

                    if (is_scalar($model)) {
                        foreach ($parameters as $parameter) {
                            $function[] = $parameter;
                        }
                    } else {
                        foreach ($params as $param) {
                            switch(gettype($param)) {
                                case 'string':
                                    if (str_contains($param, self::STRING_LITERAL)) {
                                        $function[] = substr($param, strlen(self::STRING_LITERAL));
                                    } else {
                                        $function[] = ArrayHelper::getValue($model, $param);
                                    }
                                    break;
                                case 'object': // Closure
                                    $function[] = ArrayHelper::getValue($model, $param);
                                    break;
                                case 'boolean':
                                case 'double':
                                case 'integer':
                                    $function[] = $param;
                                    break;
                                default:
                                    throw new InvalidArgumentException("Invalid parameter for '$name()'");
                            }
                        }
                    }
                    break;
                default:
                    foreach ($parameters as $parameter) {
                        $function[] = $parameter;
                    }
            }
        }

        $new->functions[] = Utils::jsonEncode($function);

        return $new;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getJsFile(): string
    {
        return $this->jsFile;
    }

    public function getPhpFile(): string
    {
        return $this->phpFile;
    }

    public function getSiteId(): int
    {
        return $this->siteId;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isImageTrackingEnabled(): bool
    {
        return $this->imageTracking;
    }
}
