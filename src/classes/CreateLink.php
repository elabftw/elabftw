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

use Elabftw\Interfaces\CreateLinkParamsInterface;

final class CreateLink implements CreateLinkParamsInterface
{
    public string $action;

    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->action = 'create';
    }

    public function getId(): int
    {
        return $this->id;
    }
}
