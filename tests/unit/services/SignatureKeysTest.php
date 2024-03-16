<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\SignatureKeys;
use Elabftw\Enums\Meaning;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;

class SignatureKeysTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerate(): void
    {
        $passphrase = 'correct horse battery staple';
        $message = "Don't push me cause I'm close to the edge
            I'm trying not to lose my head
            It's like a jungle sometimes
            It makes me wonder how I keep from goin' under";
        $Key = SignatureKeys::generate($passphrase);
        $SignatureHelper = new SignatureHelper(new Users(1, 1));
        $this->assertTrue($SignatureHelper->create($Key));
        $privkey = $SignatureHelper->serializeSk($Key);
        $this->assertIsString($SignatureHelper->serializeSignature($privkey, $passphrase, $message, Meaning::Approval));
        $this->assertInstanceOf(SignatureKeys::class, $Key);
        $this->expectException(ImproperActionException::class);
        $SignatureHelper->serializeSignature('invalid key', $passphrase, $message, Meaning::Authorship);
    }

    public function testInvalidPassphrase(): void
    {
        $Key = SignatureKeys::generate('Tr0ub4dor&3');
        $privkey = SignatureHelper::serializeSk($Key);
        $SignatureHelper = new SignatureHelper(new Users(1, 1));
        $this->expectException(ImproperActionException::class);
        $this->assertIsString($SignatureHelper->serializeSignature($privkey, 'wrong passphrase', 'a', Meaning::Approval));
    }
}
