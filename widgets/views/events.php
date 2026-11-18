<?php

/**
 * @var array<int, array{title: string, url: string, when: string, space: string}> $items
 */

use humhub\helpers\Html;

?>

<section class="tc-feed-card">
    <h2><?= Yii::t('ThiscoveryFeedModule.base', 'Upcoming events') ?></h2>

    <?php if ($items === []): ?>
        <p class="tc-feed-empty"><?= Yii::t('ThiscoveryFeedModule.base', 'No upcoming events in your spaces.') ?></p>
    <?php else: ?>
        <ul class="tc-feed-list">
            <?php foreach ($items as $item): ?>
                <li>
                    <a href="<?= Html::encode($item['url']) ?>"><?= Html::encode($item['title']) ?></a>
                    <small>
                        <?= Html::encode($item['when']) ?>
                        <?php if ($item['space'] !== ''): ?>
                            · <?= Html::encode($item['space']) ?>
                        <?php endif; ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
