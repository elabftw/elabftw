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

use Symfony\Component\HttpFoundation\Request;

/**
 * Process a classic GET request
 */
class RequestProcessor extends AbstractProcessor
{
    protected function process(Request $request): void
    {
        $type = null;
        $itemId = null;
        $this->action = $request->query->get('action');
        $this->target = $this->setTarget($request->query->get('target') ?? '');

        if ($request->query->has('entity')) {
            // we don't use the normal get here because we want to get an array, not a string
            // so use all() that returns an array, and get the entity from that
            $entity = $request->query->all()['entity'];
            $type = $entity['type'];
            $itemId = (int) $entity['id'];
        }

        if ($type !== null) {
            $this->Entity = $this->getEntity($type, $itemId);
        }
        $this->Model = $this->buildModel($request->query->get('model'));
    }
}
