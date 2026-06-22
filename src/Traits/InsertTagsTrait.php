<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Enums\Action;
use Elabftw\Models\Tags;

/**
 * For inserting tags during creation
 */
trait InsertTagsTrait
{
    // insert the tags from the extraParams
    private function insertTags(array $tags, int $id): void
    {
        $newEntity = new self($this->Users, $id);
        $Tags = new Tags($newEntity);
        foreach ($tags as $tag) {
            $Tags->postAction(Action::Create, array('tag' => $tag));
        }
    }
}
