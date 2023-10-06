<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Yii\Matomo;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function strlen;

class Tracker
{
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_LINK = 'link';
    public const MEDIA_AUDIO = 'audio';
    public const MEDIA_VIDEO = 'video';

    public const INVALID_ACTION_EXCEPTION = 'Invalid action';
    public const INVALID_COUNTRY_EXCEPTION = 'Invalid country; must be ISO 3166-1 alpha-2 country code';
    public const INVALID_CUSTOM_DIMENSION_ID_EXCEPTION = 'Invalid custom dimension id; must be 1 >= id >= 999';
    public const INVALID_LATITUDE_EXCEPTION = 'Invalid latitude';
    public const INVALID_LONGITUDE_EXCEPTION = 'Invalid longitude';
    public const INVALID_MEDIA_TYPE_EXCEPTION = 'Invalid media type';
    public const PARAMETER_REQUIRED_EXCEPTION = '%s is required';

    private const ACTIONS = [self::ACTION_DOWNLOAD, self::ACTION_LINK];
    private const API_VERSION = 1;
    private const COOKIE_CVAR = 'cvar';
    private const COOKIE_ID = 'id';
    private const COOKIE_REF = 'ref';
    private const COOKIE_SESSION = 'ses';
    private const FIRST_PARTY_COOKIES_PREFIX = '_matomo_';
    private const MAX_CUSTOM_DIMENSION_ID = 999;
    private const MIN_CUSTOM_DIMENSION_ID = 1;
    private const MEDIA_TYPES = [self::MEDIA_AUDIO, self::MEDIA_VIDEO];
    private const PARAMETER_ACTION_NAME = 'action_name';
    private const PARAMETER_API_VERSION = 'apiv';
    private const PARAMETER_ATTRIBUTION_CAMPAIGN_KEYWORD = '_rck';
    private const PARAMETER_ATTRIBUTION_CAMPAIGN_NAME = '_rcn';
    private const PARAMETER_ATTRIBUTION_TIMESTAMP = '_refts';
    private const PARAMETER_ATTRIBUTION_URL = '_ref';
    private const PARAMETER_AUTH_TOKEN = 'token_auth';
    private const PARAMETER_CHARSET = 'cs';
    private const PARAMETER_CONTENT_CONTENT = 'c_p';
    private const PARAMETER_CONTENT_INTERACTION = 'c_i';
    private const PARAMETER_CONTENT_NAME = 'c_n';
    private const PARAMETER_CONTENT_TARGET = 'c_t';
    private const PARAMETER_COOKIES = 'cookies';
    private const PARAMETER_CRASH = 'ca';
    private const PARAMETER_CRASH_CATEGORY = 'cra_ct';
    private const PARAMETER_CRASH_COLUMN = 'cra_rc';
    private const PARAMETER_CRASH_LINE = 'cra_rl';
    private const PARAMETER_CRASH_LOCATION = 'cra_ru';
    private const PARAMETER_CRASH_MESSAGE = 'cra';
    private const PARAMETER_CRASH_TYPE = 'cra_tp';
    private const PARAMETER_CRASH_STACK = 'cra_st';
    private const PARAMETER_CUSTOM_DIMENSION = 'dimension';
    private const PARAMETER_DATETIME = 'cdt';
    private const PARAMETER_ECOMMERCE_CATEGORY = '_pkc';
    private const PARAMETER_ECOMMERCE_DISCOUNT = 'ec_dt';
    private const PARAMETER_ECOMMERCE_ITEMS = 'ec_items';
    private const PARAMETER_ECOMMERCE_NAME = '_pkn';
    private const PARAMETER_ECOMMERCE_ORDER_ID = 'ec_id';
    private const PARAMETER_ECOMMERCE_PRICE = '_pkp';
    private const PARAMETER_ECOMMERCE_REVENUE = 'revenue';
    private const PARAMETER_ECOMMERCE_SHIPPING = 'ec_sh';
    private const PARAMETER_ECOMMERCE_SKU = '_pks';
    private const PARAMETER_ECOMMERCE_SUB_TOTAL = 'ec_st';
    private const PARAMETER_ECOMMERCE_TAX = 'ec_tx';
    private const PARAMETER_EVENT_ACTION = 'e_a';
    private const PARAMETER_EVENT_CATEGORY = 'e_c';
    private const PARAMETER_EVENT_NAME = 'e_n';
    private const PARAMETER_EVENT_VALUE = 'e_v';
    private const PARAMETER_GOAL_ID = 'idgoal';
    private const PARAMETER_LANGUAGE = 'lang';
    private const PARAMETER_LOCATION_CITY = 'city';
    private const PARAMETER_LOCATION_COUNTRY = 'country';
    private const PARAMETER_LOCATION_LATITUDE = 'lat';
    private const PARAMETER_LOCATION_LONGITUDE = 'long';
    private const PARAMETER_LOCATION_REGION = 'region';
    private const PARAMETER_IP = 'ip';
    private const PARAMETER_MEDIA_FULL_SCREEN = 'ma_fs';
    private const PARAMETER_MEDIA_HEIGHT = 'ma_h';
    private const PARAMETER_MEDIA_ID = 'ma_id';
    private const PARAMETER_MEDIA_LENGTH = 'ma_le';
    private const PARAMETER_MEDIA_PLAYER = 'ma_pn';
    private const PARAMETER_MEDIA_POSITION = 'ma_ps';
    private const PARAMETER_MEDIA_POSITIONS_PLAYED = 'ma_se';
    private const PARAMETER_MEDIA_TIME_PLAYED = 'ma_st';
    private const PARAMETER_MEDIA_TIME_TO_PLAY = 'ma_ttp';
    private const PARAMETER_MEDIA_TITLE = 'ma_ti';
    private const PARAMETER_MEDIA_TYPE = 'ma_mt';
    private const PARAMETER_MEDIA_URL = 'ma_re';
    private const PARAMETER_MEDIA_WIDTH = 'ma_w';
    private const PARAMETER_NEW_VISIT = 'new_visit';
    private const PARAMETER_PAGE_PERFORMANCE_DOM_COMPLETION = 'pf_dm2';
    private const PARAMETER_PAGE_PERFORMANCE_DOM_PROCESSING = 'pf_dm1';
    private const PARAMETER_PAGE_PERFORMANCE_NETWORK = 'pf_net';
    private const PARAMETER_PAGE_PERFORMANCE_ON_LOAD = 'pf_onl';
    private const PARAMETER_PAGE_PERFORMANCE_SERVER = 'pf_srv';
    private const PARAMETER_PAGE_PERFORMANCE_TRANSFER = 'pf_tfr';
    private const PARAMETER_PAGE_VIEW_ID = 'pv_id';
    private const PARAMETER_PING = 'ping';
    private const PARAMETER_PLUGIN_FLASH = 'fla';
    private const PARAMETER_PLUGIN_JAVA = 'java';
    private const PARAMETER_PLUGIN_PDF = 'pdf';
    private const PARAMETER_PLUGIN_QUICKTIME = 'qt';
    private const PARAMETER_PLUGIN_REAL_PLAYER = 'realp';
    private const PARAMETER_PLUGIN_SILVERLIGHT = 'ag';
    private const PARAMETER_PLUGIN_WINDOWS_MEDIA = 'wma';
    private const PARAMETER_RANDOM = 'rand';
    private const PARAMETER_REC = 'rec';
    private const PARAMETER_RESOLUTION = 'res';
    private const PARAMETER_SEARCH_CATEGORY = 'search_cat';
    private const PARAMETER_SEARCH_COUNT = 'search_count';
    private const PARAMETER_SEARCH_KEYWORD = 'search';
    private const PARAMETER_SEND_IMAGE = 'send_image';
    private const PARAMETER_SITE_ID = 'idsite';
    private const PARAMETER_TIME_HOUR = 'h';
    private const PARAMETER_TIME_MINUTE = 'm';
    private const PARAMETER_TIME_SECOND = 's';
    private const PARAMETER_TIMESTAMP = '_idts';
    private const PARAMETER_URL = 'url';
    private const PARAMETER_URL_REFERRER = 'urlref';
    private const PARAMETER_USER_AGENT = 'ua';
    private const PARAMETER_USER_AGENT_DATA = 'uadata';
    private const PARAMETER_USER_ID = 'uid';
    private const PARAMETER_VISITOR_ID = '_id';
    private const PLUGIN_FLASH = 'flash';
    private const PLUGIN_JAVA = 'java';
    private const PLUGIN_PDF = 'pdf';
    private const PLUGIN_QUICKTIME = 'quickTime';
    private const PLUGIN_REAL_PLAYER = 'realPlayer';
    private const PLUGIN_SILVERLIGHT = 'silverlight';
    private const PLUGIN_WINDOWS_MEDIA = 'windowsMedia';
    private const REFERRAL_COOKIE_TIMEOUT = 15768000; // 6 months
    private const SESSION_COOKIE_TIMEOUT = 1800; // 30 minutes
    private const VISITOR_COOKIE_TIMEOUT = 33955200; // 13 months (365 + 28 days)
    private const VISITOR_ID_LENGTH = 16;

    private const FALSE = 0;
    private const TRUE = 1;

    private bool $bulkTracking = false;
    private Client $client;
    private ?array $cookieConfig = null;
    private CookieJarInterface $cookieJar;
    private array $ecommerceItems = [];
    private ?CookieJarInterface $requestCookieJar = null;
    private array $trackerParameters;
    private array $trackingActions = [];

    public function __construct(
        private ServerRequestInterface $request,
        int $siteId,
        string $apiUrl,
        ?string $proxy = null,
        int $proxyPort = 80
    )
    {
        $clientOptions = ['base_uri' => $apiUrl];
        if ($proxy !== null) {
            $clientOptions['proxy'] = $proxy . ':' . $proxyPort;
        }
        $this->client = new Client($clientOptions);

        $this->cookieJar = new CookieJar(true);

        $this->trackerParameters = [
            self::PARAMETER_SITE_ID => $siteId,
            self::PARAMETER_API_VERSION => self::API_VERSION,
            self::PARAMETER_REC => self::TRUE,
        ];

        $serverParams = $this
            ->request
            ->getServerParams()
        ;

        foreach ([
            'HTTP_USER_AGENT' => self::PARAMETER_URL_REFERRER,
            'REMOTE_HOST' => self::PARAMETER_URL_REFERRER,
            'REQUEST_URI' => self::PARAMETER_URL,
        ] as $serverParam => $trackingParam) {
            if (!empty($serverParams[$serverParam])) {
                $this->trackerParameters[$trackingParam] = $serverParams[$serverParam];
            }
        }

        $this->setClientHints(
            !empty($serverParams['HTTP_SEC_CH_UA_MODEL'])
                ? $serverParams['HTTP_SEC_CH_UA_MODEL']
                : null,
            !empty($serverParams['HTTP_SEC_CH_UA_PLATFORM'])
                ? $serverParams['HTTP_SEC_CH_UA_PLATFORM']
                : null,
            !empty($serverParams['HTTP_SEC_CH_UA_PLATFORM_VERSION'])
                ? $serverParams['HTTP_SEC_CH_UA_PLATFORM_VERSION']
                : null,
            !empty($serverParams['HTTP_SEC_CH_UA_FULL_VERSION_LIST'])
                ? $serverParams['HTTP_SEC_CH_UA_FULL_VERSION_LIST']
                : null,
            !empty($serverParams['HTTP_SEC_CH_UA_FULL_VERSION'])
                ? $serverParams['HTTP_SEC_CH_UA_FULL_VERSION']
                : null
        );
    }

    /**
     * Add an item in the Ecommerce order.
     *
     * This should be called before trackEcommerceOrder(), or before trackEcommerceCartUpdate().
     * This function can be called for all individual products in the cart (or order).
     * SKU parameter is mandatory. Other parameters are optional (set to false if value not known).
     * Ecommerce items added via this function are automatically cleared when trackEcommerceOrder() or
     * getUrlTrackEcommerceOrder() is called.
     *
     * @param string $sku SKU, Product identifier
     * @param string $name Product name
     * @param array|string $category Product category, or array of up to 5 product categories
     * @param float|int $price  Individual product price (supports integer and decimal prices)
     * @param int $quantity Product quantity
     * @return $this
     */
    public function addEcommerceItem(
        string $sku,
        string $name = '',
        array|string $category = '',
        float|int $price = 0,
        int $quantity = 1
    ): self
    {
        $this->ecommerceItems[] = [$sku, $name, $category, $price, $quantity];
        return $this;
    }

    public function disableImageResponse(): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_SEND_IMAGE] = self::FALSE;
        return $new;
    }

    public function doBulkTrack(string $authToken = ''): ?ResponseInterface
    {
        if (empty($this->trackingActions)) {
            return null;
        }

        $data = ['requests' => $this->trackingActions];

        if (!empty($authToken)) {
            $data['token_auth'] = $authToken;
        }

        $this->trackingActions = [];

        return $this
            ->client
            ->post(
                '',
                [
                    RequestOptions::JSON => Utils::jsonEncode($data),
                ]
            )
        ;
    }

    public function disableBulkTracking(): void
    {
        $this->bulkTracking = false;
    }

    public function enableBulkTracking(): void
    {
        $this->bulkTracking = true;
    }

    /**
     * Enable Cookie Creation.
     * This will cause a first party VisitorId cookie to be set when the VisitorId is set or reset
     *
     * @param string $domain First-party cookie domain.
     *  Accepted values: example.com, *.example.com (same as .example.com) or subdomain.example.com
     * @param string $path First-party cookie path
     * @param bool $secure Secure flag for cookies
     * @param bool $httpOnly HTTPOnly flag for cookies
     * @param string $sameSite SameSite flag for cookies
     */
    public function enableCookies(
        string $domain = '',
        string $path = '/',
        bool $secure = false,
        bool $httpOnly = false,
        string $sameSite = ''
    ): void {
        $this->cookieConfig = [
            'Domain' => $domain,
            'Path' => $path,
            'Secure' => $secure,
            'HttpOnly' => $httpOnly,
            'SameSite' => $sameSite
        ];
    }

    public function disableCookies(): void
    {
        $this->cookieConfig = null;
    }

    public function sendPing(): ?ResponseInterface
    {
        $this->trackerParameters[self::PARAMETER_PING] = self::TRUE;
        return $this->sendRequest();
    }

    public function trackAction(string $url, string $type): ?ResponseInterface
    {
        if (!in_array($type, self::ACTIONS)) {
            throw new InvalidArgumentException(self::INVALID_ACTION_EXCEPTION);
        }

        $new = clone $this;
        $new->trackerParameters[$type] = $url;

        return $new->sendRequest();
    }

    public function trackContentImpression(
        string $name,
        string $content,
        ?string $target = null
    ): ?ResponseInterface
    {
        if ($name === '') {
            throw new InvalidArgumentException(
                sprintf(self::PARAMETER_REQUIRED_EXCEPTION, 'Content name')
            );
        }
        if ($content === '') {
            throw new InvalidArgumentException(
                sprintf(self::PARAMETER_REQUIRED_EXCEPTION, 'Content')
            );
        }

        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_CONTENT_NAME] = $name;
        $new->trackerParameters[self::PARAMETER_CONTENT_CONTENT] = $content;

        if ($target !== null) {
            $new->trackerParameters[self::PARAMETER_CONTENT_TARGET] = $target;
        }

        return $new->sendRequest();
    }

    public function trackContentInteraction(
        string $interaction,
        string $name,
        string $content,
        ?string $target = null
    ): ?ResponseInterface
    {
        if ($interaction === '') {
            throw new InvalidArgumentException(
                sprintf(self::PARAMETER_REQUIRED_EXCEPTION, 'Content interaction')
            );
        }
        if ($name === '') {
            throw new InvalidArgumentException(
                sprintf(self::PARAMETER_REQUIRED_EXCEPTION, 'Content name')
            );
        }
        if ($content === '') {
            throw new InvalidArgumentException(
                sprintf(self::PARAMETER_REQUIRED_EXCEPTION, 'Content')
            );
        }

        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_CONTENT_INTERACTION] = $interaction;
        $new->trackerParameters[self::PARAMETER_CONTENT_NAME] = $name;
        $new->trackerParameters[self::PARAMETER_CONTENT_CONTENT] = $content;

        if ($target !== null) {
            $new->trackerParameters[self::PARAMETER_CONTENT_TARGET] = $target;
        }

        return $new->sendRequest();
    }

    public function trackCrash(
        string $message,
        ?string $type = null,
        ?string $category = null,
        ?string $stack = null,
        ?string $location = null,
        ?int $line = null,
        ?int $column = null
    ): ?ResponseInterface
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_CRASH] = self::TRUE;
        $new->trackerParameters[self::PARAMETER_CRASH_MESSAGE] = $message;

        if ($type !== null) {
            $new->trackerParameters[self::PARAMETER_CRASH_TYPE] = $type;
        }
        if ($category !== null) {
            $new->trackerParameters[self::PARAMETER_CRASH_CATEGORY] = $category;
        }
        if ($stack !== null) {
            $new->trackerParameters[self::PARAMETER_CRASH_STACK] = $stack;
        }
        if ($location !== null) {
            $new->trackerParameters[self::PARAMETER_CRASH_LOCATION] = $location;
        }
        if ($line !== null) {
            $new->trackerParameters[self::PARAMETER_CRASH_LINE] = $line;
        }
        if ($column !== null) {
            $new->trackerParameters[self::PARAMETER_CRASH_COLUMN] = $column;
        }

        return $new->sendRequest();
    }

    public function trackEcommerceCartUpdate(float $total): ?ResponseInterface
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_GOAL_ID] = 0;
        $new->trackerParameters[self::PARAMETER_ECOMMERCE_REVENUE] = $total;

        return $new->sendRequest();
    }

    public function trackEcommerceOrder(
        int|string $orderId,
        float $total,
        ?float $subTotal = null,
        ?float $tax = null,
        ?float $shipping = null,
        ?float $discount = null
    ): ?ResponseInterface
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_GOAL_ID] = 0;
        $new->trackerParameters[self::PARAMETER_ECOMMERCE_ORDER_ID] = $orderId;
        $new->trackerParameters[self::PARAMETER_ECOMMERCE_REVENUE] = $total;

        if ($subTotal !== null) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_SUB_TOTAL] = $subTotal;
        }
        if ($tax !== null) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_TAX] = $tax;
        }
        if ($shipping !== null) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_SHIPPING] = $shipping;
        }
        if ($discount !== null) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_DISCOUNT] = $discount;
        }
        if (!empty($this->ecommerceItems)) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_ITEMS] = Utils::jsonEncode($this->ecommerceItems);
            $this->ecommerceItems = [];
        }

        return $new->sendRequest();
    }

    public function trackEvent(
        string $category,
        string $action,
        ?string $name = null,
        float|int|null $value = null
    ): ?ResponseInterface
    {
        if ($category === '') {
            throw new InvalidArgumentException(
                sprintf(self::PARAMETER_REQUIRED_EXCEPTION, 'Event category')
            );
        }
        if ($action === '') {
            throw new InvalidArgumentException(
                sprintf(self::PARAMETER_REQUIRED_EXCEPTION, 'Event action')
            );
        }

        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_EVENT_CATEGORY] = $category;
        $new->trackerParameters[self::PARAMETER_EVENT_ACTION] = $action;

        if ($name !== null) {
            $new->trackerParameters[self::PARAMETER_EVENT_NAME] = $name;
        }
        if ($value !== null) {
            $new->trackerParameters[self::PARAMETER_EVENT_VALUE] = $value;
        }

        return $new->sendRequest();
    }

    public function trackGoal(int $goalId, ?float $revenue): ?ResponseInterface
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_GOAL_ID] = $goalId;

        if ($revenue !== null) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_REVENUE] = $revenue;
        }

        return $new->sendRequest();
    }

    /**
     * https://developer.matomo.org/guides/media-analytics/custom-player#media-analytics-http-tracking-api-reference
     *
     * @param int|string $mediaId Media id
     * @param string $url URL of the media resource
     * @param string $type Type of the media
     * @param string|null $title Media title
     * @param string|null $player Media player
     * @param int|null $timePlayed Time in seconds media has been played
     * @param int|null $length Length/duration of the media in seconds
     * @param int|null $position Current position in the media
     * @param int|null $timeToPlay Time from media being visible to being played
     * @param array|null $resolution Media resolution in pixels as [width, height]
     * @param bool|null $isFullscreen Whether the media is in fullscreen mode
     * @param array|null $positionsPlayed List of positions played
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function trackMedia(
        int|string $mediaId,
        string $url,
        string $type,
        ?string $title,
        ?string $player,
        ?int $timePlayed,
        ?int $length,
        ?int $position,
        ?int $timeToPlay,
        ?array $resolution,
        ?bool $isFullscreen,
        ?array $positionsPlayed
    ): ?ResponseInterface
    {
        if (!in_array($type, self::MEDIA_TYPES)) {
            throw new InvalidArgumentException(self::INVALID_MEDIA_TYPE_EXCEPTION);
        }

        $new = clone $this;
        $this->trackerParameters[self::PARAMETER_MEDIA_ID] = $mediaId;
        $this->trackerParameters[self::PARAMETER_MEDIA_URL] = $url;
        $this->trackerParameters[self::PARAMETER_MEDIA_TYPE] = $type;

        if ($title !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_TITLE] = $title;
        }
        if ($player !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_PLAYER] = $player;
        }
        if ($timePlayed !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_TIME_PLAYED] = $timePlayed;
        }
        if ($length !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_LENGTH] = $length;
        }
        if ($position !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_POSITION] = $position;
        }
        if ($timeToPlay !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_TIME_TO_PLAY] = $timeToPlay;
        }
        if ($resolution !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_WIDTH] = $resolution[0];
            $new->trackerParameters[self::PARAMETER_MEDIA_HEIGHT] = $resolution[1];
        }
        if ($isFullscreen !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_FULL_SCREEN] = (int)$isFullscreen;
        }
        if ($positionsPlayed !== null) {
            $new->trackerParameters[self::PARAMETER_MEDIA_POSITIONS_PLAYED] = implode(',', $positionsPlayed);
        }

        return $new->sendRequest();
    }

    public function trackPageView(string $title): ?ResponseInterface
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_ACTION_NAME] = $title;
        $new->trackerParameters[self::PARAMETER_PAGE_VIEW_ID] = substr(
            bin2hex(random_bytes((int)ceil(3))),
            0,
            6
        );

        return $new->sendRequest();
    }

    public function trackPhpThrowable(\Throwable $throwable, ?string $category = null): ?ResponseInterface
    {
        return $this->trackCrash(
            $throwable->getMessage(),
            get_class($throwable),
            $category,
            $throwable->getTraceAsString(),
            $throwable->getFile(),
            $throwable->getLine()
        );
    }

    public function trackTrackSiteSearch(string $keyword, ?string $category, ?int $resultCount): ?ResponseInterface
    {

        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_SEARCH_KEYWORD] = $keyword;

        if ($category !== null) {
            $new->trackerParameters[self::PARAMETER_SEARCH_CATEGORY] = $category;
        }
        if ($resultCount !== null) {
            $new->trackerParameters[self::PARAMETER_SEARCH_COUNT] = abs($resultCount);
        }

        return $new->sendRequest();
    }

    public function withAttribution(string $name, string $keyword, int $timestamp, string $url): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_ATTRIBUTION_CAMPAIGN_NAME] = $name;
        $new->trackerParameters[self::PARAMETER_ATTRIBUTION_CAMPAIGN_KEYWORD] = $keyword;
        $new->trackerParameters[self::PARAMETER_ATTRIBUTION_TIMESTAMP] = $timestamp;
        $new->trackerParameters[self::PARAMETER_ATTRIBUTION_URL] = $url;
        return $new;
    }

    public function withAuthToken(string $authToken): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_AUTH_TOKEN] = $authToken;
        return $new;
    }

    public function withBrowserCookies(bool $cookies): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_COOKIES] = $cookies;
        return $new;
    }

    public function withCharset(string $charset): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_CHARSET] = $charset;
        return $new;
    }

    public function withCity(string $city): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_LOCATION_CITY] = $city;
        return $new;
    }

    /**
     * Sets the client hints, used to detect OS and browser.
     * If this function is not called, the client hints sent with the current request will be used.
     *
     * @param ?string $model  Value of the header 'HTTP_SEC_CH_UA_MODEL'
     * @param ?string $platform  Value of the header 'HTTP_SEC_CH_UA_PLATFORM'
     * @param ?string $platformVersion  Value of the header 'HTTP_SEC_CH_UA_PLATFORM_VERSION'
     * @param string|array|null $fullVersionList Value of header 'HTTP_SEC_CH_UA_FULL_VERSION_LIST' or an array
     * containing all brands with the structure [['brand' => 'Chrome', 'version' => '10.0.2'], ['brand' => '...]
     * @param ?string $uaFullVersion  Value of the header 'HTTP_SEC_CH_UA_FULL_VERSION'
     */
    public function withClientHints(
        ?string $model = null,
        ?string $platform = null,
        ?string $platformVersion = null,
        array|string|null $fullVersionList = null,
        ?string $uaFullVersion = null
    ): self
    {
        $new = $this;
        $new->setClientHints($model, $platform, $platformVersion, $fullVersionList, $uaFullVersion);
        return $new;
    }

    public function withCountry(string $country): self
    {
        if (strlen($country) !== 2) {
            throw new InvalidArgumentException(self::INVALID_COUNTRY_EXCEPTION);
        }

        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_LOCATION_COUNTRY] = strtolower($country);
        return $new;
    }

    public function withCustomDimension(int $id, string $value): self
    {
        if ($id < self::MIN_CUSTOM_DIMENSION_ID || $id > self::MAX_CUSTOM_DIMENSION_ID ) {
            throw new InvalidArgumentException(self::INVALID_CUSTOM_DIMENSION_ID_EXCEPTION);
        }

        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_CUSTOM_DIMENSION . $id] = $value;
        return $new;
    }

    public function withDatetime(string $datetime): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_DATETIME] = $datetime;
        return $new;
    }

    /**
     * Sets the current page view as an item (product) page view, or an Ecommerce Category page view.
     *
     * This must be called before doTrackPageView() on this product/category page.
     *
     * On a category page, you may set the parameter $category only and set the other parameters to false.
     *
     * Tracking Product/Category page views will allow Matomo to report on Product & Categories
     * conversion rates (Conversion rate = Ecommerce orders containing this product or category / Visits to the product or category)
     *
     * @param string $sku Product SKU being viewed
     * @param string $name Product Name being viewed
     * @param string|array $category Category being viewed. On a Product page, this is the product's category.
     *                                You can also specify an array of up to 5 categories for a given page view.
     * @param float $price Specify the price at which the item was displayed
     * @return $this
     */
    public function withEcommerceView(
        string $sku = '',
        string $name = '',
        string|array $category = '',
        float $price = 0.0
    ): self
    {
        $new = clone $this;

        if (!empty($category)) {
            if (is_array($category)) {
                $category = Utils::jsonEncode($category);
            }
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_CATEGORY] = $category;
        }

        if (!empty($price)) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_PRICE] = $price;
        }

        // On a category page, do not record "Product name not defined"
        if (empty($sku) && empty($name)) {
            return $new;
        }
        if (!empty($sku)) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_SKU] = $sku;
        }
        if (!empty($name)) {
            $new->trackerParameters[self::PARAMETER_ECOMMERCE_NAME] = $name;
        }

        return $new;
    }

    public function withIp(string $ip): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_IP] = $ip;
        return $new;
    }

    public function withLanguage(string $language): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_LANGUAGE] = $language;
        return $new;
    }

    public function withLatLong(float $latitude, float $longitude): self
    {
        if ($latitude > 90 || $latitude < -90) {
            throw new InvalidArgumentException(self::INVALID_LATITUDE_EXCEPTION);
        }

        if ($longitude > 180 || $longitude < -180) {
            throw new InvalidArgumentException(self::INVALID_LONGITUDE_EXCEPTION);
        }

        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_LOCATION_LATITUDE] = $latitude;
        $new->trackerParameters[self::PARAMETER_LOCATION_LONGITUDE] = $longitude;
        return $new;
    }

    public function withNewVisit(): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_NEW_VISIT] = self::TRUE;
        return $new;
    }

    /**
     * Sets timings for various browser performance metrics.
     * @see https://developer.mozilla.org/en-US/docs/Web/API/PerformanceTiming
     *
     * @param ?int $networkTime Network time in ms (connectEnd – fetchStart)
     * @param ?int $serverTime Server time in ms (responseStart – requestStart)
     * @param ?int $transferTime Transfer time in ms (responseEnd – responseStart)
     * @param ?int $domProcessingTime DOM Processing to Interactive time in ms (domInteractive – domLoading)
     * @param ?int $domCompletionTime DOM Interactive to Complete time in ms (domComplete – domInteractive)
     * @param ?int $onLoadTime Onload time in ms (loadEventEnd – loadEventStart)
     * @return $this
     */
    public function withPerformanceTimings(
        ?int $networkTime = null,
        ?int $serverTime = null,
        ?int $transferTime = null,
        ?int $domProcessingTime = null,
        ?int $domCompletionTime = null,
        ?int $onLoadTime = null
    ): self
    {
        $new = clone $this;

        if ($networkTime !== null) {
            $this->trackerParameters[self::PARAMETER_PAGE_PERFORMANCE_NETWORK] = $networkTime;
        }
        if ($serverTime !== null) {
            $this->trackerParameters[self::PARAMETER_PAGE_PERFORMANCE_SERVER] = $serverTime;
        }
        if ($transferTime !== null) {
            $this->trackerParameters[self::PARAMETER_PAGE_PERFORMANCE_TRANSFER] = $transferTime;
        }
        if ($domProcessingTime !== null) {
            $this->trackerParameters[self::PARAMETER_PAGE_PERFORMANCE_DOM_PROCESSING] = $domProcessingTime;
        }
        if ($domCompletionTime !== null) {
            $this->trackerParameters[self::PARAMETER_PAGE_PERFORMANCE_DOM_COMPLETION] = $domCompletionTime;
        }
        if ($onLoadTime !== null) {
            $this->trackerParameters[self::PARAMETER_PAGE_PERFORMANCE_ON_LOAD] = $onLoadTime;
        }

        return $new;
    }

    public function withPlugins(
        bool $flash = false,
        bool $java = false,
        bool $pdf = false,
        bool $quickTime = false,
        bool $realPlayer = false,
        bool $silverlight = false,
        bool $windowsMedia = false
    ): self
    {
        $new = clone $this;
        foreach ([
             self::PLUGIN_FLASH => self::PARAMETER_PLUGIN_FLASH,
             self::PLUGIN_JAVA => self::PARAMETER_PLUGIN_JAVA,
             self::PLUGIN_PDF => self::PARAMETER_PLUGIN_PDF,
             self::PLUGIN_QUICKTIME => self::PARAMETER_PLUGIN_QUICKTIME,
             self::PLUGIN_REAL_PLAYER => self::PARAMETER_PLUGIN_REAL_PLAYER,
             self::PLUGIN_SILVERLIGHT => self::PARAMETER_PLUGIN_SILVERLIGHT,
             self::PLUGIN_WINDOWS_MEDIA => self::PARAMETER_PLUGIN_WINDOWS_MEDIA,
        ] as $plugin => $trackingParam) {
            if ($$plugin) {
                $this->trackerParameters[$trackingParam] = $$plugin;
            }
        }
        return $new;
    }

    public function withRegion(string $region): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_LOCATION_REGION] = $region;
        return $new;
    }

    public function withResolution(int $width, int $height): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_RESOLUTION] = $width . 'x' . $height;
        return $new;
    }

    public function withUserAgent(string $userAgent): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_USER_AGENT] = $userAgent;
        return $new;
    }

    public function withUserId(string $userId): self
    {
        $new = clone $this;
        $new->trackerParameters[self::PARAMETER_USER_ID] = $userId;
        return $new;
    }

    private function sendRequest(bool $force = false): ?ResponseInterface
    {
        $this->trackerParameters[self::PARAMETER_RANDOM] = mt_rand();

        $now = getdate();
        $this->trackerParameters[self::PARAMETER_TIME_HOUR] = $now['hours'];
        $this->trackerParameters[self::PARAMETER_TIME_MINUTE] = $now['minutes'];
        $this->trackerParameters[self::PARAMETER_TIME_SECOND] = $now['seconds'];
        $this->trackerParameters[self::PARAMETER_TIMESTAMP] = $now[0];

        if ($this->bulkTracking && !$force) {
            $this->trackingActions[] = $this->trackerParameters;
            return null;
        }

        $this->setFirstPartyCookies();

        return $this
            ->client
            ->post(
                '',
                [
                    RequestOptions::QUERY => $this->trackerParameters,
                ]
            )
        ;
    }

    private function setClientHints(
        ?string $model = null,
        ?string $platform = null,
        ?string $platformVersion = null,
        array|string|null $fullVersionList = null,
        ?string $uaFullVersion = null
    ): void
    {
        if (is_string($fullVersionList)) {
            $reg  = '/^"([^"]+)"; ?v="([^"]+)"(?:, )?/';
            $list = [];

            while (\preg_match($reg, $fullVersionList, $matches)) {
                $list[] = ['brand' => $matches[1], 'version' => $matches[2]];
                $fullVersionList  = \substr($fullVersionList, strlen($matches[0]));
            }

            $fullVersionList = $list;
        } elseif (!is_array($fullVersionList)) {
            $fullVersionList = [];
        }

        $this->trackerParameters[self::PARAMETER_USER_AGENT_DATA] = Utils::jsonEncode(array_filter([
            'model' => $model,
            'platform' => $platform,
            'platformVersion' => $platformVersion,
            'fullVersionList' => $fullVersionList,
            'uaFullVersion' => $uaFullVersion,
        ]));
    }

    /**
     * All cookies are supported: 'id' and 'ses' and 'ref' and 'cvar' cookies.
     */
    private function setFirstPartyCookies(): void
    {
        if ($this->cookieConfig !== null) {
            if (empty($this->cookieVisitorId)) {
                $this->loadVisitorIdCookie();
            }

            // Set the 'ref' cookie
            $attributionInfo = $this->getAttributionInfo();
            if (!empty($attributionInfo)) {
                $this->setCookie(
                    self::COOKIE_REF,
                    $attributionInfo,
                    self::REFERRAL_COOKIE_TIMEOUT
                );
            }

            // Set the 'ses' cookie
            $this->setCookie(self::COOKIE_SESSION, '*', self::SESSION_COOKIE_TIMEOUT);

            // Set the 'id' cookie
            $cookieValue = implode('.', [
                $this->getVisitorId(),
                $this->trackerParameters[self::PARAMETER_TIMESTAMP],
                $this->visitCount + 1,
                $this->trackerParameters[self::PARAMETER_TIMESTAMP],
                $this->lastVisitTs,
                $this->ecommerceLastOrderTimestamp
           ]);
            $this->setCookie(self::COOKIE_ID, $cookieValue, self::VISITOR_COOKIE_TIMEOUT);

            // Set the 'cvar' cookie
            $this->setCookie(
                self::COOKIE_CVAR,
                Utils::jsonEncode($this->visitorCustomVar),
                self::SESSION_COOKIE_TIMEOUT
            );
        }
    }

    /**
     * Sets a first party cookie to the client to improve dual JS-PHP tracking.
     *
     * @param string $cookieName
     * @param string $cookieValue
     * @param int $cookieTTL
     */
    private function setCookie(string $cookieName, string $cookieValue, int $cookieTTL): void
    {
        $this
            ->cookieJar
            ->setCookie(new SetCookie([
                'Name' => $this->cookieName($cookieName),
                'Value' => $cookieValue,
                'Domain' => $this->cookieConfig['Domain'],
                'Path' => $this->cookieConfig['Path'],
                'Expires' => $this->trackerParameters[self::PARAMETER_TIMESTAMP] + $cookieTTL,
                'Secure' => $this->cookieConfig['Secure'],
                'HttpOnly' => $this->cookieConfig['HttpOnly'],
            ]))
        ;
    }

    /**
     * Return cookie name with prefix and domain hash.
     *
     * @param string $cookieName
     * @return string
     */
    private function cookieName(string $cookieName): string
    {
        return self::FIRST_PARTY_COOKIES_PREFIX
            . $cookieName
            .  '.'
            . $this->trackerParameters[self::PARAMETER_SITE_ID]
            . '.'
            . substr(
                sha1(
                    ($this->cookieConfig['Domain'] === ''
                        ? $this->request->getServerParams()['HTTP_HOST']
                        : $this->cookieConfig['Domain']
                    )
                    . $this->cookieConfig['Path']
                ),
                0,
                4
            )
        ;
    }

    /**
     * Loads values from the VisitorId Cookie
     *
     * @return bool True if cookie exists and is valid, False otherwise
     */
    private function loadVisitorIdCookie(): bool
    {
        $cookie = $this->getCookieByName('id');
        if ($cookie === null) {
            return false;
        }

        $parts = explode('.', $cookie->getValue());
        if (strlen($parts[0]) !== self::VISITOR_ID_LENGTH) {
            return false;
        }

        /* $this->cookieVisitorId provides backward compatibility since getVisitorId()
        didn't change any existing VisitorId value
        $this->cookieVisitorId = $parts[0];
        $this->createTs = $parts[1];
        $this->visitCount = (int)$parts[2];
        $this->currentVisitTs = $parts[3];
        $this->lastVisitTs = $parts[4];
        if (isset($parts[5])) {
            $this->ecommerceLastOrderTimestamp = $parts[5];
        }
        */

        return true;
    }

    private function getCookieByName(string $name): ?SetCookie
    {
        if ($this->requestCookieJar === null) {
            $this->requestCookieJar = $this
                ->request
                ->getCookieParams()
            ;
        }

        return $this
            ->requestCookieJar
            ->getCookieByName($name)
        ;
    }
}
