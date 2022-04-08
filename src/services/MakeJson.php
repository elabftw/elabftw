<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Models\AbstractEntity;
use function json_decode;
use function json_encode;

/**
 * Make a JSON export from one or several entities
 */
class MakeJson extends AbstractMake implements FileMakerInterface
{
    public function __construct(AbstractEntity $entity, private array $idArr)
    {
        parent::__construct($entity);
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        return 'export-elabftw.json';
    }

    public function getContentType(): string
    {
        return 'application/json';
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
                $all = $this->Entity->read(new ContentParams());
            } catch (IllegalActionException $e) {
                continue;
            }
            // decode the metadata column because it's json
            if (isset($all['metadata'])) {
                $all['metadata'] = json_decode($all['metadata']);
            }
            $res[] = $all;
        }

        $json = json_encode($res);
        if ($json === false) {
            return '{"error": "Something went wrong!"}';
        }
        return $json;
    }
}
