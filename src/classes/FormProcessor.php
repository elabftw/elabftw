<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Factories\EntityFactory;
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
        $this->action = $request->request->getAlpha('action');
        $this->setTarget($request->request->getAlpha('target'));
        $this->Entity = (new EntityFactory($this->Users, (string) $request->request->get('entity_type'), (int) $request->request->get('entity_id')))->getEntity();
        $this->id = $this->setId((int) $request->request->get('id'));
        $this->Model = $this->buildModel($request->request->getAlpha('model'));
        $this->uploadedFile = $request->files->get('content');
        $this->extra = (array) $request->request->get('extraParams');
    }
}
