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

use Elabftw\Interfaces\DestroyParamsInterface;

final class DestroyParams implements DestroyParamsInterface
{
    public string $action;

    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->action = 'destroy';
    }

    public function getId(): int
    {
        return $this->id;
    }
}
