<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Interfaces;

use Symfony\Component\HttpFoundation\Request;

/**
 * For things that process a request, be json or normal
 */
interface ProcessorInterface
{
    // @phpstan-ignore-next-line
    public function getModel();

    // TODO type ActionParamsInterface?
    // @phpstan-ignore-next-line
    public function getParams();

    public function getAction(): string;

    public function getTarget(): string;
}
