<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\App;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\StringMakerInterface;
use Elabftw\Models\AbstractEntity;

use function json_encode;
use function ksort;

/**
 * Make a JSON export from one or several entities
 */
class MakeJson extends AbstractMake implements StringMakerInterface
{
    public function __construct(AbstractEntity $entity, private array $idArr)
    {
        parent::__construct($entity);
        $this->contentType = 'application/json';
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        return 'export-elabftw.json';
    }

    /**
     * Loop over each id and add it to the JSON
     * This could be called the main function.
     */
    public function getFileContent(): string
    {
        $res = array();
        foreach ($this->idArr as $id) {
            $this->Entity->setId((int) $id);
            try {
                $all = $this->getEntityData();
                // add eLabFTW version number
                $all['elabftw_version'] = App::INSTALLED_VERSION;
                $all['elabftw_version_int'] = App::INSTALLED_VERSION_INT;
                ksort($all);
            } catch (IllegalActionException) {
                continue;
            }
            $res[] = $all;
        }

        $json = json_encode($res, JSON_THROW_ON_ERROR);
        $this->contentSize = mb_strlen($json);
        return $json;
    }

    protected function getEntityData(): array
    {
        return $this->Entity->readOne();
    }
}
