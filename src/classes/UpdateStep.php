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

use Elabftw\Interfaces\UpdateStepParamsInterface;

class UpdateStep extends UpdateParams implements UpdateStepParamsInterface
{
    private string $target;

    public function __construct(string $target, string $content)
    {
        parent::__construct($content);
        $this->target = $target;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
