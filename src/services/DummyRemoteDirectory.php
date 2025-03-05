<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Override;

/**
 * Implements requests to a fake directory service
 */
final class DummyRemoteDirectory extends AbstractRemoteDirectory
{
    #[Override]
    public function search(string $term): array
    {
        return array(
            array(
                'firstname' => 'Émilie',
                'lastname' => 'du Châtelet',
                'email' => 'emilie@example.net',
                'orgid' => 'nope',
                'disabled' => false,
            ),
        );
    }
}
