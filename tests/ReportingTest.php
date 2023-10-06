<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Yii\Matomo\Tests;

use BeastBytes\Yii\Matomo\Reporting;
use DateInterval;
use DateTimeImmutable;
use GuzzleHttp\Utils;
use PHPUnit\Framework\TestCase;

class ReportingTest extends TestCase
{
    private const METHOD_API_GET_MATOMO_VERSION = 'API.getMatomoVersion';
    private const METHOD_VISITS_SUMMARY_GET_VISITS = 'VisitsSummary.getVisits';
    private const TEST_SITE_ID = 1;
    private const TEST_TOKEN = 'anonymous';
    private const TEST_URL = 'https://demo.matomo.cloud/';

    private static Reporting $matomo;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        self::$matomo = (new Reporting(self::TEST_URL, self::TEST_TOKEN, self::TEST_SITE_ID))
            ->withFormat(Reporting::FORMAT_JSON)
        ;
    }

    public function testSimpleApiCall(): void
    {
        $json = self::$matomo
            ->withMethod(self::METHOD_API_GET_MATOMO_VERSION)
            ->fetch()
        ;

        $result = Utils::jsonDecode($json, true);

        $this->assertCount(1, $result);
        $this->assertMatchesRegularExpression('/^\d+(\.\d+){2}(-\w+\d+)?/', $result['value']);
    }

    public function testImmutability(): void
    {
        $dayMatomo = self::$matomo
            ->withMethod(self::METHOD_VISITS_SUMMARY_GET_VISITS)
            ->withPeriod(Reporting::PERIOD_DAY)
            ->withDate(sprintf(Reporting::DATE_PREVIOUS, 2))
        ;

        $weekMatomo = $dayMatomo
            ->withPeriod(Reporting::PERIOD_WEEK)
            ->withDate(sprintf(Reporting::DATE_PREVIOUS, 1))
        ;

        $this->assertNotSame($dayMatomo, $weekMatomo);

        /** @var array $dayResult */
        $dayResult = Utils::jsonDecode($dayMatomo->fetch(), true);
        /** @var array $weekResult */
        $weekResult = Utils::jsonDecode($weekMatomo->fetch(), true);

        $this->assertNotSame($dayResult, $weekResult);
        $this->assertCount(2, $dayResult);
        $this->assertCount(1, $weekResult);
    }

    public function testDateRange(): void
    {
        $date = new DateTimeImmutable();

        $json = self::$matomo
            ->withMethod(self::METHOD_VISITS_SUMMARY_GET_VISITS)
            ->withPeriod(Reporting::PERIOD_RANGE)
            ->withDate(
                $date
                    ->sub(new DateInterval('P8D'))
                    ->format('Y-m-d'),
                $date
                    ->sub(new DateInterval('P1D'))
                    ->format('Y-m-d')
            )
            ->fetch()
        ;

        $result = Utils::jsonDecode($json, true);

        $this->assertCount(1, $result);
        $this->assertIsInt($result['value']);
    }
}
