<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\UpdateParamsInterface;

final class UpdateStepFinished extends UpdateStep implements UpdateParamsInterface
{
    public string $action;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->action = 'update';
        $this->target = 'finished';
    }

    // TODO should not be here
    public function getContent(): string
    {
        return 'a';
    }
}
