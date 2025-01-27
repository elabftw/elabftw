<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Enums\ExportFormat;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MakeTimestampInterface;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Changelog;
use Elabftw\Models\Users;
use Elabftw\Params\ContentParams;
use Elabftw\Services\MpdfProvider;
use GuzzleHttp\Client;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PDO;

/**
 * Mother class for all timestamping actions (trusted or blockchain)
 */
abstract class AbstractMakeTimestamp extends AbstractMake implements MakeTimestampInterface
{
    protected AbstractEntity $Entity;

    public function __construct(
        protected Users $requester,
        protected AbstractEntity $entity,
        protected array $configArr,
        protected ExportFormat $dataFormat,
    ) {
        parent::__construct();
        $this->checkMonthlyLimit();
    }

    public function getFileName(): string
    {
        return date('YmdHis') . '-timestamped.zip';
    }

    public function getFileContent(): string
    {
        return '';
    }

    /**
     * Get the data that will be timestamped and saved in the timestamp archive
     */
    public function generateData(): string
    {
        return match ($this->dataFormat) {
            ExportFormat::Json => $this->generateJson(),
            ExportFormat::Pdf, ExportFormat::PdfA => $this->generatePdf(),
            default => throw new ImproperActionException('Incorrect data format for timestamp action'),
        };
    }

    /**
     * Set the experiment as timestamped so we can easily display it
     *
     * @param string $responseTime the date of the timestamp
     */
    protected function updateTimestamp(string $responseTime): bool
    {
        $sql = sprintf('UPDATE %s SET
            timestamped = 1,
            timestampedby = :userid,
            timestamped_at = :when
            WHERE id = :id', $this->entity->entityType->value);
        $req = $this->Db->prepare($sql);
        // the date recorded in the db will match the creation time of the timestamp token
        $req->bindParam(':when', $responseTime);
        $req->bindParam(':userid', $this->requester->userid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->entity->id, PDO::PARAM_INT);

        // record the action in the changelog
        $Changelog = new Changelog($this->entity);
        $params = new ContentParams('timestamped_at', $responseTime);
        $Changelog->create($params);

        return $this->Db->execute($req);
    }

    private function generateJson(): string
    {
        $MakeJson = new MakeFullJson(array($this->entity));
        return $MakeJson->getFileContent();
    }

    /**
     * Generate a pdf to timestamp
     */
    private function generatePdf(): string
    {
        $userData = $this->entity->Users->userData;
        $MpdfProvider = new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            true, // PDF/A always for timestamp pdf
        );
        $log = (new Logger('elabftw'))->pushHandler(new ErrorLogHandler());
        $MakePdf = new MakeTimestampPdf(
            log: $log,
            mpdfProvider: $MpdfProvider,
            requester: $this->entity->Users,
            entityArr: array($this->entity),
            includeChangelog: true
        );
        if ($this->configArr['keeex_enabled'] === '1') {
            $Keeex = new MakeKeeex(new Client());
            return $Keeex->fromString($MakePdf->getFileContent());
        }
        return $MakePdf->getFileContent();
    }

    private function checkMonthlyLimit(): void
    {
        $limit = (int) $this->configArr['ts_limit'];
        // a limit of 0 means no limit
        if ($limit === 0) {
            return;
        }
        if ($this->entity instanceof AbstractConcreteEntity && $this->entity->getTimestampLastMonth() >= $limit) {
            throw new ImproperActionException(_('Number of timestamps this past month reached the limit!'));
        }
    }
}
