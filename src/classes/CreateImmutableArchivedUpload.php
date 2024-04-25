<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\State;

/**
 * Automatic timestamp archives from experiments:timestamp command are immutable and archived
 */
class CreateImmutableArchivedUpload extends CreateImmutableUpload
{
    protected State $state = State::Archived;
}
