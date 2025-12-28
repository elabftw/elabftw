<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Elabftw\Enums\Messages;
use Override;
use Psr\Log\LoggerInterface;

/**
 * For permissions issues
 */
final class IllegalActionException extends AppException
{
    protected Messages $error = Messages::InsufficientPermissions;

    #[Override]
    protected function emitLog(LoggerInterface $logger, int $userid): void
    {
        // use notice level
        $logger->notice('', array(array('userid' => $userid), array('IllegalAction', $this)));
    }
}
