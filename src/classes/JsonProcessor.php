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
 * Process a JSON payload defined by the Payload interface in typescript
 */
class JsonProcessor extends Processor
{
    // process a Payload json request
    protected function process(Request $request): void
    {
        // toArray is a symfony request object function to json_decode
        $decoded = $request->toArray();

        $this->action = $decoded['action'] ?? '';
        $this->target = $this->setTarget($decoded['target'] ?? '');

        if (isset($decoded['entity'])) {
            $id = (int) $decoded['entity']['id'];
            if ($id === 0) {
                $id = null;
            }
            $this->Entity = $this->getEntity($decoded['entity']['type'], $id);
        }
        $this->id = $this->setId((int) ($decoded['id'] ?? 0));
        $this->Model = $this->buildModel($decoded['model'] ?? '');
        $this->content = $decoded['content'] ?? '';
        $this->extra = $decoded['extraParams'] ?? array();
    }
}
