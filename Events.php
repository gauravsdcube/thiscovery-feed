<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed;

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\thiscoveryFeed\services\FeedShareService;
use humhub\modules\ui\menu\MenuLink;
use humhub\widgets\TopMenu;
use Yii;
use yii\base\ActionEvent;
use yii\base\ViewEvent;
use yii\helpers\Url;

class Events
{
    public static function onAdminMenuInit($event): void
    {
        /** @var AdminMenu $menu */
        $menu = $event->sender;

        $module = self::module();
        if ($module === null || !$module->canManage()) {
            return;
        }

        $menu->addEntry(new MenuLink([
            'id' => 'thiscovery-feed',
            'label' => Yii::t('ThiscoveryFeedModule.base', 'Feed'),
            'url' => ['/thiscovery-feed/admin/index'],
            'icon' => 'rss',
            'sortOrder' => 320,
            'isActive' => ControllerHelper::isActivePath('thiscovery-feed', 'admin'),
        ]));
    }

    public static function onTopMenuInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $module = self::module();
        if ($module === null) {
            return;
        }

        /** @var TopMenu $topMenu */
        $topMenu = $event->sender;

        $topMenu->addEntry(new MenuLink([
            'id' => 'thiscovery-feed',
            'label' => Yii::t('ThiscoveryFeedModule.base', 'Feed'),
            'url' => ['/thiscovery-feed/feed/index'],
            'icon' => 'rss',
            'sortOrder' => 90,
            'isActive' => ControllerHelper::isActivePath('thiscovery-feed', 'feed'),
        ]));

        if (!$module->replacesDashboard()) {
            return;
        }

        $dashboard = $topMenu->getEntryById('dashboard');
        if ($dashboard !== null) {
            $topMenu->removeEntry($dashboard);
        }
    }

    public static function onApplicationBeforeAction(ActionEvent $event): void
    {
        if (Yii::$app->request->isConsoleRequest || Yii::$app->user->isGuest) {
            return;
        }

        $module = self::module();
        if ($module === null || !$module->replacesDashboard()) {
            return;
        }

        $dashboardHome = Url::to(['/dashboard/dashboard']);
        if (Yii::$app->homeUrl === $dashboardHome) {
            Yii::$app->homeUrl = Url::to(['/thiscovery-feed/feed/index']);
        }

        $action = $event->action;
        $controller = $action?->controller;
        if ($controller === null) {
            return;
        }

        $route = $controller->module->id . '/' . $controller->id . '/' . $action->id;
        if ($route !== 'dashboard/dashboard/index') {
            return;
        }

        $event->isValid = false;
        Yii::$app->response->redirect(Url::to(['/thiscovery-feed/feed/index']))->send();
        Yii::$app->end();
    }

    public static function onViewAfterRender(ViewEvent $event): void
    {
        $file = str_replace('\\', '/', (string) $event->viewFile);
        if (!str_ends_with($file, 'widgets/stream/views/wallStreamEntryHeader.php')) {
            return;
        }
        if (!self::labelsSharedSpaces()) {
            return;
        }

        $model = $event->params['model'] ?? null;
        if (!$model instanceof ContentActiveRecord) {
            return;
        }

        $links = FeedShareService::headerLinks($model->content);
        if ($links === null) {
            return;
        }

        $count = 0;
        $output = preg_replace(
            '/<a\b[^>]*\bwall-entry-container-link\b[^>]*>.*?<\/a>/s',
            $links,
            $event->output,
            1,
            $count,
        );
        if ($count === 1 && is_string($output)) {
            $event->output = $output;
            return;
        }

        $withNames = preg_replace('/<\/h5>/', $links . '</h5>', $event->output, 1, $count);
        if ($count === 1 && is_string($withNames)) {
            $event->output = $withNames;
        }
    }

    private static function labelsSharedSpaces(): bool
    {
        $controller = Yii::$app->controller;
        if ($controller === null || $controller->module === null) {
            return false;
        }
        if ($controller->module->id === 'thiscovery-feed') {
            return true;
        }

        $viewContext = Yii::$app->request->get('viewContext', Yii::$app->request->post('viewContext'));

        return $controller->module->id === 'content'
            && $controller->id === 'content'
            && $controller->action?->id === 'reload'
            && $viewContext === 'dashboard';
    }

    private static function module(): ?Module
    {
        $module = Yii::$app->getModule('thiscovery-feed');

        return $module instanceof Module ? $module : null;
    }
}
