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

final class UpdateUploadRealName extends UpdateUpload implements UpdateParamsInterface
{
    private string $content;

    public function __construct(JsonProcessor $payload)
    {
        parent::__construct($payload);
        $this->content = $payload->content;
        $this->target = 'real_name';
    }

    public function getContent(): string
    {
        // don't allow php extension
        $ext = Tools::getExt($this->content);
        if ($ext === 'php') {
            throw new ImproperActionException('No php extension allowed!');
        }
        return $this->content;
    }
}
