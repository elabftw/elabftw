<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateTimeImmutable;
use Override;

/**
 * Create a pdf from several Entities
 */
final class MakeMultiPdf extends MakePdf
{
    #[Override]
    public function getFileName(): string
    {
        return sprintf('%s-elabftw-export.pdf', (new DateTimeImmutable())->format('Y-m-d'));
    }

    #[Override]
    protected function getTitle(): string
    {
        return _('Multientry eLabFTW PDF');
    }

    // we don't add keywords to multi pdf
    #[Override]
    protected function getKeywords(): string
    {
        return '';
    }
}
