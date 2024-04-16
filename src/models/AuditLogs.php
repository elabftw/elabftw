<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\AuditCategory;
use Elabftw\Interfaces\AuditEventInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PDO;

/**
 * Deal with auditable events stored in audit_logs table
 */
class AuditLogs
{
    public const DEFAULT_LIMIT = 50;

    public static function create(AuditEventInterface $event): int
    {
        if (Config::getConfig()->configArr['emit_audit_logs'] === '1') {
            $Logger = new Logger('elabftw');
            $Logger->pushHandler(new ErrorLogHandler());
            $message = sprintf(
                '%s %s requester_userid: %d, target_userid: %d',
                AuditCategory::from($event->getCategory())->name,
                $event->getBody(),
                $event->getRequesterUserid(),
                $event->getTargetUserid(),
            );
            $Logger->notice($message);
        }

        $Db = Db::getConnection();
        $sql = 'INSERT INTO audit_logs(body, category, requester_userid, target_userid) VALUES(:body, :category, :requester, :target)';
        $req = $Db->prepare($sql);
        $req->bindValue(':body', $event->getBody());
        $req->bindValue(':category', $event->getCategory());
        $req->bindValue(':requester', $event->getRequesterUserid());
        $req->bindValue(':target', $event->getTargetUserid());
        $Db->execute($req);

        return $Db->lastInsertId();
    }

    public static function read(int $limit = self::DEFAULT_LIMIT, int $offset = 0): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $req = $Db->prepare($sql);
        $req->bindParam(':limit', $limit, PDO::PARAM_INT);
        $req->bindParam(':offset', $offset, PDO::PARAM_INT);
        $Db->execute($req);

        return $req->fetchAll();
    }
}
