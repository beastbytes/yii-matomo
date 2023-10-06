<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

use BeastBytes\Yii\Matomo\JsTracker;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\View\Event\WebView\BodyBegin;

return [
    BeforeRender::class => [
        [JsTracker::class, 'registerJs']
    ],
    BodyBegin::class => [
        [JsTracker::class, 'imageTracker']
    ],
];
