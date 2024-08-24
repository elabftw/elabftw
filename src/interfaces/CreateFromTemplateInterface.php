<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

/**
 * For concrete entities that are created from a template
 */
interface CreateFromTemplateInterface
{
    public function create(
        ?string $canread = null,
        ?string $canwrite = null,
        ?int $template = -1,
        array $tags = array(),
        bool $forceExpTpl = false,
        string $defaultTemplateHtml = '',
        string $defaultTemplateMd = '',
        ?int $status = null,
    ): int;
}
