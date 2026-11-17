<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\widgets;

use humhub\components\Widget;
use humhub\modules\space\models\Membership;
use humhub\modules\user\models\User;

class Shortcuts extends Widget
{
    public ?User $user = null;

    public int $spaceLimit = 6;

    public function run()
    {
        if (!$this->user instanceof User) {
            return '';
        }

        $spaces = [];
        foreach (Membership::findByUser($this->user)->limit($this->spaceLimit + 1)->all() as $membership) {
            if ($membership->space) {
                $spaces[] = $membership->space;
            }
        }

        return $this->render('shortcuts', [
            'user' => $this->user,
            'spaces' => array_slice($spaces, 0, $this->spaceLimit),
            'hasMoreSpaces' => count($spaces) > $this->spaceLimit,
        ]);
    }
}
