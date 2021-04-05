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

class UpdateStep extends UpdateParams
{
    protected string $target;

    public function __construct(int $id, string $content)
    {
        parent::__construct($id, $content);
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
