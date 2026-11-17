<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\controllers;

use humhub\components\Controller;
use humhub\modules\post\widgets\Form;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryFeed\components\actions\FeedStreamAction;
use humhub\modules\thiscoveryFeed\Module;
use humhub\modules\thiscoveryFeed\services\AudienceService;
use humhub\modules\thiscoveryFeed\services\FeedPublisher;
use humhub\modules\thiscoveryFeed\services\LayoutService;
use humhub\modules\thiscoveryFeed\services\PostableSpaceService;
use humhub\modules\user\models\User;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class FeedController extends Controller
{
    public function init()
    {
        $this->appendPageTitle(Yii::t('ThiscoveryFeedModule.base', 'Feed'));
        return parent::init();
    }

    protected function getAccessRules()
    {
        return [
            ['login'],
        ];
    }

    public function actions()
    {
        return [
            'stream' => [
                'class' => FeedStreamAction::class,
                'viewContext' => 'dashboard',
            ],
        ];
    }

    public function actionIndex()
    {
        /** @var Module $module */
        $module = $this->module;
        /** @var User $user */
        $user = Yii::$app->user->getIdentity();
        $spaces = PostableSpaceService::forUser($user);
        $audience = $this->savedAudience($module, $user);
        $checked = $this->savedSpaceGuids($module, $user, $spaces);
        $primary = PostableSpaceService::find($spaces, $checked[0] ?? '') ?? ($spaces[0] ?? null);

        return $this->render('index', [
            'user' => $user,
            'layout' => $module->layoutFor($user),
            'adminLayout' => $module->adminLayout(),
            'spaces' => $spaces,
            'selected' => $primary,
            'audience' => $audience,
            'checkedGuids' => $checked,
        ]);
    }

    public function actionPublish()
    {
        $this->forcePostRequest();
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var Module $module */
        $module = $this->module;
        /** @var User $user */
        $user = Yii::$app->user->getIdentity();
        $allowed = PostableSpaceService::forUser($user);
        $audience = (string) Yii::$app->request->post('feedAudience', AudienceService::SPACES);
        if (!in_array($audience, [AudienceService::EVERYONE, AudienceService::SPACES], true)) {
            $audience = AudienceService::SPACES;
        }
        $selected = Yii::$app->request->post('feedSpaces', []);
        if (!is_array($selected)) {
            $selected = [];
        }

        $guids = AudienceService::resolve(
            $audience,
            array_map(static fn(Space $space): string => $space->guid, $allowed),
            $selected,
        );
        $spaces = [];
        foreach ($guids as $guid) {
            $space = PostableSpaceService::find($allowed, $guid);
            if ($space instanceof Space) {
                $spaces[] = $space;
            }
        }

        if ($spaces === []) {
            return ['errors' => ['message' => [Yii::t('ThiscoveryFeedModule.base', 'Choose at least one space.')]]];
        }

        $settings = $module->settings->user($user);
        $settings->set(Module::SETTING_COMPOSER_AUDIENCE, $audience);
        $settings->set(Module::SETTING_COMPOSER_SPACES, json_encode($audience === AudienceService::EVERYONE ? array_map(static fn(Space $space): string => $space->guid, $allowed) : $guids, JSON_THROW_ON_ERROR));
        $settings->set(Module::SETTING_COMPOSER_SPACE, $spaces[0]->guid);

        return (new FeedPublisher())->publish($spaces);
    }

    private function savedAudience(Module $module, User $user): string
    {
        $audience = (string) $module->settings->user($user)->get(Module::SETTING_COMPOSER_AUDIENCE, AudienceService::SPACES);

        return $audience === AudienceService::EVERYONE ? AudienceService::EVERYONE : AudienceService::SPACES;
    }

    /**
     * @param Space[] $spaces
     * @return string[]
     */
    private function savedSpaceGuids(Module $module, User $user, array $spaces): array
    {
        $allowed = array_map(static fn(Space $space): string => $space->guid, $spaces);
        $raw = $module->settings->user($user)->get(Module::SETTING_COMPOSER_SPACES, '');
        $saved = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($saved)) {
            $legacy = (string) $module->settings->user($user)->get(Module::SETTING_COMPOSER_SPACE, '');
            $saved = $legacy !== '' ? [$legacy] : [];
        }

        $checked = AudienceService::resolve(AudienceService::SPACES, $allowed, $saved);
        if ($checked === [] && $allowed !== []) {
            $checked = [$allowed[0]];
        }

        return $checked;
    }

    public function actionComposer(string $cguid)
    {
        /** @var User $user */
        $user = Yii::$app->user->getIdentity();
        $spaces = PostableSpaceService::forUser($user);
        $space = PostableSpaceService::find($spaces, $cguid);
        if (!$space instanceof Space) {
            throw new ForbiddenHttpException();
        }

        /** @var Module $module */
        $module = $this->module;
        $module->settings->user($user)->set(Module::SETTING_COMPOSER_SPACE, $space->guid);

        return $this->renderAjaxPartial(Form::widget([
            'contentContainer' => $space,
        ]));
    }

    public function actionLayout()
    {
        $this->forcePostRequest();

        /** @var Module $module */
        $module = $this->module;
        /** @var User $user */
        $user = Yii::$app->user->getIdentity();
        $settings = $module->settings->user($user);

        if (Yii::$app->request->post('reset')) {
            $settings->set(Module::SETTING_USER_LAYOUT, null);
        } else {
            $selected = Yii::$app->request->post('widgets', []);
            $order = Yii::$app->request->post('order', []);
            if (!is_array($selected)) {
                $selected = [];
            }
            if (!is_array($order)) {
                $order = [];
            }
            $selected = array_values(array_filter($selected, 'is_string'));
            $layout = LayoutService::resolve(
                $module->adminLayout(),
                LayoutService::fromSelection($selected, $order),
            );
            $settings->set(Module::SETTING_USER_LAYOUT, json_encode($layout, JSON_THROW_ON_ERROR));
        }

        $this->view->saved();

        return $this->redirect(['index']);
    }
}
