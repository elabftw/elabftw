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

use Elabftw\Elabftw\ContentParams;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Models\AbstractEntity;
use function json_decode;
use function json_encode;

/**
 * Make a JSON export from one or several entities
 */
class MakeJson extends AbstractMake implements FileMakerInterface
{
    // the input ids but in an array
    private array $idArr = array();

    /**
     * Give me an id list and a type, I make json export
     *
     * @param string $idList 4 8 15 16 23 42
     */
    public function __construct(AbstractEntity $entity, string $idList)
    {
        parent::__construct($entity);

        $this->idArr = explode(' ', $idList);
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
            $all = $this->Entity->read(new ContentParams());
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
