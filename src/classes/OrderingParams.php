<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Orderable;
use Elabftw\Exceptions\ImproperActionException;
use function json_decode;

use JsonException;

/**
 * Parameters passed for ordering stuff
 */
class OrderingParams
{
    public readonly Orderable $table;

    public readonly array $ordering;

    public function __construct(string $jsonRequestBody)
    {
        try {
            $reqBody = json_decode($jsonRequestBody, true, 5, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ImproperActionException('Error decoding JSON payload');
        }
        $this->table = Orderable::tryFrom($reqBody['table'] ?? '') ?? throw new ImproperActionException('Incorrect table');
        $this->ordering = $this->cleanup($reqBody['ordering']);
    }

    /**
     * Transform example_33 in 33
     */
    private function cleanup(array $ordering): array
    {
        return array_map(function ($el) {
            return (int) explode('_', $el)[1];
        }, $ordering);
    }
}
