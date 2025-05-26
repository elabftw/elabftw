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
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\Meaning;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Interfaces\MakeTrustedTimestampInterface;
use Elabftw\Interfaces\QueryParamsInterface;
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
use Elabftw\Params\DisplayParams;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\SignatureHelper;
use Elabftw\Services\TimestampUtils;
use GuzzleHttp\Client;
use PDO;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;
use Override;

use function is_string;
use function json_decode;
use function ksort;
use function sprintf;

/**
 * An entity like Experiments or Items. Concrete as opposed to TemplateEntity for experiments templates or items types
 */
abstract class AbstractConcreteEntity extends AbstractEntity
{
    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $Teams = new Teams($this->Users, $this->Users->team);
        $teamConfigArr = $Teams->readOne();
        // convert to int only if not empty, otherwise send null: we don't want to convert a null to int, as it would send 0
        $category = !empty($reqBody['category']) ? (int) $reqBody['category'] : null;
        $status = !empty($reqBody['status']) ? (int) $reqBody['status'] : null;
        $metadata = null;
        if (!empty($reqBody['metadata'])) {
            $metadata = json_encode($reqBody['metadata'], JSON_THROW_ON_ERROR);
        }
        // force tags to be an array
        $tags = $reqBody['tags'] ?? null;
        if (is_string($tags)) {
            $tags = array($tags);
        }
        return match ($action) {
            Action::Create => $this->create(
                // the category_id is there for backward compatibility (changed in 5.1)
                template: (int) ($reqBody['template'] ?? $reqBody['category_id'] ?? -1),
                body: $reqBody['body'] ?? null,
                title: $reqBody['title'] ?? null,
                canread: $reqBody['canread'] ?? null,
                canwrite: $reqBody['canwrite'] ?? null,
                canreadIsImmutable: (bool) ($reqBody['canread_is_immutable'] ?? false),
                canwriteIsImmutable: (bool) ($reqBody['canwrite_is_immutable'] ?? false),
                tags: $tags ?? array(),
                category: $category,
                status: $status,
                metadata: $metadata,
                forceExpTpl: (bool) $teamConfigArr['force_exp_tpl'],
                defaultTemplateHtml: $teamConfigArr['common_template'] ?? '',
                defaultTemplateMd: $teamConfigArr['common_template_md'] ?? '',
            ),
            Action::Duplicate => $this->duplicate((bool) ($reqBody['copyFiles'] ?? false), (bool) ($reqBody['linkToOriginal'] ?? false)),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
    }

    #[Override]
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

    /**
     * Read all from one entity
     */
    #[Override]
    public function readOne(): array
    {
        if ($this->id === null) {
            throw new IllegalActionException('No id was set!');
        }
        // build query params for Uploads
        $queryParams = $this->getQueryParams(Request::createFromGlobals()->query);
        $EntitySqlBuilder = new EntitySqlBuilder($this);
        $sql = $EntitySqlBuilder->getReadSqlBeforeWhere(true, true);

        $sql .= sprintf(' WHERE entity.id = %d', $this->id);

        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $this->entityData = $this->Db->fetch($req);
        // Note: this is returning something with all values set to null instead of resource not found exception if the id is incorrect.
        if ($this->entityData['id'] === null) {
            throw new ResourceNotFoundException();
        }
        $this->canOrExplode('read');
        $this->entityData['steps'] = $this->Steps->readAll();
        $this->entityData['experiments_links'] = $this->ExperimentsLinks->readAll();
        $this->entityData['items_links'] = $this->ItemsLinks->readAll();
        $this->entityData['related_experiments_links'] = $this->ExperimentsLinks->readRelated();
        $this->entityData['related_items_links'] = $this->ItemsLinks->readRelated();
        $this->entityData['uploads'] = $this->Uploads->readAll($queryParams);
        $this->entityData['comments'] = $this->Comments->readAll();
        $this->entityData['page'] = substr($this->entityType->toPage(), 0, -4);
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $this->entityData['compounds'] = $CompoundsLinks->readAll();
        $ContainersLinks = LinksFactory::getContainersLinks($this);
        $this->entityData['containers'] = $ContainersLinks->readAll();
        $this->entityData['sharelink'] = sprintf(
            '%s/%s?mode=view&id=%d%s',
            Config::fromEnv('SITE_URL'),
            $this->entityType->toPage(),
            $this->id,
            // add a share link
            !empty($this->entityData['access_key'])
                ? sprintf('&access_key=%s', $this->entityData['access_key'])
                : '',
        );
        // add the body as html
        $this->entityData['body_html'] = $this->entityData['body'];
        // convert from markdown only if necessary
        if ($this->entityData['content_type'] === self::CONTENT_MD) {
            $this->entityData['body_html'] = Tools::md2html($this->entityData['body'] ?? '');
        }
        if (!empty($this->entityData['metadata'])) {
            $this->entityData['metadata_decoded'] = json_decode($this->entityData['metadata']);
        }
        $this->entityData['exclusive_edit_mode'] = $this->ExclusiveEditMode->readOne();
        ksort($this->entityData);
        return $this->entityData;
    }

    #[Override]
    public function getQueryParams(?InputBag $query = null): DisplayParams
    {
        return new DisplayParams($this->Users, $this->entityType, $query);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        if (!$queryParams instanceof DisplayParams) {
            $Request = Request::createFromGlobals();
            $queryParams = $this->getQueryParams($Request->query);
        }
        return $this->readShow($queryParams, true);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canOrExplode('write');
        // mark all uploads related to that entity as deleted
        $sql = 'UPDATE uploads SET state = :state WHERE item_id = :entity_id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':type', $this->entityType->value);
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        // do same for compounds links and containers links
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $CompoundsLinks->destroyAll();
        $ContainersLinks = LinksFactory::getContainersLinks($this);
        $ContainersLinks->destroyAll();

        return parent::destroy();
    }

    /**
     * Count the number of timestamp archives created during past month (sliding window)
     * Here we merge bloxberg and trusted timestamp methods because there is no way currently to tell them apart
     */
    public function getTimestampLastMonth(): int
    {
        $sql = "SELECT COUNT(id) FROM uploads WHERE comment LIKE 'Timestamp archive%' = 1 AND created_at > (NOW() - INTERVAL 1 MONTH)";
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Get timestamper full name for display in view mode
     */
    #[Override]
    public function getTimestamperFullname(): string
    {
        if ($this->entityData['timestamped'] === 0) {
            return 'Unknown';
        }
        return $this->getFullnameFromUserid($this->entityData['timestampedby']);
    }

    public function timestamp(): array
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
            new CreateUpload($zipName, $zipPath, $comment, immutable: 1, state: State::Archived),
        );

        // decrement the balance
        $Config->decrementTsBalance();

        // clear any request action
        $RequestActions = new RequestActions($this->Users, $this);
        $RequestActions->remove(RequestableAction::Timestamp);

        return $this->readOne();
    }

    protected function bloxberg(): array
    {
        $configArr = Config::getConfig()->configArr;
        $HttpGetter = new HttpGetter(new Client(), $configArr['proxy']);
        $Maker = new MakeBloxberg(
            $this->Users,
            $this,
            $configArr,
            $HttpGetter,
        );
        $Maker->timestamp();
        return $this->readOne();
    }

    protected function getTimestampMaker(array $config, ExportFormat $dataFormat): MakeTrustedTimestampInterface
    {
        //$entitySlugs = array(new EntitySlug($this->entityType, $this->id ?? 0));
        return match ($config['ts_authority']) {
            'dfn' => new MakeDfnTimestamp($this->Users, $this, $config, $dataFormat),
            'dgn' => new MakeDgnTimestamp($this->Users, $this, $config, $dataFormat),
            'universign' => $config['debug'] ? new MakeUniversignTimestampDev($this->Users, $this, $config, $dataFormat) : new MakeUniversignTimestamp($this->Users, $this, $config, $dataFormat),
            'digicert' => new MakeDigicertTimestamp($this->Users, $this, $config, $dataFormat),
            'sectigo' => new MakeSectigoTimestamp($this->Users, $this, $config, $dataFormat),
            'globalsign' => new MakeGlobalSignTimestamp($this->Users, $this, $config, $dataFormat),
            'custom' => new MakeCustomTimestamp($this->Users, $this, $config, $dataFormat),
            default => throw new ImproperActionException('Incorrect timestamp authority configuration.'),
        };
    }

    protected function sign(string $passphrase, Meaning $meaning): array
    {
        $Sigkeys = new SignatureHelper($this->Users);
        $Maker = new MakeFullJson(array($this));
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
        // allow uploading a file to that entity because sign action only requires read access
        $this->Uploads->Entity->bypassWritePermission = true;
        $this->Uploads->create(new CreateUpload('signature archive.zip', $zipPath, $comment, immutable: 1, state: State::Archived));
        $RequestActions = new RequestActions($this->Users, $this);
        $RequestActions->remove(RequestableAction::Sign);
        AuditLogs::create(new SignatureCreated($this->Users->userData['userid'], $this->id ?? 0, $this->entityType));
        return $this->readOne();
    }

    protected function getNextCustomId(?int $category): ?int
    {
        if ($category === null) {
            return $category;
        }
        $sql = sprintf(
            'SELECT custom_id FROM %s WHERE custom_id IS NOT NULL AND category = :category
                ORDER BY custom_id DESC LIMIT 1',
            $this->entityType->value
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        if ($res === false || $res['custom_id'] === null) {
            return null;
        }
        return ++$res['custom_id'];
    }
}
