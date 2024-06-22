<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\MinisignKeys;
use Elabftw\Enums\Action;
use Elabftw\Enums\Meaning;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\SigKeys;
use Elabftw\Models\Users;

class SignatureHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testSerializeSignature(): void
    {
        $passphrase = 'correct horse battery staple';
        $message = "Don't push me cause I'm close to the edge
            I'm trying not to lose my head
            It's like a jungle sometimes
            It makes me wonder how I keep from goin' under";
        $user = new Users(1, 1);
        $SigKeys = new SigKeys($user);
        $keyId = $SigKeys->postAction(Action::Create, array('passphrase' => $passphrase));
        $this->assertIsInt($keyId);
        $SigKeys->setId($keyId);
        $key = $SigKeys->readOne();
        $SignatureHelper = new SignatureHelper($user);
        $this->assertIsString($SignatureHelper->serializeSignature($key['privkey'], $passphrase, $message, Meaning::Approval));
        $this->expectException(ImproperActionException::class);
        $SignatureHelper->serializeSignature('invalid key', $passphrase, $message, Meaning::Authorship);
    }

    public function testInvalidPassphrase(): void
    {
        $Key = MinisignKeys::generate('Tr0ub4dor&3');
        $privkey = $Key->serializeSk();
        $SignatureHelper = new SignatureHelper(new Users(1, 1));
        $this->expectException(ImproperActionException::class);
        $SignatureHelper->serializeSignature($privkey, 'wrong passphrase', 'a', Meaning::Approval);
    }
}
