<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\AuditEvent\SignatureCreated;
use Elabftw\Elabftw\CreateImmutableArchivedUpload;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Enums\Action;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\Meaning;
use Elabftw\Enums\RequestableAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CreateFromTemplateInterface;
use Elabftw\Interfaces\MakeTrustedTimestampInterface;
use Elabftw\Make\MakeBloxberg;
use Elabftw\Make\MakeCustomTimestamp;
use Elabftw\Make\MakeDfnTimestamp;
use Elabftw\Make\MakeDgnTimestamp;
use Elabftw\Make\MakeDigicertTimestamp;
use Elabftw\Make\MakeFullJson;
use Elabftw\Make\MakeGlobalSignTimestamp;
use Elabftw\Make\MakeSectigoTimestamp;
use Elabftw\Make\MakeUniversignTimestamp;
use Elabftw\Make\MakeUniversignTimestampDev;
use Elabftw\Services\SignatureHelper;
use Elabftw\Services\TimestampUtils;
use GuzzleHttp\Client;
use ZipArchive;

/**
 * An entity like Experiments or Items. Concrete as opposed to TemplateEntity for experiments templates or items types
 */
abstract class AbstractConcreteEntity extends AbstractEntity implements CreateFromTemplateInterface
{
    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create((int) ($reqBody['category_id'] ?? -1), $reqBody['tags'] ?? array()),
            Action::Duplicate => $this->duplicate(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
    }

    public function patch(Action $action, array $params): array
    {
        // was "write" previously, but let's make timestamping/signing only require read access
        $this->canOrExplode('read');
        return match ($action) {
            Action::Bloxberg => $this->bloxberg(),
            Action::Sign => $this->sign($params['passphrase'], Meaning::from((int) $params['meaning'])),
            Action::Timestamp => $this->timestamp(),
            default => parent::patch($action, $params),
        };
    }

    protected function bloxberg(): array
    {
        $Maker = new MakeBloxberg(Config::getConfig()->configArr, $this, new Client());
        $Maker->timestamp();
        return $this->readOne();
    }

    abstract protected function getNextCustomId(int $category): ?int;

    protected function getTimestampMaker(array $config, ExportFormat $dataFormat): MakeTrustedTimestampInterface
    {
        return match ($config['ts_authority']) {
            'dfn' => new MakeDfnTimestamp($config, $this, $dataFormat),
            'dgn' => new MakeDgnTimestamp($config, $this, $dataFormat),
            'universign' => $config['debug'] ? new MakeUniversignTimestampDev($config, $this, $dataFormat) : new MakeUniversignTimestamp($config, $this, $dataFormat),
            'digicert' => new MakeDigicertTimestamp($config, $this, $dataFormat),
            'sectigo' => new MakeSectigoTimestamp($config, $this, $dataFormat),
            'globalsign' => new MakeGlobalSignTimestamp($config, $this, $dataFormat),
            'custom' => new MakeCustomTimestamp($config, $this, $dataFormat),
            default => throw new ImproperActionException('Incorrect timestamp authority configuration.'),
        };
    }

    protected function timestamp(): array
    {
        $Config = Config::getConfig();

        // the source data can be in any format, here it defaults to json but can be pdf too
        $dataFormat = ExportFormat::Json;
        // if we do keeex we want to timestamp a pdf so we can keeex it
        // there might be other options impacting this condition later
        if ($Config->configArr['keeex_enabled'] === '1') {
            $dataFormat = ExportFormat::Pdf;
        }

        // select the timestamp service and do the timestamp request to TSA
        $Maker = $this->getTimestampMaker($Config->configArr, $dataFormat);
        $TimestampUtils = new TimestampUtils(
            new Client(),
            $Maker->generateData(),
            $Maker->getTimestampParameters(),
            new TimestampResponse(),
        );

        // save the token and data in a zip archive
        $zipName = $Maker->getFileName();
        $zipPath = FsTools::getCacheFile() . '.zip';
        $comment = sprintf(_('Timestamp archive by %s'), $this->Users->userData['fullname']);
        $Maker->saveTimestamp(
            $TimestampUtils->timestamp(),
            new CreateImmutableArchivedUpload($zipName, $zipPath, $comment),
        );

        // decrement the balance
        $Config->decrementTsBalance();

        // clear any request action
        $RequestActions = new RequestActions($this->Users, $this);
        $RequestActions->remove(RequestableAction::Timestamp);

        return $this->readOne();
    }

    protected function sign(string $passphrase, Meaning $meaning): array
    {
        $Sigkeys = new SignatureHelper($this->Users);
        $Maker = new MakeFullJson($this, array($this->id));
        $message = $Maker->getFileContent();
        $signature = $Sigkeys->serializeSignature($this->Users->userData['sig_privkey'], $passphrase, $message, $meaning);
        $SigKeys = new SigKeys($this->Users);
        $SigKeys->touch();
        $Comments = new ImmutableComments($this);
        $comment = sprintf(_('Signed by %s (%s)'), $this->Users->userData['fullname'], $meaning->name);
        $Comments->postAction(Action::Create, array('comment' => $comment));
        // save the signature and data in a zip archive
        $zipPath = FsTools::getCacheFile() . '.zip';
        $comment = sprintf(_('Signature archive by %s (%s)'), $this->Users->userData['fullname'], $meaning->name);
        $ZipArchive = new ZipArchive();
        $ZipArchive->open($zipPath, ZipArchive::CREATE);
        $ZipArchive->addFromString('data.json.minisig', $signature);
        $ZipArchive->addFromString('data.json', $message);
        $ZipArchive->addFromString('key.pub', $this->Users->userData['sig_pubkey']);
        $ZipArchive->addFromString('verify.sh', "#!/bin/sh\nminisign -H -V -p key.pub -m data.json\n");
        $ZipArchive->close();
        $this->Uploads->create(new CreateImmutableArchivedUpload('signature archive.zip', $zipPath, $comment));
        $RequestActions = new RequestActions($this->Users, $this);
        $RequestActions->remove(RequestableAction::Sign);
        AuditLogs::create(new SignatureCreated($this->Users->userData['userid'], $this->id ?? 0, $this->entityType));
        return $this->readOne();
    }
}
