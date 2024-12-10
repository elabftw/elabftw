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

use Elabftw\Exceptions\ImproperActionException;

use function mb_strlen;
use function str_replace;

class StepParams extends ContentParams
{
    public function getContent(): ?string
    {
        return match ($this->target) {
            'body' => $this->getStep(),
            'deadline', 'finished_time' => $this->getDatetime(),
            default => throw new ImproperActionException('Incorrect parameter for steps.'),
        };
    }

    private function getDatetime(): ?string
    {
        if (empty($this->content)) {
            return null;
        }
        return $this->content;
    }

    private function getStep(): string
    {
        // remove any | as they are used in the group_concat
        $content = str_replace('|', '', $this->content);
        // check for length
        if (mb_strlen($content) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), self::MIN_CONTENT_SIZE));
        }
        return $content;
    }
}
