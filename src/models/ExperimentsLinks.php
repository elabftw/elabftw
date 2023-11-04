<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;

/**
 * All about Experiments Links
 */
class ExperimentsLinks extends AbstractLinks
{
    protected function getTargetType(): string
    {
        return 'experiments';
    }

    protected function getCatTable(): string
    {
        return 'experiments_categories';
    }

    protected function getTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments2experiments';
        }
        if ($this->Entity instanceof Templates) {
            throw new ImproperActionException('Templates cannot be linked to experiments.');
        }
        return 'items2experiments';
    }

    protected function getImportTargetTable(): string
    {
        return 'experiments2experiments';
    }

    protected function getTemplateTable(): string
    {
        return 'experiments_templates_links';
    }

    protected function getRelatedTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments2experiments';
        }
        return 'experiments_links';
    }
}
