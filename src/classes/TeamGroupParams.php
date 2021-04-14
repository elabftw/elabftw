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

use Elabftw\Interfaces\TeamGroupParamsInterface;

final class TeamGroupParams extends ContentParams implements TeamGroupParamsInterface
{
    private ?array $extra;

    public function __construct(string $content, string $target = '', ?array $extra = null)
    {
        parent::__construct($content, $target);
        $this->extra = $extra;
    }

    public function getUserid(): int
    {
        return (int) $this->extra['userid'];
    }

    public function getGroup(): int
    {
        return (int) $this->extra['group'];
    }

    public function getHow(): string
    {
        return $this->extra['how'];
    }
}
