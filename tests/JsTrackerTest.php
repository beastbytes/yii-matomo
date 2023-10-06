<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Yii\Matomo\Tests;

use BeastBytes\Yii\Matomo\events\BeforeRender as BeforeRenderHandler;
use BeastBytes\Yii\Matomo\JsTracker;
use BeastBytes\Yii\Matomo\Tests\Support\TestHelper;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Event\WebView\BeforeRender;

class JsTrackerTest extends TestCase
{
    private const API_URL = 'tracker.example.com';
    private const SITE_ID = 1;
    private const MATOMO_SCRIPT = "const _paq=window._paq=window._paq||[];_paq.push(['trackPageView']);_paq.push(['enableLinkTracking']);(function(){let u='//" . self::API_URL . "/';_paq.push(['setTrackerUrl',u+'matomo.php']);_paq.push(['setSiteId'," . self::SITE_ID . "]);let d=document,g=d.createElement('script'),s=d.getElementsByTagName('script')[0];g.type='text/javascript';g.async=true;g.src=u+'matomo.js';s.parentNode.insertBefore(g,s);})();";
    private const IMAGE_TRACKER = '<noscript><p><img src="//'
        . self::API_URL
        . '/matomo.php?idsite='
        . self::SITE_ID
        . '" style="border:0;" alt=""/></p></noscript>';

    private SimpleEventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = new SimpleEventDispatcher(
            function (mixed $event) {
                if ($event instanceof BeforeRender) {
                    BeforeRenderHandler::registerJs($event);
                    BeforeRenderHandler::imageTracker($event);
                }
            }
        );
    }

    public function testBasicMatomoScript(): void
    {
        $tracker = new JsTracker(self::API_URL, self::SITE_ID);
        $html = $this->getHtml($tracker);
        $this->assertStringContainsString(self::MATOMO_SCRIPT, $html);
        $this->assertStringNotContainsString(self::IMAGE_TRACKER, $html);
    }

    #[DataProvider('functionProvider')]
    public function testAddFunctions(array $functions, string $expected): void
    {
        $tracker = new JsTracker(self::API_URL, self::SITE_ID);

        foreach ($functions as $function) {
            $tracker = $tracker->addFunction($function['name'], $function['parameters']);
        }

        $html = $this->getHtml($tracker);
        $this->assertStringContainsString(self::MATOMO_SCRIPT, $html);
        $this->assertStringContainsString($expected, $html);
    }

    public function testImageTracker(): void
    {
        $tracker = new JsTracker(self::API_URL, self::SITE_ID, true);
        $html = $this->getHtml($tracker);
        $this->assertStringContainsString(self::MATOMO_SCRIPT, $html);
        $this->assertStringContainsString(self::IMAGE_TRACKER, $html);
    }

    private function getHtml(JsTracker $tracker): string
    {
        return TestHelper::createWebView($this->eventDispatcher)
            ->render('/layout.php', ['content' => 'content', 'tracker' => $tracker])
        ;
    }

    public static function functionProvider(): Generator
    {
        foreach([
            'setDocumentTitle' => [
                'functions' => [
                    ['name' => 'setDocumentTitle', 'parameters' => 'Test Title'],
                ],
                'expected' => '_paq.push(["setDocumentTitle","Test Title"]);'
            ],
            'trackGoal with scalar parameters' => [
                'functions' => [
                    ['name' => 'trackGoal', 'parameters' => [22, 99.99]],
                ],
                'expected' =>'_paq.push(["trackGoal",22,99.99]);'
            ],
            'trackGoal with array' => [
                'functions' => [
                    [
                        'name' => 'trackGoal',
                        'parameters' => [
                            ['goalId' => 22, 'revenue' => 99.99],
                            ['goalId', 'revenue']
                        ]
                    ],
                ],
                'expected' =>'_paq.push(["trackGoal",22,99.99]);'
            ],
            'trackGoal with object' => [
                'functions' => [
                    [
                        'name' => 'trackGoal',
                        'parameters' => [
                            (object)['goalId' => 22, 'revenue' => 99.99],
                            ['goalId', 'revenue']
                        ]
                    ],
                ],
                'expected' =>'_paq.push(["trackGoal",22,99.99]);'
            ],
            'setDocumentTitle & trackGoal' => [
                'functions' => [
                    ['name' => 'setDocumentTitle', 'parameters' => 'Test Title'],
                    ['name' => 'trackGoal', 'parameters' => [22, 99.99]],
                ],
                'expected' => '_paq.push(["setDocumentTitle","Test Title"]);_paq.push(["trackGoal",22,99.99]);'
            ],
        ] as $name => $functions) {
            yield $name => $functions;
        }
    }
}
