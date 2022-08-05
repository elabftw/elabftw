<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

final class UserParams extends ContentParams implements ContentParamsInterface
{
    public function getContent(): string
    {
        return match ($this->target) {
            // checked in update
            'email' => $this->content,
            'firstname', 'lastname' => Filter::sanitize($this->content),
            'validated' => (string) Filter::toBinary($this->content),
            'usergroup' => (string) Check::usergroup((int) $this->content),
            // return the hash of the password
            'password' => password_hash(Check::passwordLength($this->content), PASSWORD_DEFAULT),
            'orcid' => $this->filterOrcid(),
            default => throw new ImproperActionException('Invalid target for user update.'),
        };
    }

    public function getColumn(): string
    {
        return match ($this->target) {
            'password' => 'password_hash',
            default => $this->target,
        };
    }

    private function filterOrcid(): string
    {
        if (preg_match('/[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{4}/', $this->content) === 1) {
            return $this->content;
        }
        // note: the input field should prevent any incorrect value from being submitted in the first place
        throw new ImproperActionException('Incorrect value for orcid.');
    }
}
