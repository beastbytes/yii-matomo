<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Yii\Matomo\Tests\Support;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\View;
use Yiisoft\View\WebView;

use function dirname;

final class TestHelper
{
    public static function touch(string $path): void
    {
        FileHelper::ensureDirectory(dirname($path));
        touch($path);
    }

    public static function createView(?EventDispatcherInterface $eventDispatcher = null): View
    {
        return new View(
            dirname(__DIR__) . '/assets/view',
            $eventDispatcher ?? new SimpleEventDispatcher(),
        );
    }

    public static function createWebView(?EventDispatcherInterface $eventDispatcher = null): WebView
    {
        return new WebView(
            dirname(__DIR__) . '/assets/view',
            $eventDispatcher ?? new SimpleEventDispatcher(),
        );
    }
}
