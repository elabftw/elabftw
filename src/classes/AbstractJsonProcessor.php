<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Request;

/**
 * Process a JSON payload
 */
abstract class AbstractJsonProcessor extends AbstractProcessor
{
    abstract protected function getJson(Request $request): string;

    protected function process(Request $request): void
    {
        $json = $this->getJson($request);
        $decoded = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        $this->action = $decoded->action ?? '';
        $this->setTarget($decoded->target ?? '');

        if (property_exists($decoded, 'entity') && $decoded->entity !== null) {
            $id = (int) $decoded->entity->id;
            if ($id === 0) {
                $id = null;
            }
            $this->Entity = $this->getEntity($decoded->entity->type, $id);
        }
        $this->id = $this->setId((int) ($decoded->id ?? 0));
        $this->Model = $this->buildModel($decoded->model ?? '');
        $this->content = $decoded->content ?? '';
        if (property_exists($decoded, 'extraParams')) {
            $this->extra = (array) $decoded->extraParams;
        }
    }
}
