<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryForms\models\CustomForm;
use humhub\modules\thiscoveryForms\models\FormAnswer;
use humhub\modules\user\models\User;
use Throwable;
use Yii;
use yii\db\Expression;

/**
 * Open forms and surveys in a member's spaces that they can still complete.
 */
final class OpenFormService
{
    /**
     * @return array<int, array{title: string, url: string, space: string, closes: string, inProgress: bool}>
     */
    public static function forUser(User $user, int $limit = 5): array
    {
        if (!Yii::$app->hasModule('thiscovery-forms') || !class_exists(CustomForm::class)) {
            return [];
        }

        $containerIds = MemberSpaceService::contentContainerIds($user);
        if ($containerIds === []) {
            return [];
        }

        try {
            $forms = CustomForm::find()
                ->joinWith('content')
                ->where([
                    'custom_form.status' => CustomForm::STATUS_OPEN,
                    'custom_form.is_template' => 0,
                    'content.contentcontainer_id' => $containerIds,
                    'content.state' => Content::STATE_PUBLISHED,
                ])
                ->andWhere(['or', ['content.archived' => 0], ['content.archived' => null]])
                ->orderBy(new Expression('custom_form.closes_at IS NULL, custom_form.closes_at ASC, custom_form.title ASC'))
                ->limit(40)
                ->all();
        } catch (Throwable $e) {
            Yii::warning($e->getMessage(), 'thiscovery-feed');

            return [];
        }

        $items = [];
        foreach ($forms as $form) {
            if (!$form instanceof CustomForm || $form->isTemplate() || $form->isTrashed()) {
                continue;
            }
            if ($form->content === null || !$form->content->canView($user) || !$form->canAnswer($user)) {
                continue;
            }

            $container = $form->content->container;
            $closes = '';
            if (is_string($form->closes_at) && $form->closes_at !== '') {
                $closes = Yii::$app->formatter->asDate($form->closes_at, 'medium');
            }

            $items[] = [
                'title' => (string) $form->title,
                'url' => $form->getUrl(),
                'space' => $container instanceof Space ? $container->name : '',
                'closes' => $closes,
                'inProgress' => FormAnswer::find()->where([
                    'form_id' => $form->id,
                    'created_by' => $user->id,
                    'status' => FormAnswer::STATUS_IN_PROGRESS,
                    'is_test' => 0,
                ])->exists(),
            ];
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}
