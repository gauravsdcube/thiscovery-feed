<?php

/**
 * @var \humhub\modules\user\models\User $user
 * @var array{left: string[], right: string[]} $layout
 * @var array{left: string[], right: string[]} $adminLayout
 * @var \humhub\modules\space\models\Space[] $spaces
 * @var \humhub\modules\space\models\Space|null $selected
 * @var string $audience
 * @var string[] $checkedGuids
 */

use humhub\helpers\Html;
use humhub\modules\thiscoveryFeed\services\AudienceService;
use humhub\modules\post\widgets\Form;
use humhub\modules\stream\widgets\StreamViewer;
use humhub\modules\thiscoveryFeed\assets\FeedAsset;
use humhub\modules\thiscoveryFeed\services\WidgetCatalog;
use humhub\modules\thiscoveryFeed\widgets\Announcements;
use humhub\modules\thiscoveryFeed\widgets\Shortcuts;
use humhub\modules\user\widgets\Image as UserImage;
use yii\helpers\Url;

FeedAsset::register($this);

$gridClass = 'tc-feed__grid';
if ($layout['left'] === [] && $layout['right'] === []) {
    $gridClass .= ' is-center-only';
} elseif ($layout['left'] === []) {
    $gridClass .= ' is-no-left';
} elseif ($layout['right'] === []) {
    $gridClass .= ' is-no-right';
}

$renderWidget = static function (string $id) use ($user): string {
    return match ($id) {
        WidgetCatalog::SHORTCUTS => Shortcuts::widget(['user' => $user]),
        WidgetCatalog::ANNOUNCEMENTS => Announcements::widget(['user' => $user]),
        default => '',
    };
};

$visible = array_merge($layout['left'], $layout['right']);
$allowed = array_merge($adminLayout['left'], $adminLayout['right']);
?>

<?= Html::beginContainer() ?>
<div class="tc-feed">

<div class="tc-feed__toolbar">
    <h1><?= Yii::t('ThiscoveryFeedModule.base', 'Feed') ?></h1>
    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#tc-feed-customize" aria-expanded="false" aria-controls="tc-feed-customize">
        <?= Yii::t('ThiscoveryFeedModule.base', 'Customize') ?>
    </button>
</div>

<div class="collapse" id="tc-feed-customize">
    <section class="tc-feed-card tc-feed-customize">
        <p><?= Yii::t('ThiscoveryFeedModule.base', 'Show, hide, and order the widgets your administrator has turned on. Reset brings back that default.') ?></p>
        <?= Html::beginForm(['layout'], 'post') ?>
            <?php foreach (['left' => Yii::t('ThiscoveryFeedModule.base', 'Left column'), 'right' => Yii::t('ThiscoveryFeedModule.base', 'Right column')] as $column => $columnLabel): ?>
                <?php
                $columnWidgets = array_values(array_filter(
                    $allowed,
                    static fn(string $id): bool => WidgetCatalog::column($id) === $column,
                ));
                if ($columnWidgets === []) {
                    continue;
                }
                ?>
                <fieldset>
                    <legend><?= Html::encode($columnLabel) ?></legend>
                    <?php foreach ($columnWidgets as $index => $id): ?>
                        <div class="tc-feed-customize__row">
                            <label>
                                <?= Html::checkbox('widgets[]', in_array($id, $visible, true), ['value' => $id]) ?>
                                <span><?= Html::encode(Yii::$app->getModule('thiscovery-feed')->widgetLabel($id)) ?></span>
                            </label>
                            <?php
                            $position = array_search($id, $layout[$column], true);
                            $orderValue = $position === false ? $index + 1 : $position + 1;
                            ?>
                            <label class="tc-feed-customize__order">
                                <span><?= Yii::t('ThiscoveryFeedModule.base', 'Order') ?></span>
                                <?= Html::input('number', 'order[' . $id . ']', (string) $orderValue, [
                                    'min' => 1,
                                    'max' => 20,
                                    'class' => 'form-control form-control-sm',
                                ]) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <div class="tc-feed-customize__actions">
                <?= Html::submitButton(Yii::t('ThiscoveryFeedModule.base', 'Save'), ['class' => 'btn btn-primary btn-sm']) ?>
                <?= Html::submitButton(Yii::t('ThiscoveryFeedModule.base', 'Reset to default'), ['class' => 'btn btn-light btn-sm', 'name' => 'reset', 'value' => '1']) ?>
            </div>
        <?= Html::endForm() ?>
    </section>
</div>

<div class="<?= Html::encode($gridClass) ?>">
    <?php if ($layout['left'] !== []): ?>
        <aside class="tc-feed__left" aria-label="<?= Html::encode(Yii::t('ThiscoveryFeedModule.base', 'Shortcuts')) ?>">
            <?php foreach ($layout['left'] as $widgetId): ?>
                <?= $renderWidget($widgetId) ?>
            <?php endforeach; ?>
        </aside>
    <?php endif; ?>

    <main class="tc-feed__main">
        <?php if ($selected === null): ?>
            <section class="tc-feed-card tc-feed-composer tc-feed-composer--empty">
                <p><?= Yii::t('ThiscoveryFeedModule.base', 'Join a space to post.') ?></p>
                <a class="btn btn-light btn-sm" href="<?= Html::encode(Url::to(['/space/spaces'])) ?>">
                    <?= Yii::t('ThiscoveryFeedModule.base', 'Find a space') ?>
                </a>
            </section>
        <?php else: ?>
            <?php
            $spaceGuids = array_map(static fn($space): string => $space->guid, $spaces);
            $everyone = $audience === AudienceService::EVERYONE;
            ?>
            <section class="tc-feed-composer" data-publish-url="<?= Html::encode(Url::to(['publish'])) ?>" data-space-guids="<?= Html::encode(json_encode($spaceGuids, JSON_THROW_ON_ERROR)) ?>" data-choose-space="<?= Html::encode(Yii::t('ThiscoveryFeedModule.base', 'Choose at least one space.')) ?>">
                <div class="tc-feed-composer__head">
                    <?= UserImage::widget(['user' => $user, 'width' => 40, 'link' => true]) ?>
                    <div class="tc-feed-composer__target">
                        <span class="tc-feed-audience__label" id="tc-feed-audience-label"><?= Yii::t('ThiscoveryFeedModule.base', 'Post to') ?></span>
                        <?php if (count($spaces) < 2): ?>
                            <p class="tc-feed-audience__summary">
                                <?= Yii::t('ThiscoveryFeedModule.base', 'Posting to {space}', ['space' => Html::encode($selected->name)]) ?>
                            </p>
                            <input type="hidden" class="tc-feed-locked-guid" value="<?= Html::encode($selected->guid) ?>">
                        <?php else: ?>
                            <div class="tc-feed-audience__choice" role="radiogroup" aria-labelledby="tc-feed-audience-label">
                                <label>
                                    <input type="radio" name="tc-feed-audience" value="<?= AudienceService::EVERYONE ?>"<?= $everyone ? ' checked' : '' ?>>
                                    <?= Yii::t('ThiscoveryFeedModule.base', 'Everyone') ?>
                                </label>
                                <label>
                                    <input type="radio" name="tc-feed-audience" value="<?= AudienceService::SPACES ?>"<?= $everyone ? '' : ' checked' ?>>
                                    <?= Yii::t('ThiscoveryFeedModule.base', 'Selected spaces') ?>
                                </label>
                            </div>
                            <p class="tc-feed-audience__summary" data-audience="everyone"<?= $everyone ? '' : ' hidden' ?>>
                                <?= Yii::t('ThiscoveryFeedModule.base', 'All {n} spaces you can post in', ['n' => count($spaces)]) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (count($spaces) > 1): ?>
                    <ul class="tc-feed-audience__list" data-audience="spaces"<?= $everyone ? ' hidden' : '' ?>>
                        <?php foreach ($spaces as $space): ?>
                            <li>
                                <label>
                                    <input type="checkbox" value="<?= Html::encode($space->guid) ?>"<?= in_array($space->guid, $checkedGuids, true) ? ' checked' : '' ?>>
                                    <span><?= Html::encode($space->name) ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p id="tc-feed-audience-error" class="tc-feed-audience__error" hidden></p>
                <?php endif; ?>
                <div id="tc-feed-composer-slot">
                    <?php
                    $publishUrl = Url::to(['publish']);
                    $formHtml = Form::widget(['contentContainer' => $selected]);
                    $formHtml = preg_replace(
                        '/(<form\b[^>]*\saction=")[^"]*(")/',
                        '$1' . Html::encode($publishUrl) . '$2',
                        $formHtml,
                        1,
                    );
                    $formHtml = preg_replace(
                        '/data-action-click-url="[^"]*"/',
                        'data-action-click-url="' . Html::encode($publishUrl) . '"',
                        $formHtml,
                        1,
                    );
                    $audienceFields = Html::hiddenInput('feedAudience', $everyone ? AudienceService::EVERYONE : AudienceService::SPACES);
                    foreach (($everyone ? $spaceGuids : $checkedGuids) as $guid) {
                        $audienceFields .= Html::hiddenInput('feedSpaces[]', $guid);
                    }
                    $formHtml = preg_replace('/<\/form>/', $audienceFields . '</form>', $formHtml, 1);
                    echo $formHtml;
                    ?>
                </div>
            </section>
        <?php endif; ?>

        <?= StreamViewer::widget([
            'id' => 'thiscovery-feed-stream',
            'options' => ['class' => 'tc-feed-stream'],
            'streamAction' => '/thiscovery-feed/feed/stream',
            'showFilters' => false,
            'messageStreamEmpty' => Yii::t('ThiscoveryFeedModule.base', '<strong>Your feed is empty.</strong><br>Join a space to see posts here.'),
        ]) ?>
    </main>

    <?php if ($layout['right'] !== []): ?>
        <aside class="tc-feed__right" aria-label="<?= Html::encode(Yii::t('ThiscoveryFeedModule.base', 'Announcements')) ?>">
            <?php foreach ($layout['right'] as $widgetId): ?>
                <?= $renderWidget($widgetId) ?>
            <?php endforeach; ?>
        </aside>
    <?php endif; ?>
</div>

</div>
<?= Html::endContainer() ?>
