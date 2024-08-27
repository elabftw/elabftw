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

use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Users;
use Elabftw\Models\ValidatedUser;
use Override;

use function sprintf;

/**
 * Import a trusted .eln file: the Author will be respected, and users created
 */
class TrustedEln extends Eln
{
    #[Override]
    protected function getAuthor(array $dataset): Users
    {
        if (!array_key_exists('author', $dataset)) {
            $this->logger->warning('Could not find "author" property in Dataset node! Falling back to requester.');
            return $this->requester;
        }
        if (!is_array($dataset['author']) || !array_key_exists('@id', $dataset['author'])) {
            $this->logger->warning('Invalid "author" property in Dataset node. Falling back to requester.');
            return $this->requester;
        }

        // look for the Author node, and create the user if they do not exist
        $author = $this->getNodeFromId($dataset['author']['@id']);
        try {
            $Author = ExistingUser::fromEmail($author['email'] ?? 'nope');
        } catch (ResourceNotFoundException) {
            $Author = ValidatedUser::createFromPerson($author, $this->requester->team ?? 0);
            $this->logger->info(sprintf('Created user with email: %s', $author['email']));
        }
        $Author->team = $this->requester->team;
        $Author->userData['team'] = $this->requester->team;
        return $Author;
    }
}
