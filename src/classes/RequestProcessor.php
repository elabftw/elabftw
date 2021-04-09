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
 * Process a classic request, only here because of Ajax.php and EntityAjaxController that are both deprecated
 * @deprecated
 */
class RequestProcessor extends Processor
{
    protected function process(Request $request): void
    {
        $type = null;
        $itemId = null;
        if ($request->getMethod() === 'POST') {
            $this->action = $request->request->get('action');
            $what = $request->request->get('what');
            $type = $request->request->get('type');
            $params = $request->request->get('params') ?? array();
        } else {
            $this->action = $request->query->get('action');
            $what = $request->query->get('what');
            $type = $request->query->get('type');
            $params = $request->query->get('params') ?? array();
        }
        if (isset($params['itemId'])) {
            $itemId = (int) $params['itemId'];
        }

        if ($type !== null) {
            $this->Entity = $this->getEntity($type, $itemId);
        }
        $this->Model = $this->buildModel($what);
    }
}
