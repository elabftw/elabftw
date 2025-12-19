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
        $sql = 'SELECT c.*, e.created_at AS experiment_created_at, e.title AS experiment_title, e.id AS experiment_id
            FROM compounds AS c
            JOIN compounds2experiments AS c2e
              ON c.id = c2e.compound_id
            JOIN experiments AS e
              ON e.id = c2e.entity_id
            WHERE e.userid = :userid AND DATE(e.created_at) >= :start AND DATE(e.created_at) <= :end
            ORDER BY e.created_at DESC, c.name ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->compounds->requester->userid, PDO::PARAM_INT);
        $req->bindValue(':start', $this->start->format('Y-m-d'));
        $req->bindValue(':end', $this->end->format('Y-m-d'));
        $this->Db->execute($req);
        return $req->fetchAll();
    }
}
