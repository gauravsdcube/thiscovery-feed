<?php

/**
 * @var array<int, array{title: string, url: string, space: string, closes: string, inProgress: bool}> $items
 */

use humhub\helpers\Html;

?>

<section class="tc-feed-card">
    <h2><?= Yii::t('ThiscoveryFeedModule.base', 'Forms') ?></h2>

    <?php if ($items === []): ?>
        <p class="tc-feed-empty"><?= Yii::t('ThiscoveryFeedModule.base', 'No forms to complete.') ?></p>
    <?php else: ?>
        <ul class="tc-feed-list">
            <?php foreach ($items as $item): ?>
                <li>
                    <a href="<?= Html::encode($item['url']) ?>"><?= Html::encode($item['title']) ?></a>
                    <small>
                        <?php if ($item['inProgress']): ?>
                            <?= Yii::t('ThiscoveryFeedModule.base', 'In progress') ?>
                        <?php endif; ?>
                        <?php if ($item['space'] !== ''): ?>
                            <?= $item['inProgress'] ? ' · ' : '' ?><?= Html::encode($item['space']) ?>
                        <?php endif; ?>
                        <?php if ($item['closes'] !== ''): ?>
                            <?= ($item['inProgress'] || $item['space'] !== '') ? ' · ' : '' ?>
                            <?= Yii::t('ThiscoveryFeedModule.base', 'Closes {date}', ['date' => $item['closes']]) ?>
                        <?php endif; ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
