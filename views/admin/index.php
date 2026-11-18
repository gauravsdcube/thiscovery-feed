<?php

/**
 * @var \humhub\modules\thiscoveryFeed\models\SettingsForm $model
 * @var array{left: string[], right: string[]} $layout
 * @var array<string, array{column: string}> $catalog
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryFeed\Module;
use humhub\modules\thiscoveryFeed\services\BannerService;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\bootstrap\Button;

/** @var Module $module */
$module = Yii::$app->getModule('thiscovery-feed');
$visible = array_merge($layout['left'], $layout['right']);

$this->registerCss('.tc-feed-banner-preview-wrap{max-width:100%;overflow:hidden}.tc-feed-banner-preview{display:block;width:100%;max-width:100%;height:auto;border-radius:12px}');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= Yii::t('ThiscoveryFeedModule.base', 'Feed') ?></strong>
    </div>
    <div class="panel-body">
        <p><?= Yii::t('ThiscoveryFeedModule.base', 'The feed shows new posts from spaces a member belongs to, as long as that membership is still included on the dashboard. Members can hide or reorder the widgets you turn on here.') ?></p>
        <p><?= Yii::t('ThiscoveryFeedModule.base', 'The top bar is managed in Thiscovery Navigation. While the feed sits beside the Dashboard, Feed is available to place in that menu. When the feed replaces the Dashboard, the existing Dashboard item opens the feed instead.') ?></p>

        <?php $form = ActiveForm::begin(['acknowledge' => true, 'options' => ['enctype' => 'multipart/form-data']]) ?>

        <?= $form->field($model, 'showInTopMenu')->checkbox([
            'label' => Yii::t('ThiscoveryFeedModule.base', 'Show Feed in the top bar'),
        ])->label(false)->hint(Yii::t('ThiscoveryFeedModule.base', 'Turn this off to leave Feed off the top bar. Members can still open it at /feed. With Thiscovery Navigation, Feed is left out of that menu too.')) ?>

        <?= $form->field($model, 'placement')->radioList([
            Module::PLACEMENT_ALONGSIDE => Yii::t('ThiscoveryFeedModule.base', 'Show the feed next to the Dashboard'),
            Module::PLACEMENT_REPLACE => Yii::t('ThiscoveryFeedModule.base', 'Replace the Dashboard. The logo and login land on the feed, and the Dashboard menu is hidden.'),
        ])->label(Yii::t('ThiscoveryFeedModule.base', 'Where the feed lives')) ?>

        <?= $form->field($model, 'announcementLimit')->input('number', ['min' => 1, 'max' => 10, 'class' => 'form-control', 'style' => 'max-width: 6rem'])
            ->hint(Yii::t('ThiscoveryFeedModule.base', 'How many pinned posts to show in the announcements widget.')) ?>

        <?= $form->field($model, 'announcementLines')->input('number', ['min' => 1, 'max' => 20, 'class' => 'form-control', 'style' => 'max-width: 6rem'])
            ->hint(Yii::t('ThiscoveryFeedModule.base', 'How many lines of each pinned post to show before Read more.')) ?>

        <?= $form->field($model, 'showBanner')->checkbox([
            'label' => Yii::t('ThiscoveryFeedModule.base', 'Show a banner on the feed'),
        ])->label(false)->hint(Yii::t('ThiscoveryFeedModule.base', 'Shown across the top of the feed when an image is uploaded.')) ?>

        <?php $bannerUrl = BannerService::url(); ?>
        <?php if ($bannerUrl !== null): ?>
            <div class="tc-feed-banner-preview-wrap mb-3">
                <img class="tc-feed-banner-preview" src="<?= Html::encode($bannerUrl) ?>" alt="<?= Html::encode(Yii::t('ThiscoveryFeedModule.base', 'Feed banner')) ?>">
            </div>
            <?= $form->field($model, 'removeBanner')->checkbox([
                'label' => Yii::t('ThiscoveryFeedModule.base', 'Remove the current banner'),
            ])->label(false) ?>
        <?php endif; ?>

        <?= $form->field($model, 'bannerFile')->fileInput(['accept' => 'image/png,image/jpeg,image/gif,image/webp'])
            ->hint(Yii::t('ThiscoveryFeedModule.base', 'PNG, JPG, GIF or WebP. Up to 8 MB.')) ?>

        <fieldset class="mb-3">
            <legend class="form-label"><?= Yii::t('ThiscoveryFeedModule.base', 'Default widgets') ?></legend>
            <?php foreach ($catalog as $id => $definition): ?>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <label class="mb-0">
                        <?= Html::checkbox($model->formName() . '[widgets][]', in_array($id, $visible, true), ['value' => $id]) ?>
                        <strong><?= Html::encode($module->widgetLabel($id)) ?></strong>
                        <span class="text-muted">— <?= Html::encode($module->widgetDescription($id)) ?></span>
                    </label>
                    <label class="mb-0 d-flex align-items-center gap-2">
                        <span><?= Yii::t('ThiscoveryFeedModule.base', 'Order') ?></span>
                        <?= Html::input('number', $model->formName() . '[order][' . $id . ']', (string) ((array_search($id, $layout[$definition['column']], true) ?: 0) + 1), [
                            'min' => 1,
                            'max' => 20,
                            'class' => 'form-control form-control-sm',
                            'style' => 'width: 4.5rem',
                        ]) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </fieldset>

        <?= Button::save()->submit() ?>

        <?php ActiveForm::end() ?>
    </div>
</div>
