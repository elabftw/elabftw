<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Models\AbstractEntity;

/**
 * Make a JSON export from one or several entities
 */
class MakeJson extends AbstractMake
{
    // the input ids but in an array
    private array $idArr = array();

    /**
     * Give me an id list and a type, I make json export
     *
     * @param AbstractEntity $entity
     * @param string $idList 4 8 15 16 23 42
     * @return void
     */
    public function __construct(AbstractEntity $entity, string $idList)
    {
        parent::__construct($entity);

        $this->idArr = explode(' ', $idList);
    }

    /**
     * Get the name of the generated file
     *
     * @return string
     */
    public function getFileName(): string
    {
        return 'export-elabftw.json';
    }

    /**
     * Loop over each id and add it to the JSON
     * This could be called the main function.
     *
     * @return array
     */
    public function getJson(): array
    {
        $res = array();
        foreach ($this->idArr as $id) {
            $this->Entity->setId((int) $id);
            $res[] = $this->Entity->read(true);
        }

        return $res;
    }
}
