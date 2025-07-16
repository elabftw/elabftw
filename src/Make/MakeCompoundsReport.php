<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Compounds;
use Override;
use Symfony\Component\HttpFoundation\InputBag;

use function date;

/**
 * Make a CSV file with all the compounds
 */
final class MakeCompoundsReport extends AbstractMakeCsv
{
    public function __construct(protected Compounds $compounds)
    {
        parent::__construct();
        $this->rows = $this->getRows();
    }

    #[Override]
    public function getFileName(): string
    {
        return date('Y-m-d') . '-compounds.elabftw.csv';
    }

    protected function getData(): array
    {
        $params = $this->compounds->getQueryParams(new InputBag(array('limit' => 999999)));
        return $this->compounds->readAll($params);
    }

    #[Override]
    protected function getRows(): array
    {
        $rows = $this->getData();
        if (empty($rows)) {
            throw new ImproperActionException(_('Nothing to export!'));
        }
        return $rows;
    }
}
