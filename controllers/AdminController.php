<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\thiscoveryFeed\models\SettingsForm;
use humhub\modules\thiscoveryFeed\Module;
use humhub\modules\thiscoveryFeed\permissions\ManageFeed;
use humhub\modules\thiscoveryFeed\services\LayoutService;
use humhub\modules\thiscoveryFeed\services\WidgetCatalog;
use Yii;

class AdminController extends Controller
{
    public $adminOnly = false;

    protected function getAccessRules()
    {
        return [
            ['permissions' => [
                ManageFeed::class,
                ManageModules::class,
            ]],
        ];
    }

    public function actionIndex()
    {
        /** @var Module $module */
        $module = $this->module;
        $layout = $module->adminLayout();

        $model = new SettingsForm([
            'placement' => $module->replacesDashboard() ? Module::PLACEMENT_REPLACE : Module::PLACEMENT_ALONGSIDE,
            'announcementLimit' => $module->announcementLimit(),
            'widgets' => array_merge($layout['left'], $layout['right']),
        ]);

        if (Yii::$app->request->isPost) {
            $model->widgets = [];
            $model->order = [];
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $widgets = is_array($model->widgets) ? $model->widgets : [];
            $order = is_array($model->order) ? $model->order : [];
            $selected = array_values(array_filter($widgets, 'is_string'));
            $saved = LayoutService::fromSelection($selected, $order);
            $module->settings->set(Module::SETTING_PLACEMENT, $model->placement);
            $module->settings->set(Module::SETTING_ANNOUNCEMENT_LIMIT, (string) $model->announcementLimit);
            $module->settings->set(Module::SETTING_DEFAULT_LAYOUT, json_encode($saved, JSON_THROW_ON_ERROR));
            $this->view->saved();

            return $this->refresh();
        }

        return $this->render('index', [
            'model' => $model,
            'layout' => $layout,
            'catalog' => WidgetCatalog::definitions(),
        ]);
    }
}
