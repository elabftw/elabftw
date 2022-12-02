<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MakeTimestampInterface;
use Elabftw\Services\MakeBloxberg;
use Elabftw\Services\MakeCustomTimestamp;
use Elabftw\Services\MakeDfnTimestamp;
use Elabftw\Services\MakeDigicertTimestamp;
use Elabftw\Services\MakeGlobalSignTimestamp;
use Elabftw\Services\MakeSectigoTimestamp;
use Elabftw\Services\MakeUniversignTimestamp;
use Elabftw\Services\MakeUniversignTimestampDev;
use Elabftw\Services\TimestampUtils;
use Elabftw\Traits\InsertTagsTrait;
use GuzzleHttp\Client;
use PDO;

/**
 * All about the experiments
 */
class Experiments extends AbstractConcreteEntity
{
    use InsertTagsTrait;

    public function __construct(Users $users, ?int $id = null)
    {
        $this->page = parent::TYPE_EXPERIMENTS;
        $this->type = parent::TYPE_EXPERIMENTS;
        parent::__construct($users, $id);
    }

    public function create(int $template = -1, array $tags = array()): int
    {
        $Templates = new Templates($this->Users);
        $Teams = new Teams($this->Users);
        $teamConfigArr = $Teams->readOne();

        // defaults
        $title = _('Untitled');
        $body = null;
        $canread = 'team';
        $canwrite = 'user';
        $metadata = null;

        // do we want template ?
        // $templateId can be a template id, or 0: common template, or -1: null body
        if ($template > 0) {
            $Templates->setId($template);
            $templateArr = $Templates->readOne();
            $title = $templateArr['title'];
            $body = $templateArr['body'];
            $canread = $templateArr['canread'];
            $canwrite = $templateArr['canwrite'];
            $metadata = $templateArr['metadata'];
        }

        if ($template === 0) {
            // no template, make sure admin didn't disallow it
            if ($teamConfigArr['force_exp_tpl'] === 1) {
                throw new ImproperActionException(_('Experiments must use a template!'));
            }
            $commonTemplateKey = 'common_template';
            // use the markdown template if the user prefers markdown
            if ($this->Users->userData['use_markdown']) {
                $commonTemplateKey .= '_md';
            }
            $body = $teamConfigArr[$commonTemplateKey];
            if ($this->Users->userData['default_read'] !== null) {
                $canread = $this->Users->userData['default_read'];
            }
            if ($this->Users->userData['default_write'] !== null) {
                $canwrite = $this->Users->userData['default_write'];
            }
        }

        $contentType = AbstractEntity::CONTENT_HTML;
        if ($this->Users->userData['use_markdown']) {
            $contentType = AbstractEntity::CONTENT_MD;
        }

        // enforce the permissions if the admin has set them
        $canread = $teamConfigArr['do_force_canread'] === 1 ? $teamConfigArr['force_canread'] : $canread;
        $canwrite = $teamConfigArr['do_force_canwrite'] === 1 ? $teamConfigArr['force_canwrite'] : $canwrite;

        // SQL for create experiments
        $sql = 'INSERT INTO experiments(title, date, body, category, elabid, canread, canwrite, metadata, userid, content_type)
            VALUES(:title, CURDATE(), :body, :category, :elabid, :canread, :canwrite, :metadata, :userid, :content_type)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title, PDO::PARAM_STR);
        $req->bindParam(':body', $body, PDO::PARAM_STR);
        $req->bindValue(':category', $this->getStatus(), PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid(), PDO::PARAM_STR);
        $req->bindParam(':canread', $canread, PDO::PARAM_STR);
        $req->bindParam(':canwrite', $canwrite, PDO::PARAM_STR);
        $req->bindParam(':metadata', $metadata, PDO::PARAM_STR);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':content_type', $contentType, PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        // insert the tags, steps and links from the template
        if ($template > 0) {
            $Tags = new Tags($Templates);
            $Tags->copyTags($newId, true);
            $this->Steps->duplicate($template, $newId, true);
            $this->ItemsLinks->duplicate($template, $newId, true);
        }

        $this->insertTags($tags, $newId);

        return $newId;
    }

    /**
     * Set the experiment as timestamped with a path to the token
     *
     * @param string $responseTime the date of the timestamp
     */
    public function updateTimestamp(string $responseTime): void
    {
        $this->canOrExplode('write');

        $sql = 'UPDATE experiments SET
            timestamped = 1,
            timestampedby = :userid,
            timestampedwhen = :when
            WHERE id = :id;';
        $req = $this->Db->prepare($sql);
        // the date recorded in the db will match the creation time of the timestamp token
        $req->bindParam(':when', $responseTime);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
    }

    /**
     * Duplicate an experiment
     *
     * @return int the ID of the new item
     */
    public function duplicate(): int
    {
        $this->canOrExplode('read');

        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $this->entityData['title'] . ' I';

        $sql = 'INSERT INTO experiments(title, date, body, category, elabid, canread, canwrite, userid, metadata, content_type)
            VALUES(:title, CURDATE(), :body, :category, :elabid, :canread, :canwrite, :userid, :metadata, :content_type)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title, PDO::PARAM_STR);
        $req->bindParam(':body', $this->entityData['body'], PDO::PARAM_STR);
        $req->bindValue(':category', $this->getStatus(), PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid(), PDO::PARAM_STR);
        $req->bindParam(':canread', $this->entityData['canread'], PDO::PARAM_STR);
        $req->bindParam(':canwrite', $this->entityData['canwrite'], PDO::PARAM_STR);
        $req->bindParam(':metadata', $this->entityData['metadata'], PDO::PARAM_STR);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':content_type', $this->entityData['content_type'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();
        /** @psalm-suppress PossiblyNullArgument
         * this->id cannot be null here, checked during canOrExplode */
        $this->ExperimentsLinks->duplicate($this->id, $newId);
        $this->ItemsLinks->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);

        return $newId;
    }

    /**
     * Experiment is not actually deleted but the state is changed from normal to deleted
     */
    public function destroy(): bool
    {
        if ($this->entityData['timestamped'] === 1) {
            throw new IllegalActionException('User tried to delete an experiment that was timestamped.');
        }
        $Teams = new Teams($this->Users);
        $teamConfigArr = $Teams->readOne();
        $Config = Config::getConfig();
        if ((!$teamConfigArr['deletable_xp'] && !$this->Users->userData['is_admin'])
            || $Config->configArr['deletable_xp'] === 0) {
            throw new ImproperActionException('You cannot delete experiments!');
        }
        // delete from pinned too
        return parent::destroy() && $this->Pins->cleanup();
    }

    public function patch(Action $action, array $params): array
    {
        $this->canOrExplode('write');
        return match ($action) {
            Action::Bloxberg => $this->bloxberg(),
            Action::Timestamp => $this->timestamp(),
            default => parent::patch($action, $params),
        };
    }

    private function bloxberg(): array
    {
        $Config = Config::getConfig();
        $config = $Config->configArr;
        if ($config['blox_enabled'] !== '1') {
            throw new ImproperActionException('Bloxberg timestamping is disabled on this instance.');
        }
        (new MakeBloxberg(new Client(), $this))->timestamp();
        return $this->readOne();
    }

    private function getTimestampMaker(array $config): MakeTimestampInterface
    {
        return match ($config['ts_authority']) {
            'dfn' => new MakeDfnTimestamp($config, $this),
            'universign' => $config['debug'] ? new MakeUniversignTimestampDev($config, $this) : new MakeUniversignTimestamp($config, $this),
            'digicert' => new MakeDigicertTimestamp($config, $this),
            'sectigo' => new MakeSectigoTimestamp($config, $this),
            'globalsign' => new MakeGlobalSignTimestamp($config, $this),
            'custom' => new MakeCustomTimestamp($config, $this),
            default => throw new ImproperActionException('Incorrect timestamp authority configuration.'),
        };
    }

    private function timestamp(): array
    {
        $Config = Config::getConfig();
        $Maker = $this->getTimestampMaker($Config->configArr);
        $pdfBlob = $Maker->generatePdf();
        $TimestampUtils = new TimestampUtils(
            new Client(),
            $pdfBlob,
            $Maker->getTimestampParameters(),
            new TimestampResponse(),
        );
        $tsResponse = $TimestampUtils->timestamp();
        $Maker->saveTimestamp($TimestampUtils->getDataPath(), $tsResponse);
        return $this->readOne();
    }

    /**
     * Select what will be the status for the experiment
     *
     * @return int The status ID
     */
    private function getStatus(): int
    {
        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
            $this->Db->execute($req);
            $status = $req->fetchColumn();
        }
        return (int) $status;
    }
}
