<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users\ValidatedUser;
use Override;

use function sprintf;
use function array_key_exists;
use function is_array;
use function is_string;

/**
 * Import a trusted .eln file: the Author will be respected, and users created
 */
final class TrustedEln extends Eln
{
    public bool $requesterIsAuthor = false;

    // CLI imports are not subject to the web interface's archive limits.
    protected bool $enforceLimits = false;

    #[Override]
    protected function getChangelogAuthor(array $action): Users
    {
        $agent = $action['agent'] ?? null;
        if (!is_array($agent) || !is_string($agent['@id'] ?? null)) {
            throw new ImproperActionException(
                'Cannot restore changelog author: missing or invalid agent.'
            );
        }
        return $this->getOrCreateUser($agent['@id']);
    }

    #[Override]
    protected function getAuthor(array $dataset): Users
    {
        if ($this->requesterIsAuthor) {
            return $this->requester;
        }
        if (!array_key_exists('author', $dataset)) {
            $this->logger->warning('Could not find "author" property in Dataset node! Falling back to requester.');
            return $this->requester;
        }
        if (!is_array($dataset['author']) || !array_key_exists('@id', $dataset['author'])) {
            $this->logger->warning('Invalid "author" property in Dataset node. Falling back to requester.');
            return $this->requester;
        }

        // look for the Author node, and create the user if they do not exist
        $Author = $this->getOrCreateUser($dataset['author']['@id']);
        $Author->team = $this->requester->team;
        $Author->userData['team'] = $this->requester->team;
        return $Author;
    }

    #[Override]
    protected function getMetadataUserReference(string $personId): int
    {
        return $this->getOrCreateUser($personId)->getUserid();
    }

    private function getOrCreateUser(string $personId): Users
    {
        $person = $this->getNodeFromId($personId);
        try {
            $Author = ExistingUser::fromEmail($person['email'] ?? 'undefined');
        } catch (ResourceNotFoundException) {
            $Author = ValidatedUser::createFromPerson($person, $this->requester->team ?? 0);
            $this->logger->info(sprintf('Created user with email: %s', $person['email']));
        }
        return $Author;
    }
}
