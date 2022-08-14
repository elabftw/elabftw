<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Factories;

use Elabftw\Elabftw\GetJsonProcessor;
use Elabftw\Elabftw\PostJsonProcessor;
use Elabftw\Interfaces\ProcessorInterface;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\Request;

/**
 * Build a Processor from the request
 */
class ProcessorFactory
{
    public function getProcessor(Users $users, Request $request): ProcessorInterface
    {
        if ($request->getMethod() === 'POST') {
            return new PostJsonProcessor($users, $request);
        }
        return new GetJsonProcessor($users, $request);
    }
}
