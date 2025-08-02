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

use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
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
            ),
            Action::Duplicate => $this->duplicate((bool) ($reqBody['copyFiles'] ?? false), (bool) ($reqBody['linkToOriginal'] ?? false)),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
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
