<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;

/**
 * When there was an error during pdf generation because of MathJax
 */
class MathjaxFailed extends AbstractNotifications
{
    protected Notifications $category = Notifications::MathjaxFailed;

    public function __construct(private int $entityId, private string $entityPage)
    {
        parent::__construct();
    }

    protected function getBody(): array
    {
        return array(
            'entity_id' => $this->entityId,
            'entity_page' => $this->entityPage,
        );
    }
}
