<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Enums\Language;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Services\TeamFinder;

final readonly class AccessKeyVisitor
{
    public function __construct(
        private TeamFinder $teamFinder,
        private AnonymousLoginValidator $validator,
    ) {}

    public function getUser(): AnonymousUser
    {
        $teamId = $this->teamFinder->findTeam();

        $this->validator->validate($teamId);

        return new AnonymousUser(
            $teamId,
            Language::EnglishGB,
        );
    }
}
