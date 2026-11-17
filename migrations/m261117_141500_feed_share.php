<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\components\Migration;
use humhub\libs\UUID;
use humhub\modules\post\models\Post;
use humhub\modules\thiscoveryFeed\services\ShareGrouper;
use yii\db\Query;

class m261117_141500_feed_share extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('thiscovery_feed_share', [
            'content_id' => $this->integer()->notNull(),
            'share_id' => $this->char(36)->notNull(),
        ]);
        $this->addPrimaryKey('pk_thiscovery_feed_share', 'thiscovery_feed_share', 'content_id');
        $this->safeCreateIndex('idx_thiscovery_feed_share', 'thiscovery_feed_share', 'share_id');
        $this->safeAddForeignKey(
            'fk_thiscovery_feed_share_content',
            'thiscovery_feed_share',
            'content_id',
            'content',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->linkExistingCopies();
    }

    public function safeDown()
    {
        $this->safeDropForeignKey('fk_thiscovery_feed_share_content', 'thiscovery_feed_share');
        $this->safeDropTable('thiscovery_feed_share');
    }

    private function linkExistingCopies(): void
    {
        if ($this->db->getTableSchema('post', true) === null) {
            return;
        }

        $rows = (new Query())
            ->select([
                'id' => 'c.id',
                'created_by' => 'c.created_by',
                'created_at' => 'c.created_at',
                'message' => 'p.message',
                'container_id' => 'c.contentcontainer_id',
            ])
            ->from(['c' => 'content'])
            ->innerJoin(['p' => 'post'], 'p.id = c.object_id')
            ->where(['c.object_model' => Post::class])
            ->orderBy(['c.created_by' => SORT_ASC, 'c.created_at' => SORT_ASC, 'c.id' => SORT_ASC])
            ->all($this->db);

        foreach (ShareGrouper::groups($rows) as $ids) {
            $shareId = UUID::v4();
            foreach ($ids as $id) {
                $this->insert('thiscovery_feed_share', [
                    'content_id' => $id,
                    'share_id' => $shareId,
                ]);
            }
        }
    }
}
