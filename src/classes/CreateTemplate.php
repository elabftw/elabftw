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

use Elabftw\Interfaces\CreateTemplateParamsInterface;
use Elabftw\Services\Filter;

final class CreateTemplate extends ContentParams implements CreateTemplateParamsInterface
{
    private string $body;

    public function __construct(string $content, string $body)
    {
        parent::__construct($content);
        $this->body = $body;
    }

    public function getContent(): string
    {
        return Filter::title($this->content);
    }

    public function getBody(): string
    {
        return Filter::body($this->body);
    }
}
