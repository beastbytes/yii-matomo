<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Yii\Matomo\events;

use BeastBytes\Yii\Matomo\JsTracker;
use Yiisoft\View\WebView;
use Yiisoft\View\Event\WebView\BeforeRender as Event;

final class BeforeRender
{
    public const BLOCK_ID = 'matomoImageTracker';

    /**
     * @var string Matomo image tracking HTML
     */
    private const IMG = '<noscript><p><img src="//%s/%s?idsite=%d" style="border:0;" alt=""/></p></noscript>';

    /**
     * @var string Matomo tracking Javascript
     */
    private const JS = "const _paq=window._paq=window._paq||[];_paq.push(['trackPageView']);_paq.push(['enableLinkTracking']);(function(){let u='//%s/';_paq.push(['setTrackerUrl',u+'%s']);_paq.push(['setSiteId',%d]);let d=document,g=d.createElement('script'),s=d.getElementsByTagName('script')[0];g.type='text/javascript';g.async=true;g.src=u+'%s';s.parentNode.insertBefore(g,s);})();";

    /**
     * Renders image tracker HTML
     */
    public static function imageTracker(Event $event): void
    {
        $parameters = $event->getParameters();
        /** @var JsTracker $tracker */
        $tracker = $parameters['tracker'];

        if ($tracker->isImageTrackingEnabled()) {
            $event
                ->getView()
                ->setBlock(
                    self::BLOCK_ID,
                    sprintf(self::IMG, $tracker->getUrl(), $tracker->getPhpFile(), $tracker->getSiteId())
                )
            ;
        }
    }

    /**
     * Registers tracking Javascript
     *
     * @internal **DO NOT** call this method; it is attached to the BeforeRender event
     */
    public static function registerJs(Event $event): void
    {
        $parameters = $event->getParameters();
        /** @var JsTracker $tracker */
        $tracker = $parameters['tracker'];
        $view = $event->getView();
        $view
            ->registerJs(
                sprintf(
                    self::JS,
                    $tracker->getUrl(),
                    $tracker->getPhpFile(),
                    $tracker->getSiteId(),
                    $tracker->getJsFile()
                ),
                WebView::POSITION_HEAD
            )
        ;

        $functions = $tracker->getFunctions();
        if (!empty($functions)) {
            $view
                ->registerJs(
                    '_paq.push(' . implode(');_paq.push(', $functions) . ');',
                    WebView::POSITION_HEAD
                )
            ;
        }
    }
}
