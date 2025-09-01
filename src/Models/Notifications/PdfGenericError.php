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
 * When there is a problem with the PDF creation
 */
final class PdfGenericError extends WebOnlyNotifications
{
    protected Notifications $category = Notifications::PdfGenericError;
}
