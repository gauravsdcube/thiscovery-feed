<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\tests;

use humhub\modules\thiscoveryFeed\services\AudienceService;
use humhub\modules\thiscoveryFeed\services\LayoutService;
use humhub\modules\thiscoveryFeed\services\ShareGrouper;
use humhub\modules\thiscoveryFeed\services\WidgetCatalog;
use PHPUnit\Framework\TestCase;

class LayoutServiceTest extends TestCase
{
    public function testDefaultLayoutKeepsWidgetsInTheirColumns(): void
    {
        $layout = LayoutService::defaultLayout();

        $this->assertSame([WidgetCatalog::SHORTCUTS], $layout['left']);
        $this->assertSame([WidgetCatalog::ANNOUNCEMENTS], $layout['right']);
    }

    public function testNormalizeDropsUnknownWidgetsAndKeepsAWidgetOnEitherSide(): void
    {
        $layout = LayoutService::normalize([
            'left' => [WidgetCatalog::SHORTCUTS, WidgetCatalog::ANNOUNCEMENTS, 'nope', WidgetCatalog::SHORTCUTS],
            'right' => ['nope', WidgetCatalog::ANNOUNCEMENTS],
        ]);

        $this->assertSame([WidgetCatalog::SHORTCUTS, WidgetCatalog::ANNOUNCEMENTS], $layout['left']);
        $this->assertSame([], $layout['right']);
    }

    public function testSelectionCanPlaceAWidgetOnEitherSide(): void
    {
        $layout = LayoutService::fromSelection(
            [WidgetCatalog::EVENTS, WidgetCatalog::FORMS],
            [WidgetCatalog::FORMS => 1, WidgetCatalog::EVENTS => 2],
            [WidgetCatalog::EVENTS => 'left', WidgetCatalog::FORMS => 'right'],
        );

        $this->assertSame([WidgetCatalog::EVENTS], $layout['left']);
        $this->assertSame([WidgetCatalog::FORMS], $layout['right']);
    }

    public function testMemberLayoutIsLimitedToTheAdminCatalogue(): void
    {
        $resolved = LayoutService::resolve(
            ['left' => [], 'right' => [WidgetCatalog::ANNOUNCEMENTS]],
            LayoutService::defaultLayout(),
        );

        $this->assertSame([], $resolved['left']);
        $this->assertSame([WidgetCatalog::ANNOUNCEMENTS], $resolved['right']);
    }

    public function testMissingMemberLayoutUsesTheAdminDefault(): void
    {
        $admin = ['left' => [WidgetCatalog::SHORTCUTS], 'right' => []];

        $this->assertSame(
            LayoutService::normalize($admin),
            LayoutService::resolve($admin, null),
        );
    }

    public function testSelectionHonoursOrderAndIgnoresUnknownIds(): void
    {
        $layout = LayoutService::fromSelection(
            [WidgetCatalog::ANNOUNCEMENTS, 'nope', WidgetCatalog::SHORTCUTS],
            [WidgetCatalog::SHORTCUTS => 2, WidgetCatalog::ANNOUNCEMENTS => 1],
        );

        $this->assertSame([WidgetCatalog::SHORTCUTS], $layout['left']);
        $this->assertSame([WidgetCatalog::ANNOUNCEMENTS], $layout['right']);
    }

    public function testEveryoneIsNotCopiedIntoSpaces(): void
    {
        $this->assertSame(
            [],
            AudienceService::resolve(AudienceService::EVERYONE, ['a', 'b', 'a'], ['b']),
        );
    }

    public function testSelectedSpacesStayInsideTheAllowedList(): void
    {
        $this->assertSame(
            ['b'],
            AudienceService::resolve(AudienceService::SPACES, ['a', 'b', 'c'], ['nope', 'b', 'b']),
        );
    }

    public function testSelectedSpacesCanBeEmpty(): void
    {
        $this->assertSame(
            [],
            AudienceService::resolve(AudienceService::SPACES, ['a'], []),
        );
    }

    public function testCopiesToSeveralSpacesFormOneGroup(): void
    {
        $groups = ShareGrouper::groups([
            ['id' => 1, 'created_by' => 4, 'created_at' => '2026-11-17 14:00:00', 'message' => 'Hello', 'container_id' => 10],
            ['id' => 2, 'created_by' => 4, 'created_at' => '2026-11-17 14:00:02', 'message' => 'Hello', 'container_id' => 11],
            ['id' => 3, 'created_by' => 4, 'created_at' => '2026-11-17 14:00:03', 'message' => 'Hello', 'container_id' => 12],
        ]);

        $this->assertSame([[1, 2, 3]], $groups);
    }

    public function testTheSameSpaceIsNotTreatedAsAnotherCopy(): void
    {
        $groups = ShareGrouper::groups([
            ['id' => 1, 'created_by' => 4, 'created_at' => '2026-11-17 14:00:00', 'message' => 'Hello', 'container_id' => 10],
            ['id' => 2, 'created_by' => 4, 'created_at' => '2026-11-17 14:00:02', 'message' => 'Hello', 'container_id' => 10],
        ]);

        $this->assertSame([], $groups);
    }
}
