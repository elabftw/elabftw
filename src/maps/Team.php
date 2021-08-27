<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Maps;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MapInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use PDO;
use const SECRET_KEY;

/**
 * One team
 */
class Team implements MapInterface
{
    private Db $Db;

    private string $commonTemplate = '';

    private string $name = '';

    private int $deletableXp = 1;

    private int $deletableItem = 1;

    private int $userCreateTag = 1;

    private int $forceExpTpl = 0;

    private int $publicDb = 0;

    private string $linkName = 'Documentation';

    private string $linkHref = 'https://doc.elabftw.net';

    private string $stamplogin = '';

    private string $stamppass = '';

    private string $stampprovider = '';

    private string $stampcert = '';

    private string $orgid = '';

    private int $doForceCanread;

    private int $doForceCanwrite;

    private string $forceCanread = '';

    private string $forceCanwrite = '';

    private int $visible;

    public function __construct(private int $id)
    {
        $this->Db = Db::getConnection();
        $this->hydrate($this->read());
    }

    final public function getId(): int
    {
        return $this->id;
    }

    final public function setName(?string $setting): void
    {
        if ($setting === null) {
            throw new ImproperActionException('Team name cannot be empty!');
        }
        $this->name = $setting;
    }

    final public function getName(): string
    {
        return $this->name;
    }

    final public function setCommonTemplate(?string $setting): void
    {
        if ($setting === null) {
            throw new ImproperActionException('Common template cannot be empty!');
        }
        $this->commonTemplate = $setting;
    }

    final public function getCommonTemplate(): string
    {
        return $this->commonTemplate;
    }

    final public function setDeletableXp(string $setting): void
    {
        $this->deletableXp = Filter::toBinary($setting);
    }

    final public function getDeletableXp(): int
    {
        return $this->deletableXp;
    }

    final public function setDeletableItem(string $setting): void
    {
        $this->deletableItem = Filter::toBinary($setting);
    }

    final public function getDeletableItem(): int
    {
        return $this->deletableItem;
    }

    final public function setUserCreateTag(string $setting): void
    {
        $this->userCreateTag = Filter::toBinary($setting);
    }

    final public function getUserCreateTag(): int
    {
        return $this->userCreateTag;
    }

    final public function setForceExpTpl(string $setting): void
    {
        $this->forceExpTpl = Filter::toBinary($setting);
    }

    final public function getForceExpTpl(): int
    {
        return $this->forceExpTpl;
    }

    final public function setPublicDb(string $setting): void
    {
        $this->publicDb = Filter::toBinary($setting);
    }

    final public function setLinkName(string $setting): void
    {
        $this->linkName = Filter::sanitize($setting);
    }

    final public function getLinkName(): string
    {
        return $this->linkName;
    }

    final public function setLinkHref(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new ImproperActionException('Link target is not a valid URL');
        }
        $this->linkHref = $url;
    }

    final public function getLinkHref(): string
    {
        return $this->linkHref;
    }

    final public function setDoForceCanread(string $setting): void
    {
        $this->doForceCanread = Filter::toBinary($setting);
    }

    final public function getDoForceCanread(): int
    {
        return $this->doForceCanread;
    }

    final public function getDoForceCanwrite(): int
    {
        return $this->doForceCanwrite;
    }

    final public function getForceCanread(): string
    {
        return $this->forceCanread;
    }

    final public function getForceCanwrite(): string
    {
        return $this->forceCanwrite;
    }

    final public function setDoForceCanwrite(string $setting): void
    {
        $this->doForceCanwrite = Filter::toBinary($setting);
    }

    final public function setForceCanread(string $setting): void
    {
        $this->forceCanread = Check::visibility($setting);
    }

    final public function setForceCanwrite(string $setting): void
    {
        $this->forceCanwrite = Check::visibility($setting);
    }

    final public function setStamplogin(?string $setting): void
    {
        if (!empty($setting)) {
            $this->stamplogin = Filter::sanitize($setting);
        }
    }

    final public function setStamppass(string $setting): void
    {
        $this->stamppass = Crypto::encrypt($setting, Key::loadFromAsciiSafeString(SECRET_KEY));
    }

    final public function setStampcert(?string $setting): void
    {
        if (!empty($setting)) {
            $this->stampcert = Filter::sanitize($setting);
        }
    }

    final public function setStampprovider(?string $url): void
    {
        if (!empty($url)) {
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new ImproperActionException('Timestamping provider is not a valid URL');
            }
            $this->stampprovider = $url;
        }
    }

    final public function setOrgid(?string $setting): void
    {
        if ($setting !== null) {
            $this->orgid = Filter::sanitize($setting);
        }
    }

    final public function setVisible(string $setting): void
    {
        $this->visible = Filter::toBinary($setting);
    }

    public function save(): bool
    {
        $sql = 'UPDATE teams SET
            name = :name,
            common_template = :common_template,
            orgid = :orgid,
            deletable_xp = :deletable_xp,
            deletable_item = :deletable_item,
            user_create_tag = :user_create_tag,
            force_exp_tpl = :force_exp_tpl,
            public_db = :public_db,
            link_name = :link_name,
            link_href = :link_href,
            do_force_canread = :do_force_canread,
            force_canread = :force_canread,
            do_force_canwrite = :do_force_canwrite,
            force_canwrite = :force_canwrite,
            stamplogin = :stamplogin,
            stamppass = :stamppass,
            stampprovider = :stampprovider,
            stampcert = :stampcert,
            visible = :visible
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $this->name);
        $req->bindParam(':common_template', $this->commonTemplate);
        $req->bindParam(':orgid', $this->orgid);
        $req->bindParam(':deletable_xp', $this->deletableXp, PDO::PARAM_INT);
        $req->bindParam(':deletable_item', $this->deletableItem, PDO::PARAM_INT);
        $req->bindParam(':user_create_tag', $this->userCreateTag, PDO::PARAM_INT);
        $req->bindParam(':force_exp_tpl', $this->forceExpTpl, PDO::PARAM_INT);
        $req->bindParam(':public_db', $this->publicDb, PDO::PARAM_INT);
        $req->bindParam(':link_name', $this->linkName);
        $req->bindParam(':link_href', $this->linkHref);
        $req->bindParam(':do_force_canread', $this->doForceCanread, PDO::PARAM_INT);
        $req->bindParam(':do_force_canwrite', $this->doForceCanwrite, PDO::PARAM_INT);
        $req->bindParam(':force_canread', $this->forceCanread);
        $req->bindParam(':force_canwrite', $this->forceCanwrite);
        $req->bindParam(':stamplogin', $this->stamplogin);
        $req->bindParam(':stamppass', $this->stamppass);
        $req->bindParam(':stampprovider', $this->stampprovider);
        $req->bindParam(':stampcert', $this->stampcert);
        $req->bindParam(':visible', $this->visible);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Fill this object's properties from the source
     * Source can be sql query or post data
     *
     * @param array<string, mixed> $source
     */
    public function hydrate(array $source): void
    {
        $this->setName($source['name'] ?? $this->name);
        $this->setCommonTemplate($source['common_template'] ?? $this->commonTemplate);
        $this->setOrgid($source['orgid'] ?? $this->orgid);
        $this->setDeletableXp($source['deletable_xp'] ?? (string) $this->deletableXp);
        $this->setDeletableItem($source['deletable_item'] ?? (string) $this->deletableItem);
        $this->setUserCreateTag($source['user_create_tag'] ?? (string) $this->userCreateTag);
        $this->setForceExpTpl($source['force_exp_tpl'] ?? (string) $this->forceExpTpl);
        $this->setLinkName($source['link_name'] ?? $this->linkName);
        $this->setLinkHref($source['link_href'] ?? $this->linkHref);
        $this->setStamplogin($source['stamplogin'] ?? $this->stamplogin);
        if (!empty($source['stamppass'])) {
            $this->setStamppass($source['stamppass']);
        }
        $this->stampprovider = $source['stampprovider'] ?? $this->stampprovider;
        $this->setStampcert($source['stampcert'] ?? $this->stampcert);
        $this->setPublicDb($source['public_db'] ?? (string) $this->publicDb);
        $this->setDoForceCanread($source['do_force_canread'] ?? (string) $this->doForceCanread);
        $this->setDoForceCanwrite($source['do_force_canwrite'] ?? (string) $this->doForceCanwrite);
        $this->setForceCanread($source['force_canread'] ?? $this->forceCanread);
        $this->setForceCanwrite($source['force_canwrite'] ?? $this->forceCanwrite);
        $this->setVisible($source['visible'] ?? (string) $this->visible);
    }

    /**
     * Read from the current team
     */
    private function read(): array
    {
        $sql = 'SELECT * FROM `teams` WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch();
        if ($res === false) {
            throw new ImproperActionException('Could not find a team with that id!');
        }

        return $res;
    }
}
