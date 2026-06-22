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

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\State;
use Override;
use PDO;

use function is_int;

/**
 * An entity like Templates or ItemsTypes. Template as opposed to Concrete: Experiments and Items
 */
abstract class AbstractTemplateEntity extends AbstractEntity
{
    /**
     * Get an id of an existing one or create it and get its id
     */
    public function getIdempotentIdFromTitle(string $title): int
    {
        $sql = 'SELECT id
            FROM ' . $this->entityType->value . ' WHERE title = :title AND team = :team AND state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch(PDO::FETCH_COLUMN);
        if (!is_int($res)) {
            return $this->create(title: $title);
        }
        return $res;
    }

    #[Override]
    public function readOne(): array
    {
        $this->entityData = parent::readOne();
        $this->entityData['canread_target_base_human'] = BasePermissions::from($this->entityData['canread_target_base'])->toHuman();
        $this->entityData['canwrite_target_base_human'] = BasePermissions::from($this->entityData['canwrite_target_base'])->toHuman();
        return $this->entityData;
    }

    #[Override]
    public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int
    {
        $this->canOrExplode(AccessType::Read);

        return $this->copyEntityFrom(
            sourceEntity: $this,
            title: $this->entityData['title'] . ' I',
            copyFiles: $copyFiles,
        );
    }
}
