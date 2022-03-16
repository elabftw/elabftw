<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function date;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Traits\CsvTrait;

/**
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends AbstractMake implements FileMakerInterface
{
    use CsvTrait;

    public function __construct(AbstractEntity $entity, private array $idArr)
    {
        parent::__construct($entity);
    }

    /**
     * Return a nice name for the file
     */
    public function getFileName(): string
    {
        return date('Y-m-d') . '-export.elabftw.csv';
    }

    /**
     * Here we populate the first row: it will be the column names
     */
    protected function getHeader(): array
    {
        return  array('id', 'date', 'title', 'content', 'category', 'elabid', 'rating', 'url', 'metadata');
    }

    /**
     * Generate an array for the requested data
     */
    protected function getRows(): array
    {
        $rows = array();
        foreach ($this->idArr as $id) {
            $this->Entity->setId((int) $id);
            try {
                $permissions = $this->Entity->getPermissions();
            } catch (IllegalActionException $e) {
                continue;
            }
            if ($permissions['read']) {
                $row = array(
                    $this->Entity->entityData['id'],
                    $this->Entity->entityData['date'],
                    htmlspecialchars_decode((string) $this->Entity->entityData['title'], ENT_QUOTES | ENT_COMPAT),
                    html_entity_decode(strip_tags(htmlspecialchars_decode((string) $this->Entity->entityData['body'], ENT_QUOTES | ENT_COMPAT))),
                    htmlspecialchars_decode((string) $this->Entity->entityData['category'], ENT_QUOTES | ENT_COMPAT),
                    $this->Entity->entityData['elabid'],
                    $row[] = $this->Entity->entityData['rating'],
                    $this->getUrl(),
                    $this->Entity->entityData['metadata'] ?? '',
                );
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
