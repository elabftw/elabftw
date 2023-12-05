<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\AuditEvent;

use Elabftw\Interfaces\AuditEventInterface;

abstract class AbstractAuditEvent implements AuditEventInterface
{
    public function __construct(private int $userid = 0)
    {
    }

    public function getUserid(): int
    {
        return $this->userid;
    }

    abstract public function getBody(): string;

    abstract public function getCategory(): int;
}
