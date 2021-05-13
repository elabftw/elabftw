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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Uploads;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Process a submitted form
 */
class FormProcessor extends AbstractProcessor
{
    private UploadedFile $uploadedFile;

    // @phpstan-ignore-next-line
    public function getParams()
    {
        if ($this->Model instanceof Uploads && $this->target === 'file') {
            return new UploadParams('', 'file', $this->uploadedFile);
        }
        throw new IllegalActionException('Bad params');
    }

    protected function process(Request $request): void
    {
        $this->action = $request->request->get('action') ?? '';
        $this->setTarget($request->request->get('target'));
        $type = 'experiment';
        if ($request->request->get('entity_type') === 'items') {
            $type = 'item';
        }
        $this->Entity = $this->getEntity($type, (int) $request->request->get('entity_id'));
        $this->id = $this->setId((int) $request->request->get('id'));
        $this->Model = $this->buildModel($request->request->get('model') ?? '');
        $this->uploadedFile = $request->files->get('content');
        $this->extra = $request->request->get('extraParams') ?? array();
    }
}
