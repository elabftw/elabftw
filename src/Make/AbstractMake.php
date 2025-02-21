<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\FileMakerInterface;
use Symfony\Component\HttpFoundation\Response;
use Override;

/**
 * Mother class of the Make* services
 */
abstract class AbstractMake implements FileMakerInterface
{
    // a place to gather errors or warnings generated during the making
    public array $errors = array();

    protected int $contentSize = 0;

    protected Db $Db;

    protected string $contentType = 'application/octet-stream';

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * The filename for what we are making
     */
    #[Override]
    abstract public function getFileName(): string;

    #[Override]
    public function getContentSize(): int
    {
        return $this->contentSize;
    }

    #[Override]
    public function getContentType(): string
    {
        return $this->contentType;
    }

    #[Override]
    public function getResponse(): Response
    {
        return new Response(
            $this->getFileContent(),
            200,
            array(
                'Content-Type' => $this->getContentType(),
                'Content-Size' => $this->getContentSize(),
                'Content-disposition' => 'inline; filename="' . $this->getFileName() . '"',
                'Cache-Control' => 'no-store',
                'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            )
        );
    }
}
