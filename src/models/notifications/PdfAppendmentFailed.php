<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;

/**
 * When an attached PDF file cannot be appended during PDF export
 */
class PdfAppendmentFailed extends WebOnlyNotifications
{
    protected Notifications $category = Notifications::PdfAppendmentFailed;

    public function __construct(private int $entityId, private string $entityPage, private string $fileNames)
    {
        parent::__construct();
    }

    protected function getBody(): array
    {
        return array(
            'entity_id' => $this->entityId,
            'entity_page' => $this->entityPage,
            'file_names' => $this->fileNames,
        );
    }
}
