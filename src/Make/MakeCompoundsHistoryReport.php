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

use DateTimeImmutable;
use Elabftw\Models\Compounds;
use Override;
use PDO;

/**
 * Make a CSV file with all the compounds associated with a user over a period
 */
final class MakeCompoundsHistoryReport extends MakeCompoundsReport
{
    public function __construct(protected Compounds $compounds, private DateTimeImmutable $start, private DateTimeImmutable $end)
    {
        parent::__construct($compounds);
    }

    #[Override]
    protected function getData(): array
    {
        $sql = 'SELECT
              c.*,
              e.created_at AS experiment_created_at,
              e.title      AS experiment_title,
              e.id         AS experiment_id
            FROM experiments AS e
            JOIN (
              SELECT c2e.entity_id AS experiment_id, c2e.compound_id
              FROM compounds2experiments AS c2e

              UNION DISTINCT

              SELECT e2i.item_id AS experiment_id, c2i.compound_id
              FROM experiments2items AS e2i
              JOIN compounds2items   AS c2i
                ON c2i.entity_id = e2i.link_id
            ) AS ec
              ON ec.experiment_id = e.id
            JOIN compounds AS c
              ON c.id = ec.compound_id
            WHERE e.userid = :userid
              AND DATE(e.date) >= :start
              AND DATE(e.date) <= :end
            ORDER BY e.date DESC, c.name ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->compounds->requester->userid, PDO::PARAM_INT);
        $req->bindValue(':start', $this->start->format('Y-m-d'));
        $req->bindValue(':end', $this->end->format('Y-m-d'));
        $this->Db->execute($req);
        return $req->fetchAll();
    }
}
