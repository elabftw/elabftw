<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Controllers\DownloadController;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\EntitySlugsSqlBuilder;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Invoker;
use Elabftw\Enums\Action;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\Users;
use Elabftw\Services\Filter;
use Elabftw\Services\MpdfProvider;
use Elabftw\Traits\SetIdTrait;
use Exception;
use League\Flysystem\Filesystem;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PDO;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use ValueError;
use ZipStream\ZipStream;

use function hash_file;

/**
 * Handle data exports
 */
class Exports implements RestInterface
{
    use SetIdTrait;

    private const string HASH_ALGO = 'sha256';

    // a given user cannot have more than this number of export requests
    private const int MAX_EXPORT = 6;

    protected Db $Db;

    private Filesystem $fs;

    public function __construct(private Users $requester, private StorageInterface $storage, public ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->fs = $storage->getFs();
        $this->setId($id);
    }

    public function readAll(): array
    {
        $sql = 'SELECT * FROM exports
            WHERE requester_userid = :requester_userid
            ORDER BY created_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':requester_userid', $this->requester->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function readOne(): array
    {
        $sql = 'SELECT * FROM exports WHERE id = :id AND requester_userid = :requester_userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':requester_userid', $this->requester->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function readBinary(): Response
    {
        $export = $this->readOne();

        $DownloadController = new DownloadController(
            $this->fs,
            $export['long_name'],
            $export['real_name'],
            true,
        );
        return $DownloadController->getResponse();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        if (count($this->readAll()) >= self::MAX_EXPORT) {
            throw new ImproperActionException(
                sprintf(
                    _('Cannot store more than %d exports. Delete previous exports to be permitted to create new ones.'),
                    self::MAX_EXPORT
                )
            );
        }
        try {
            $format = ExportFormat::from($reqBody['format'] ?? '');
        } catch (ValueError $e) {
            throw new ImproperActionException('Improper value for format: ' . $e->getMessage());
        }
        $sql = 'INSERT INTO exports (requester_userid, format, experiments, experiments_templates, items, items_types, changelog, json, pdfa, team)
            VALUES (:requester_userid, :format, :experiments, :experiments_templates, :items, :items_types, :changelog, :json, :pdfa, :team)';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':requester_userid', $this->requester->userid, PDO::PARAM_INT);
        $req->bindValue(':format', $format->value);
        $req->bindValue(':experiments', Filter::onToBinary($reqBody['experiments']));
        $req->bindValue(':experiments_templates', Filter::onToBinary($reqBody['experiments_templates']));
        $req->bindValue(':items', Filter::onToBinary($reqBody['items']));
        $req->bindValue(':items_types', Filter::onToBinary($reqBody['items_types']));
        $req->bindValue(':changelog', Filter::onToBinary($reqBody['changelog']));
        $req->bindValue(':json', Filter::onToBinary($reqBody['json']));
        $req->bindValue(':pdfa', Filter::onToBinary($reqBody['pdfa']));
        $req->bindParam(':team', $this->requester->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        $id = $this->Db->lastInsertId();
        // launch an asynchronous task immediately
        $Invoker = new Invoker();
        $Invoker->write(sprintf('export:process %d', $id));
        return $id;
    }

    // use this to call something immediately
    public function process(): int
    {
        $sql = 'SELECT * FROM exports WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $request = $this->Db->fetch($req);
        try {
            $this->processRequest($request);
        } catch (Exception $e) {
            $this->update('state', State::Error->value);
            $this->update('error', $e->getMessage());
            return State::Error->value;
        }
        return 0;
    }

    public function processPending(): int
    {
        $requests = $this->readPending();
        foreach ($requests as $request) {
            try {
                $this->processRequest($request);
            } catch (Exception $e) {
                $this->update('state', State::Error->value);
                $this->update('error', $e->getMessage());
            }
        }
        return 0;
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No PATCH action for this endpoint.');
    }

    public function getApiPath(): string
    {
        return 'api/v2/export/';
    }

    public function destroy(): bool
    {
        $request = $this->readOne();
        if ($request['long_name']) {
            $this->fs->delete($request['long_name']);
        }

        $sql = 'DELETE FROM exports WHERE id = :id AND requester_userid = :requester_userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':requester_userid', $this->requester->userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function readPending(): array
    {
        $sql = 'SELECT * FROM exports WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Pending->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    private function processRequest(array $request): State
    {
        $this->setId($request['id']);
        $this->requester = new Users($request['requester_userid'], $request['team']);
        $this->update('state', State::Processing->value);
        $longName = FsTools::getUniqueString();
        $absolutePath = $this->storage->getPath($longName);
        try {
            $format = ExportFormat::from($request['format']);
        } catch (ValueError $e) {
            throw new ImproperActionException('Improper value for format: ' . $e->getMessage());
        }

        $realName = sprintf(
            'export-%s.%s',
            date('Y-m-d_H-i-s'),
            $format->value,
        );
        $includeChangelog = (bool) $request['changelog'];
        $usePdfa = (bool) $request['pdfa'];
        $includeJson = (bool) $request['json'];
        $withExperiments = (bool) $request['experiments'];
        $withItems = (bool) $request['items'];
        $withTemplates = (bool) $request['experiments_templates'];
        $withItemsTypes = (bool) $request['items_types'];
        $builder = new EntitySlugsSqlBuilder(
            targetUser: $this->requester,
            withExperiments: $withExperiments,
            withItems: $withItems,
            withTemplates: $withTemplates,
            withItemsTypes: $withItemsTypes,
        );
        $entitySlugs = $builder->getAllEntitySlugs();
        $entityArr = array();
        foreach ($entitySlugs as $slug) {
            try {
                $entityArr[] = $slug->type->toInstance($this->requester, $slug->id);
            } catch (IllegalActionException) {
                // TODO figure out why we encounter instances with no read access in the first place
                continue;
            }
        }

        switch ($format) {
            case ExportFormat::Eln:
            case ExportFormat::Zip:
                $fileStream = fopen($absolutePath, 'wb');
                if ($fileStream === false) {
                    throw new RuntimeException('Could not open output stream!');
                }
                $ZipStream = new ZipStream(sendHttpHeaders: false, outputStream: $fileStream);
                if ($format === ExportFormat::Eln) {
                    $Maker = new MakeEln($ZipStream, $this->requester, $entityArr);
                } else {
                    $Maker = new MakeBackupZip($ZipStream, $this->requester, $entityArr, $usePdfa, $includeChangelog, $includeJson);
                };
                $Maker->getStreamZip();
                fclose($fileStream);
                break;
            case ExportFormat::Pdf:
                $log = (new Logger('elabftw'))->pushHandler(new ErrorLogHandler());
                $mpdfProvider = new MpdfProvider(
                    $this->requester->userData['fullname'],
                    $this->requester->userData['pdf_format'],
                    $usePdfa,
                );
                $Maker = new MakeMultiPdf($log, $mpdfProvider, $this->requester, $entityArr, $includeChangelog);
                $this->fs->write($longName, $Maker->getFileContent());
                break;
            case ExportFormat::Json:
                $Maker = new MakeFullJson($entityArr);
                $this->fs->write($longName, $Maker->getFileContent());
                break;
            default:
                throw new ImproperActionException('Incorrect export format');
        }
        $this->update('long_name', $longName);
        $this->update('real_name', $realName);
        $this->update('filesize', $this->fs->fileSize($longName));
        $hash = hash_file(self::HASH_ALGO, $absolutePath);
        if ($hash === false) {
            $hash = 'error computing hash';
        }
        $this->update('hash', $hash);
        $this->update('hash_algo', self::HASH_ALGO);
        $this->update('state', State::Normal->value);
        return State::Normal;
    }

    private function update(string $column, string|int $value): bool
    {
        $sql = 'UPDATE exports SET ' . $column . ' = :value WHERE id = :id AND requester_userid = :requester_userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':value', $value);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':requester_userid', $this->requester->userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
