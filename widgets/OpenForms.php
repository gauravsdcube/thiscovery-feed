<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryFeed\widgets;

use humhub\components\Widget;
use humhub\modules\thiscoveryFeed\services\OpenFormService;
use humhub\modules\user\models\User;

class OpenForms extends Widget
{
    public ?User $user = null;

    public function run()
    {
        if (!$this->user instanceof User) {
            return '';
        }

        return $this->render('forms', [
            'items' => OpenFormService::forUser($this->user),
        ]);
    }
}
