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

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Env;
use Elabftw\Interfaces\ContentParamsInterface;
use PDO;

use function is_string;
use function rtrim;
use function sprintf;
use function strtr;

/**
 * All about changelog tables
 */
final class Changelog
{
    private Db $Db;

    public function __construct(private AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
    }

    public function create(ContentParamsInterface $params): bool
    {
        // edge case when creating team with non existing user during populate action for dev
        if (empty($this->entity->Users->userData['userid'])) {
            return false;
        }
        // we don't store the full body, let the revisions system handle that
        $content = $params->getUnfilteredContent();
        if ($params->getTarget() === 'body' || $params->getTarget() === 'bodyappend') {
            // skip creation if the new body is the same as old body
            if ($this->entity->entityData['body'] === $content) {
                return false;
            }
            /** @psalm-suppress PossiblyNullArgument */
            $content = sprintf('Depending on your instance configuration, the change in the body is possibly recorded in the revisions. <a href="revisions.php?type=%s&amp;item_id=%d">See revisions page.</a>', $this->entity->entityType->value, $this->entity->id);
        }
        $sql = 'INSERT INTO ' . $this->entity->entityType->value . '_changelog (entity_id, users_id, target, content) VALUES (:entity_id, :users_id, :target, :content)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $req->bindParam(':users_id', $this->entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':target', $params->getTarget());
        $req->bindParam(':content', $content);
        return $this->Db->execute($req);
    }

    public function readAll(): array
    {
        $sql = "SELECT ch.created_at, ch.target, ch.content, CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM " . $this->entity->entityType->value . '_changelog AS ch LEFT JOIN users ON (users.userid = ch.users_id)
            WHERE entity_id = :entity_id ORDER BY created_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * This function exists to convert the revisions.php url into absolute url for pdf export.
     * We don't store the absolute url directly so it doesn't break on url change in web mode.
     */
    public function readAllWithAbsoluteUrls(): array
    {
        $changes = $this->readAll();
        $base = rtrim(Env::asUrl('SITE_URL'), '/');
        foreach ($changes as &$change) {
            // content can be NULL, which will make str_replace explode
            if (is_string($change['content'])) {
                $change['content'] = strtr($change['content'], array(
                    'href="revisions.php?type' => sprintf('href="%s/revisions.php?type', $base),
                    'href="/experiments.php' => sprintf('href="%s/experiments.php', $base),
                    'href="/database.php' => sprintf('href="%s/database.php', $base),
                ));
            }
        }
        return $changes;
    }
}
