<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\CreateImmutableUpload;
use Elabftw\Elabftw\FsTools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MakeTimestampInterface;
use Elabftw\Interfaces\TimestampResponseInterface;
use Elabftw\Models\Experiments;
use Elabftw\Services\MpdfProvider;
use GuzzleHttp\Client;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PDO;
use ZipArchive;

/**
 * Timestamp an experiment with RFC 3161
 * Based on:
 * http://www.d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161
 */
abstract class AbstractMakeTimestamp extends AbstractMake implements MakeTimestampInterface
{
    public function __construct(protected array $configArr, Experiments $entity)
    {
        parent::__construct($entity);
        $this->checkMonthlyLimit();
    }

    public function getFileName(): string
    {
        return date('YmdHis') . '-timestamped.zip';
    }

    public function saveTimestamp(string $pdfPath, TimestampResponseInterface $tsResponse): int
    {
        // 20220210171842-timestamp.pdf
        $zipName = $this->getFileName();
        $pdfName = str_replace('zip', 'pdf', $zipName);
        $tokenName = str_replace('zip', 'asn1', $zipName);

        // update timestamp on the experiment
        $this->updateTimestamp($this->formatResponseTime($tsResponse->getTimestampFromResponseFile()));

        // create a zip archive with the timestamped pdf and the asn1 token
        $zipPath = FsTools::getCacheFile() . '.zip';
        $ZipArchive = new ZipArchive();
        $ZipArchive->open($zipPath, ZipArchive::CREATE);
        $ZipArchive->addFile($pdfPath, $pdfName);
        $ZipArchive->addFile($tsResponse->getTokenPath(), $tokenName);
        $ZipArchive->close();
        return $this->Entity->Uploads->create(new CreateImmutableUpload($zipName, $zipPath, sprintf(_('Timestamp archive by %s'), $this->Entity->Users->userData['fullname'])));
    }

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    abstract public function getTimestampParameters(): array;

    /**
     * Generate the pdf to timestamp
     */
    public function generatePdf(): string
    {
        $userData = $this->Entity->Users->userData;
        $MpdfProvider = new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            true, // PDF/A always for timestamp pdf
        );
        $log = (new Logger('elabftw'))->pushHandler(new ErrorLogHandler());
        $MakePdf = new MakeTimestampPdf($log, $MpdfProvider, $this->Entity, array($this->Entity->id));
        if ($this->configArr['keeex_enabled'] === '1') {
            $Keeex = new MakeKeeex(new Client());
            return $Keeex->fromString($MakePdf->getFileContent());
        }
        return $MakePdf->getFileContent();
    }

    /**
     * Convert the time found in the response file to the correct format for sql insertion
     */
    protected function formatResponseTime(string $timestamp): string
    {
        $time = strtotime($timestamp);
        if ($time === false) {
            throw new ImproperActionException('Could not get response time!');
        }
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * Set the experiment as timestamped so we can easily display it
     *
     * @param string $responseTime the date of the timestamp
     */
    private function updateTimestamp(string $responseTime): bool
    {
        $sql = 'UPDATE experiments SET
            timestamped = 1,
            timestampedby = :userid,
            timestamped_at = :when
            WHERE id = :id;';
        $req = $this->Db->prepare($sql);
        // the date recorded in the db will match the creation time of the timestamp token
        $req->bindParam(':when', $responseTime);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    private function checkMonthlyLimit(): void
    {
        $limit = (int) $this->configArr['ts_limit'];
        // a limit of 0 means no limit
        if ($limit === 0) {
            return;
        }
        if ($this->Entity->getTimestampLastMonth() >= $limit) {
            throw new ImproperActionException(_('Number of timestamps this past month reached the limit!'));
        }
    }
}
