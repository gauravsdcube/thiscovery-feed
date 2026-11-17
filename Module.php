<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed;

use humhub\components\Module as BaseModule;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\thiscoveryFeed\permissions\ManageFeed;
use humhub\modules\thiscoveryFeed\services\LayoutService;
use humhub\modules\thiscoveryFeed\services\WidgetCatalog;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Url;

class Module extends BaseModule
{
    public const PLACEMENT_ALONGSIDE = 'alongside';
    public const PLACEMENT_REPLACE = 'replace';

    public const SETTING_PLACEMENT = 'placement';
    public const SETTING_SHOW_IN_TOP_MENU = 'showInTopMenu';
    public const SETTING_DEFAULT_LAYOUT = 'defaultLayout';
    public const SETTING_ANNOUNCEMENT_LIMIT = 'announcementLimit';
    public const SETTING_USER_LAYOUT = 'layout';
    public const SETTING_COMPOSER_SPACE = 'composerSpaceGuid';
    public const SETTING_COMPOSER_AUDIENCE = 'composerAudience';
    public const SETTING_COMPOSER_SPACES = 'composerSpaces';

    public $resourcesPath = 'resources';

    public $icon = 'rss';

    public function init()
    {
        parent::init();
        Yii::setAlias('@thiscovery-feed', $this->getBasePath());
    }

    public function getName()
    {
        return Yii::t('ThiscoveryFeedModule.base', 'Thiscovery Feed');
    }

    public function getDescription()
    {
        return Yii::t(
            'ThiscoveryFeedModule.base',
            'A clean home feed of the spaces a member belongs to, with posting to every space they can use or the ones they choose, and a small set of widgets.'
        );
    }

    public function getConfigUrl()
    {
        return Url::to(['/thiscovery-feed/admin/index']);
    }

    /**
     * @return ManageFeed[]
     */
    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer !== null) {
            return [];
        }

        return [
            new ManageFeed(),
        ];
    }

    public function canManage(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        return Yii::$app->user->isAdmin()
            || Yii::$app->user->can(ManageFeed::class)
            || Yii::$app->user->can(ManageModules::class);
    }

    public function replacesDashboard(): bool
    {
        return $this->settings->get(self::SETTING_PLACEMENT, self::PLACEMENT_ALONGSIDE) === self::PLACEMENT_REPLACE;
    }

    public function showInTopMenu(): bool
    {
        return (string) $this->settings->get(self::SETTING_SHOW_IN_TOP_MENU, '1') !== '0';
    }

    public function announcementLimit(): int
    {
        $limit = (int) $this->settings->get(self::SETTING_ANNOUNCEMENT_LIMIT, 5);

        return max(1, min(10, $limit));
    }

    /**
     * @return array{left: string[], right: string[]}
     */
    public function adminLayout(): array
    {
        $decoded = $this->decodeLayout($this->settings->get(self::SETTING_DEFAULT_LAYOUT));
        if ($decoded === null) {
            return LayoutService::defaultLayout();
        }

        return LayoutService::normalize($decoded);
    }

    /**
     * @return array{left: string[], right: string[]}
     */
    public function layoutFor(User $user): array
    {
        $settings = $this->settings->user($user);
        $decoded = $settings ? $this->decodeLayout($settings->get(self::SETTING_USER_LAYOUT)) : null;

        return LayoutService::resolve($this->adminLayout(), $decoded);
    }

    public function widgetLabel(string $id): string
    {
        return match ($id) {
            WidgetCatalog::SHORTCUTS => Yii::t('ThiscoveryFeedModule.base', 'Shortcuts'),
            WidgetCatalog::ANNOUNCEMENTS => Yii::t('ThiscoveryFeedModule.base', 'Announcements'),
            default => $id,
        };
    }

    public function widgetDescription(string $id): string
    {
        return match ($id) {
            WidgetCatalog::SHORTCUTS => Yii::t('ThiscoveryFeedModule.base', 'Your profile and the spaces you belong to.'),
            WidgetCatalog::ANNOUNCEMENTS => Yii::t('ThiscoveryFeedModule.base', 'Pinned posts from your spaces.'),
            default => '',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeLayout(mixed $raw): ?array
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
