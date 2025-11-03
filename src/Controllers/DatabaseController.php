<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Templates;
use Elabftw\Params\DisplayParams;
use Override;

/**
 * For database.php
 */
final class DatabaseController extends AbstractEntityController
{
    public function __construct(App $app, Items|ItemsTypes $entity)
    {
        parent::__construct($app, $entity);

        $this->categoryArr = $app->itemsCategoryArr;
        $this->statusArr = $this->itemsStatusArr;
    }

    #[Override]
    protected function getPageTitle(): string
    {
        if ($this->Entity instanceof Items) {
            return ngettext('Resource', 'Resources', 2);
        }
        return _('Resources templates');
    }
}
