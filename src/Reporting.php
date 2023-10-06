<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

namespace BeastBytes\Yii\Matomo;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * Integrates the {@link https://developer.matomo.org/api-reference/reporting-api Matomo Reporting API}
 */
final class Reporting
{
    public const DATE_LAST = 'last%d';
    public const DATE_PREVIOUS = 'previous%d';
    public const DATE_TODAY = 'today';
    public const DATE_YESTERDAY = 'yesterday';

    public const FORMAT_XML = 'xml';
    public const FORMAT_JSON = 'json';
    public const FORMAT_CSV = 'csv';
    public const FORMAT_TSV = 'tsv';
    public const FORMAT_HTML = 'html';
    public const FORMAT_RSS = 'rss';
    public const FORMAT_ORIGINAL = 'original';

    public const INVALID_FORMAT_EXCEPTION = 'Invalid format: `%s`';
    public const INVALID_PERIOD_EXCEPTION = 'Invalid period: `%s`';

    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_YEAR = 'year';
    public const PERIOD_RANGE = 'range';

    private const FORMATS = [
        self::FORMAT_XML,
        self::FORMAT_JSON,
        self::FORMAT_CSV,
        self::FORMAT_TSV,
        self::FORMAT_HTML,
        self::FORMAT_RSS,
        self::FORMAT_ORIGINAL,
    ];
    private const PERIODS = [
        self::PERIOD_DAY,
        self::PERIOD_WEEK,
        self::PERIOD_MONTH,
        self::PERIOD_YEAR,
        self::PERIOD_RANGE,
    ];
    private const HTTP_STATUS_OK = 200;
    private const MODULE_API = 'API';

    private ?ResponseInterface $response = null;
    private array $parameters;

    /**
     * @param string $authToken Matomo Auth token - can be found on the API page of the Matomo interface
     * @param int $siteId Matomo site id
     */
    public function __construct(protected string $uri, protected string $authToken, int $siteId)
    {
        $this->parameters = [
            'idSite' => $siteId,
            'module' => self::MODULE_API,
        ];
    }

    /**
     * Fetch the report
     *
     * @return bool|string Report content or false on error
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch(): bool|string
    {
        $this->response = (new Client())
            ->post(
                $this->uri,
                [
                    RequestOptions::FORM_PARAMS => ['token_auth' => $this->authToken],
                    RequestOptions::QUERY => $this->parameters,
                ]
            )
        ;

        if (
            $this
                ->response
                ->getStatusCode()
            === self::HTTP_STATUS_OK
        ) {
            return $this
                ->response
                ->getBody()
                ->getContents()
            ;
        }

        return false;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function withDate(string $date, string $endDate = ''): self
    {
        if ($endDate !== '') {
            $date .= ',' . $endDate;
        }

        return $this->withParameter('date', $date);
    }

    public function withFilterLimit(string $filterLimit): self
    {
        return $this->withParameter('filter_limit', $filterLimit);
    }

    public function withFormat(string $format): self
    {
        if (!in_array($format, self::FORMATS)) {
            throw new InvalidArgumentException(sprintf(self::INVALID_FORMAT_EXCEPTION, $format));
        }

        return $this->withParameter('format', $format);
    }

    public function withMethod(string $method): self
    {
        return $this->withParameter('method', $method);
    }

    public function withParameter(string $name, array|bool|int|float|string $value): self
    {
        $new = clone $this;

        if (is_array($value)) {
            /**
             * @var int $k
             * @var bool|int|float|string $v
             */
            foreach ($value as $k => $v) {
                $new = $new->withParameter($name . '[' . (string)$k . ']', $v);
            }
        } else {
            $new->parameters[$name] = $value;
        }

        return $new;
    }

    /**
     * @psalm-param non-empty-array<string, array|bool|int|float|string> $parameters
     */
    public function withParameters(array $parameters): self
    {
        foreach ($parameters as $name => $parameter) {
            $new = $this->withParameter($name, $parameter);
        }

        return $new;
    }

    public function withPeriod(string $period): self
    {
        if (!in_array($period, self::PERIODS)) {
            throw new InvalidArgumentException(sprintf(self::INVALID_PERIOD_EXCEPTION, $period));
        }

        return  $this->withParameter('period', $period);
    }

    public function withSegment(string $segment): self
    {
        return $this->withParameter('segment', $segment);
    }
}
