<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users\Users;
use Elabftw\Services\AccessKeyHelper;
use PDO;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function random_bytes;

final class AccessKeyDownloadValidatorTest extends TestCase
{
    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    public function testValidateAcceptsActiveUploadAndRejectsItAfterEntityDeletion(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $experimentId = $Experiments->create();
        $accessKey = new AccessKeyHelper(
            EntityType::Experiments,
            $experimentId,
        )->toggleAccessKey();
        $longName = 'test/' . bin2hex(random_bytes(8)) . '.txt';

        $sql = 'INSERT INTO uploads
            (real_name, long_name, item_id, userid, type, state)
            VALUES (:real_name, :long_name, :item_id, 1, :type, :state)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':real_name', 'access-key.txt');
        $req->bindValue(':long_name', $longName);
        $req->bindValue(':item_id', $experimentId, PDO::PARAM_INT);
        $req->bindValue(':type', EntityType::Experiments->value);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        $validator = new AccessKeyDownloadValidator();

        self::assertSame(1, $validator->validate($accessKey, $longName));
        self::assertSame(
            1,
            $validator->validate($accessKey, $longName . '_th.jpg'),
        );

        $sql = 'UPDATE experiments SET state = :deleted WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':deleted', State::Deleted->value, PDO::PARAM_INT);
        $req->bindValue(':id', $experimentId, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->expectException(UnauthorizedException::class);

        $validator->validate($accessKey, $longName);
    }

    public function testValidateRejectsUnknownAccessKey(): void
    {
        $this->expectException(UnauthorizedException::class);

        new AccessKeyDownloadValidator()->validate(
            '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'test/does-not-exist.txt',
        );
    }
}
