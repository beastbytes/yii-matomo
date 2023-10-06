<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $content string
 */

use BeastBytes\Yii\Matomo\events\BeforeRender;
use BeastBytes\Yii\Matomo\JsTracker;

?>
<?php $this->beginPage(); ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test</title>
        <?php $this->head(); ?>
    </head>
    <body>
    <?php $this->beginBody(); ?>

    <?= $content ?>

    <?php if ($this->hasBlock(BeforeRender::BLOCK_ID)): ?>
        <?= $this->getBlock(BeforeRender::BLOCK_ID) ?>
    <?php endif; ?>
    <?php $this->endBody(); ?>
    </body>
    </html>
<?php $this->endPage();
