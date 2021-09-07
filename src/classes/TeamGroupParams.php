<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\TeamGroupParamsInterface;

final class TeamGroupParams extends ContentParams implements TeamGroupParamsInterface
{
    public function __construct(string $content, string $target = '', ?array $extra = null)
    {
        parent::__construct($content, $target, $extra);
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
