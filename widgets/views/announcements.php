<?php

/**
 * @var array<int, array{url: string, spaces: array<int, array{name: string, url: string}>, title: string, message: string, record: \humhub\modules\post\models\Post, time: string}> $items
 * @var int $lines
 */

use humhub\helpers\Html;
use humhub\modules\content\widgets\richtext\RichText;

?>

<section class="tc-feed-card tc-feed-announcements-card">
    <h2><?= Yii::t('ThiscoveryFeedModule.base', 'Announcements') ?></h2>

    <?php if ($items === []): ?>
        <p class="tc-feed-empty"><?= Yii::t('ThiscoveryFeedModule.base', 'Nothing is pinned in your spaces yet.') ?></p>
    <?php else: ?>
        <ul class="tc-feed-announcements">
            <?php foreach ($items as $item): ?>
                <li>
                    <span class="tc-feed-announcement__meta">
                        <span class="tc-feed-announcement__spaces">
                            <?php foreach ($item['spaces'] as $index => $space): ?>
                                <?php if ($index > 0): ?>, <?php endif; ?>
                                <a href="<?= Html::encode($space['url']) ?>"><?= Html::encode($space['name']) ?></a>
                            <?php endforeach; ?>
                        </span>
                        <?php if ($item['time'] !== ''): ?>
                            <time><?= Html::encode($item['time']) ?></time>
                        <?php endif; ?>
                    </span>
                    <?php if ($item['title'] !== ''): ?>
                        <span class="tc-feed-announcement__title"><?= Html::encode($item['title']) ?></span>
                    <?php endif; ?>
                    <?php if ($item['message'] !== ''): ?>
                        <div class="tc-feed-announcement__body" data-ui-markdown style="--tc-feed-announcement-lines: <?= (int) $lines ?>">
                            <?= RichText::output($item['message'], ['record' => $item['record']]) ?>
                        </div>
                    <?php endif; ?>
                    <a class="tc-feed-announcement__more" href="<?= Html::encode($item['url']) ?>"><?= Yii::t('ThiscoveryFeedModule.base', '… Read more') ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
