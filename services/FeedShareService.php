<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\services;

use humhub\helpers\Html;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryFeed\models\FeedShare;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

/**
 * Reads and writes the link between copies of a post sent to several spaces.
 */
final class FeedShareService
{
    public static function remember(string $shareId, int $contentId): void
    {
        if (FeedShare::find()->where(['content_id' => $contentId])->exists()) {
            return;
        }

        $row = new FeedShare();
        $row->share_id = $shareId;
        $row->content_id = $contentId;
        $row->save(false);
    }

    public static function shareId(int $contentId): ?string
    {
        $shareId = FeedShare::find()->select('share_id')->where(['content_id' => $contentId])->scalar();

        return is_string($shareId) && $shareId !== '' ? $shareId : null;
    }

    /**
     * Keeps one copy of a shared post in the feed: the earliest published copy
     * the member can still see. Posts that were not shared stay as they are.
     *
     * @param int[] $containerIds
     */
    public static function hideDuplicateCopies(ActiveQuery $query, array $containerIds): void
    {
        $representative = (new Query())
            ->select(new Expression('MIN(shared_content.id)'))
            ->from(['shared' => FeedShare::tableName()])
            ->innerJoin(['shared_content' => Content::tableName()], 'shared_content.id = shared.content_id')
            ->where(['shared_content.contentcontainer_id' => $containerIds])
            ->andWhere(['shared_content.state' => Content::STATE_PUBLISHED])
            ->andWhere(['or', ['shared_content.pinned' => 0], ['shared_content.pinned' => null]])
            ->andWhere(['or', ['shared_content.archived' => 0], ['shared_content.archived' => null]])
            ->groupBy('shared.share_id');

        $query->leftJoin(FeedShare::tableName() . ' feed_share', 'feed_share.content_id = content.id');
        $query->andWhere([
            'or',
            ['feed_share.content_id' => null],
            ['in', 'content.id', $representative],
        ]);
    }

    /**
     * @return Space[]
     */
    public static function spaces(Content $content): array
    {
        $shareId = self::shareId((int) $content->id);
        if ($shareId === null) {
            $container = $content->getContainer();

            return $container instanceof Space ? [$container] : [];
        }

        $ids = FeedShare::find()->select('content_id')->where(['share_id' => $shareId])->orderBy(['content_id' => SORT_ASC])->column();
        $contents = Content::find()->where(['id' => $ids])->orderBy(['id' => SORT_ASC])->all();
        $spaces = [];
        foreach ($contents as $shared) {
            if (!$shared instanceof Content) {
                continue;
            }
            $container = $shared->getContainer();
            if ($container instanceof Space) {
                $spaces[] = $container;
            }
        }

        return $spaces;
    }

    /**
     * Linked space names for the feed header. A single space keeps the normal header.
     */
    public static function headerLinks(Content $content): ?string
    {
        $spaces = self::spaces($content);
        if (count($spaces) < 2) {
            return null;
        }

        $links = [];
        foreach ($spaces as $space) {
            $links[] = Html::containerLink($space, ['class' => 'wall-entry-container-link link-accent']);
        }

        return '<span class="tc-feed-posted"><span class="visually-hidden">'
            . Html::encode(Yii::t('ThiscoveryFeedModule.base', 'Posted to'))
            . ' </span>'
            . implode(', ', $links)
            . '</span>';
    }

    public static function spaceLabel(Content $content): string
    {
        $names = [];
        foreach (self::spaces($content) as $space) {
            $names[] = $space->name;
        }

        return implode(', ', $names);
    }
}
