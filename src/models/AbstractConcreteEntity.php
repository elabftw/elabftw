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

use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\DisplayParams;
use PDO;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Override;

use function is_string;
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
        // force metadata to be a string
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
                template: (int) ($reqBody['template'] ?? $reqBody['category_id'] ?? $category ?? -1),
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
        $exclusiveEditMode = $this->ExclusiveEditMode->readOne();
        $this->entityData['exclusive_edit_mode'] = empty($exclusiveEditMode) ? null : $exclusiveEditMode;
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
