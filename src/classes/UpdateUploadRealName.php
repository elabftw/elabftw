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
use Elabftw\Interfaces\UpdateParamsInterface;
use Elabftw\Services\Filter;

final class UpdateUploadRealName extends UpdateParams implements UpdateParamsInterface
{
    public function __construct(string $content)
    {
        parent::__construct($content);
        $this->target = 'real_name';
    }

    public function getContent(): string
    {
        // don't allow php extension
        $ext = Tools::getExt($this->content);
        if ($ext === 'php') {
            throw new ImproperActionException('No php extension allowed!');
        }
        return Filter::sanitize($this->content);
    }
}
