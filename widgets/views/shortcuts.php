<?php

/**
 * @var \humhub\modules\user\models\User $user
 * @var \humhub\modules\space\models\Space[] $spaces
 * @var array<int, array{name: string, spaces: \humhub\modules\space\models\Space[], children: array<int, array{name: string, spaces: \humhub\modules\space\models\Space[]}>}>|null $groups
 * @var bool $hasMoreSpaces
 */

use humhub\helpers\Html;
use humhub\modules\space\widgets\Image as SpaceImage;
use humhub\modules\user\widgets\Image as UserImage;
use yii\helpers\Url;

?>

<section class="tc-feed-card">
    <a class="tc-feed-person" href="<?= Html::encode($user->createUrl()) ?>">
        <?= UserImage::widget(['user' => $user, 'width' => 48, 'link' => false, 'htmlOptions' => ['class' => 'tc-feed-person__avatar']]) ?>
        <span class="tc-feed-person__text">
            <strong><?= Html::encode($user->displayName) ?></strong>
            <small><?= Yii::t('ThiscoveryFeedModule.base', 'View profile') ?></small>
        </span>
    </a>

    <h2><?= Yii::t('ThiscoveryFeedModule.base', 'Your spaces') ?></h2>

    <?php if ($spaces === []): ?>
        <p class="tc-feed-empty"><?= Yii::t('ThiscoveryFeedModule.base', 'You have not joined a space yet.') ?></p>
    <?php elseif ($groups === null): ?>
        <ul class="tc-feed-spaces">
            <?php foreach ($spaces as $space): ?>
                <li>
                    <a href="<?= Html::encode($space->getUrl()) ?>">
                        <?= SpaceImage::widget(['space' => $space, 'width' => 28, 'link' => false]) ?>
                        <span><?= Html::encode($space->name) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <?php foreach ($groups as $group): ?>
            <h3 class="tc-feed-spaces__category"><?= Html::encode($group['name']) ?></h3>
            <?php if ($group['spaces'] !== []): ?>
                <ul class="tc-feed-spaces">
                    <?php foreach ($group['spaces'] as $space): ?>
                        <li>
                            <a href="<?= Html::encode($space->getUrl()) ?>">
                                <?= SpaceImage::widget(['space' => $space, 'width' => 28, 'link' => false]) ?>
                                <span><?= Html::encode($space->name) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php foreach ($group['children'] as $child): ?>
                <h4 class="tc-feed-spaces__child"><?= Html::encode($child['name']) ?></h4>
                <ul class="tc-feed-spaces">
                    <?php foreach ($child['spaces'] as $space): ?>
                        <li>
                            <a href="<?= Html::encode($space->getUrl()) ?>">
                                <?= SpaceImage::widget(['space' => $space, 'width' => 28, 'link' => false]) ?>
                                <span><?= Html::encode($space->name) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <a class="tc-feed-more" href="<?= Html::encode(Url::to(['/space/spaces'])) ?>">
        <?= $hasMoreSpaces
            ? Yii::t('ThiscoveryFeedModule.base', 'All spaces')
            : Yii::t('ThiscoveryFeedModule.base', 'Find a space') ?>
    </a>
</section>
