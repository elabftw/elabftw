<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

/**
 * Create a pdf from an Entity for timestamping purposes
 */
class MakeTimestampPdf extends MakePdf
{
    protected bool $includeAttachments = true;
}
