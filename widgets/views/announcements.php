<?php

/**
 * @var array<int, array{url: string, spaces: array<int, array{name: string, url: string}>, title: string, excerpt: string, time: string}> $items
 */

use humhub\helpers\Html;

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
                    <a href="<?= Html::encode($item['url']) ?>">
                        <?php if ($item['title'] !== ''): ?>
                            <span class="tc-feed-announcement__title"><?= Html::encode($item['title']) ?></span>
                        <?php endif; ?>
                        <?php if ($item['excerpt'] !== ''): ?>
                            <span class="tc-feed-announcement__excerpt"><?= Html::encode($item['excerpt']) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
