<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use DateTimeImmutable;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use PDO;

use function sprintf;

class EntitySlugsSqlBuilder
{
    protected Db $Db;

    public function __construct(
        private Users $targetUser,
        private DateTimeImmutable $start = new DateTimeImmutable('500 years ago'),
        private DateTimeImmutable $end = new DateTimeImmutable('tomorrow'),
        private bool $withExperiments = true,
        private bool $withItems = false,
        private bool $withTemplates = false,
        private bool $withItemsTypes = false,
        private ?int $experimentsCategoryFilter = null,
        private ?int $itemsCategoryFilter = null,
        private ?int $templatesCategoryFilter = null,
        private ?int $itemsTypesCategoryFilter = null,
    ) {
        $this->Db = Db::getConnection();
    }

    public function getAllEntitySlugs(): array
    {
        $sql = $this->getSql();
        $req = $this->Db->prepare($sql);
        if (str_contains($sql, ':userid')) {
            $req->bindParam(':userid', $this->targetUser->userid, PDO::PARAM_INT);
        }
        if (str_contains($sql, ':team')) {
            $req->bindValue(':team', $this->targetUser->team);
        }
        $req->bindValue(':start', $this->start->format('Y-m-d'));
        $req->bindValue(':end', $this->end->format('Y-m-d'));
        $this->Db->execute($req);
        $slugs = array_column($req->fetchAll(), 'slug');
        return array_map('\Elabftw\Elabftw\EntitySlug::fromString', $slugs);
    }

    private function getSql(): string
    {
        $sql = '';

        if ($this->withExperiments) {
            $sql .= 'SELECT CONCAT("experiments:", experiments.id) AS slug FROM experiments WHERE experiments.userid = :userid AND modified_at BETWEEN :start AND :end';
            if (is_int($this->experimentsCategoryFilter)) {
                $sql .= sprintf(' AND experiments.category = %d', $this->experimentsCategoryFilter);
            }
        }
        if ($this->withItems) {
            if (!empty($sql)) {
                $sql .= ' UNION ALL ';
            }
            $sql .= 'SELECT CONCAT("items:", items.id) AS slug FROM items WHERE items.userid = :userid AND modified_at BETWEEN :start AND :end';
            if (is_int($this->itemsCategoryFilter)) {
                $sql .= sprintf(' AND items.category = %d', $this->itemsCategoryFilter);
            }
        }
        if ($this->withTemplates) {
            if (!empty($sql)) {
                $sql .= ' UNION ALL ';
            }
            $sql .= 'SELECT CONCAT("experiments_templates:", experiments_templates.id) AS slug FROM experiments_templates WHERE experiments_templates.userid = :userid AND modified_at BETWEEN :start AND :end';
            if (is_int($this->templatesCategoryFilter)) {
                $sql .= sprintf(' AND experiments_templates.category = %d', $this->templatesCategoryFilter);
            }
        }
        if ($this->withItemsTypes) {
            if (!empty($sql)) {
                $sql .= ' UNION ALL ';
            }
            $sql .= 'SELECT CONCAT("items_types:", items_types.id) AS slug FROM items_types WHERE items_types.team = :team AND modified_at BETWEEN :start AND :end';
            if (is_int($this->itemsTypesCategoryFilter)) {
                $sql .= sprintf(' AND items_types.category = %d', $this->itemsTypesCategoryFilter);
            }
        }
        if (empty($sql)) {
            throw new ImproperActionException('Nothing to export!');
        }
        return $sql;
    }
}
