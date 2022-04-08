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
use Elabftw\Interfaces\StepParamsInterface;
use Elabftw\Services\Filter;
use function mb_strlen;
use function str_replace;

class StepParams extends ContentParams implements StepParamsInterface
{
    public function getContent(): string
    {
        // remove any | as they are used in the group_concat
        $c = str_replace('|', '', Filter::sanitize($this->content));
        // check for length
        if (mb_strlen($c) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        return $c;
    }

    public function getDatetime(): ?string
    {
        if (empty($this->content)) {
            return null;
        }
        return $this->content;
    }
}
