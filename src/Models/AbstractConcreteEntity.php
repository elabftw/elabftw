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

use Elabftw\Enums\State;
use Elabftw\Factories\LinksFactory;
use PDO;
use Override;

use function sprintf;

/**
 * An entity like Experiments or Items. Concrete as opposed to TemplateEntity for experiments templates or items types
 */
abstract class AbstractConcreteEntity extends AbstractEntity
{
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
