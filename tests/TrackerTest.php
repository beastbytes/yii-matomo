<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Yii\Matomo\Tests;

use BeastBytes\Yii\Matomo\Tracker;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class TrackerTest extends TestCase
{
    private const API_URL = 'https://example.com';
    private const SITE_ID = 1;

    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker = new Tracker(
            new ServerRequest( 'GET', ''),
            self::SITE_ID,
            self::API_URL
        );
    }

    public function testSomething()
    {

    }
}
