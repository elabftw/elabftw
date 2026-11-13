<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AuditCategory;
use Elabftw\Interfaces\AuditEventInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Override;
use PDO;

use function array_map;

/**
 * Deal with auditable events stored in audit_logs table
 */
final class AuditLogs extends AbstractRest
{
    // the table grows with every login, so never send more rows than a browser can handle in one go
    public const int MAX_LIMIT = 10000;

    public function __construct(private Users $requester)
    {
        parent::__construct();
    }

    public static function create(AuditEventInterface $event): int
    {
        if (Config::getConfig()->configArr['emit_audit_logs'] === '1') {
            $Logger = App::getDefaultLogger();
            $Logger->info($event->getJsonBody());
        }

        $Db = Db::getConnection();
        $sql = 'INSERT INTO audit_logs(body, category, requester_userid, target_userid) VALUES(:body, :category, :requester, :target)';
        $req = $Db->prepare($sql);
        $req->bindValue(':body', $event->getBody());
        $req->bindValue(':category', $event->getCategory()->value, PDO::PARAM_INT);
        $req->bindValue(':requester', $event->getRequesterUserid(), PDO::PARAM_INT);
        $req->bindValue(':target', $event->getTargetUserid(), PDO::PARAM_INT);
        $Db->execute($req);

        return $Db->lastInsertId();
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/audit_logs/';
    }

    /**
     * Read events with the names of involved users, most recent first
     * At most MAX_LIMIT events are returned, use offset to read older ones
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->requester->isSysadminOrExplode();
        $limit = $queryParams?->getLimit() ?? 0;
        // a limit of 0 (no limit query parameter) still gets capped
        if ($limit <= 0 || $limit > self::MAX_LIMIT) {
            $limit = self::MAX_LIMIT;
        }
        $offset = $queryParams?->getOffset() ?? 0;
        // sort on id too, as several events can share the same created_at second
        $sql = "SELECT audit_logs.id, audit_logs.created_at, audit_logs.category,
            audit_logs.requester_userid, audit_logs.target_userid, audit_logs.body,
            CONCAT(requester.firstname, ' ', requester.lastname) AS requester_fullname,
            CONCAT(target.firstname, ' ', target.lastname) AS target_fullname
            FROM audit_logs
            LEFT JOIN users AS requester ON (requester.userid = audit_logs.requester_userid)
            LEFT JOIN users AS target ON (target.userid = audit_logs.target_userid)
            ORDER BY audit_logs.created_at DESC, audit_logs.id DESC
            LIMIT :limit OFFSET :offset";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':limit', $limit, PDO::PARAM_INT);
        $req->bindValue(':offset', $offset, PDO::PARAM_INT);
        $this->Db->execute($req);

        return array_map(
            static function (array $event): array {
                $event['category_name'] = AuditCategory::tryFrom((int) $event['category'])->name ?? (string) $event['category'];
                return $event;
            },
            $req->fetchAll(),
        );
    }
}
