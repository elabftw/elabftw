<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Enums\EntityType;
use Elabftw\Interfaces\QueryParamsInterface;
use Override;
use PDO;

/**
 * Get extra fields keys of items and experiments used for autocomplete on search page.
 */
class ExtraFieldsKeys extends AbstractRest
{
    public function __construct(private Users $Users, private string $searchTerm, private int $limit = 0)
    {
        parent::__construct();
        $this->limit = $this->limit < -1 || $this->limit === 0 ? $this->Users->userData['limit_nb'] : $this->limit;
    }

    public function getApiPath(): string
    {
        return 'api/v2/extra_fields_keys';
    }

    /**
     * Get all exta fields keys of a team from experiments and items
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = array();
        foreach (array(EntityType::Items, EntityType::Experiments) as $entityType) {
            $sql[] = sprintf(
                'SELECT JSON_UNQUOTE(`extra_fields_key`) AS `extra_fields_key`, COUNT(`id`) as `frequency`
                    FROM %s AS `entity`
                    LEFT JOIN `users` ON (
                        `entity`.`userid` = `users`.`userid`
                    )
                    LEFT JOIN `users2teams` ON (
                        `users2teams`.`users_id` = `users`.`userid`
                        AND `users2teams`.`teams_id` = %d
                    )
                    JOIN JSON_TABLE (
                        JSON_KEYS(`entity`.`metadata`, "$.extra_fields"),
                        "$[*]" COLUMNS (
                            `extra_fields_key` JSON path "$"
                        )
                    ) AS `extra_fields_keys_table`
                    # Need to CAST here to retain case-insensitive comparison
                    WHERE CAST(`extra_fields_key` AS CHAR) LIKE :search_term
                    %s
                    GROUP BY `extra_fields_key`',
                $entityType->value,
                $this->Users->userData['team'],
                (new EntitySqlBuilder($entityType->toInstance($this->Users)))->getCanFilter('canread'),
            );
        }

        $finalSql = sprintf(
            'SELECT `extra_fields_key`, CAST(SUM(`frequency`) AS UNSIGNED) AS `frequency`
                FROM (%s) AS `finalTable`
                GROUP BY `extra_fields_key`
                ORDER BY `frequency` DESC, `extra_fields_key` ASC
                %s',
            implode(' UNION ', $sql),
            $this->limit > 0 ? 'LIMIT :limit' : '',
        );

        $req = $this->Db->prepare($finalSql);
        $req->bindValue(':search_term', '%' . $this->searchTerm . '%');
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        if ($this->limit > 0) {
            $req->bindParam(':limit', $this->limit, PDO::PARAM_INT);
        }

        $this->Db->execute($req);

        return $req->fetchAll();
    }
}
