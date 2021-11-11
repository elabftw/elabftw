<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\CreateCommentParamsInterface;
use Elabftw\Services\Email;

final class CreateComment extends ContentParams implements CreateCommentParamsInterface
{
    public function __construct(string $content, string $target, private Email $email)
    {
        parent::__construct($content, $target);
    }

    public function getEmail(): Email
    {
        return $this->email;
    }
}
