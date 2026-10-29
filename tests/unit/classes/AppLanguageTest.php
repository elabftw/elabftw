<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Action;
use Elabftw\Enums\Language;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AppLanguageTest extends \PHPUnit\Framework\TestCase
{
    public function testAllLanguagesAreAvailableByDefault(): void
    {
        $App = $this->getApp();

        self::assertSame(Language::getAllHuman(), $App->getLanguages());
        self::assertSame('en_GB', $App->getLang());
        $App->setLang(Language::French);
        self::assertSame('fr_FR', $App->getLang());
    }

    public function testPublicLanguageListIsRestricted(): void
    {
        $App = $this->getApp('en_GB,de_DE');

        self::assertSame(array('en_GB' => 'English (UK/GB)', 'de_DE' => 'German'), $App->getLanguages());
        $App->setLang(Language::German);
        self::assertSame('de_DE', $App->getLang());
    }

    public function testDisallowedLanguageRequestsDoNotChangeTheSession(): void
    {
        $App = $this->getApp('en_GB,de_DE');
        $App->setLang(Language::German);
        $App->setLang(Language::French);

        self::assertSame('de_DE', $App->Session->get('lang'));
        self::assertSame('de_DE', $App->getLang());
    }

    public function testStaleSessionLanguageFallsBackToAllowedInstanceDefault(): void
    {
        $App = $this->getApp('en_GB,de_DE', 'de_DE');
        $App->Session->set('lang', 'fr_FR');

        self::assertSame('de_DE', $App->getLang());
    }

    public function testDisallowedInstanceDefaultFallsBackToFirstAllowedLanguage(): void
    {
        $App = $this->getApp('de_DE,en_GB', 'fr_FR');

        self::assertSame('en_GB', $App->getLang());
        $App->Session->set('lang', 'fr_FR');
        self::assertSame('en_GB', $App->getLang());
    }

    public function testPolicyChangesApplyToExistingSessions(): void
    {
        $App = $this->getApp();
        $App->setLang(Language::French);
        $App->Config->configArr['allowed_unauthenticated_languages'] = 'de_DE';

        self::assertSame(array('de_DE' => 'German'), $App->getLanguages());
        self::assertSame('de_DE', $App->getLang());
    }

    public function testAuthenticatedUsersKeepAllLanguagesAndTheirPreference(): void
    {
        $App = $this->getApp('en_GB,de_DE');
        $App->Users = new ReflectionClass(AuthenticatedUser::class)->newInstanceWithoutConstructor();
        $App->Users->userData['lang'] = 'fr_FR';
        $App->Session->set('lang', 'de_DE');

        self::assertSame(Language::getAllHuman(), $App->getLanguages());
        self::assertSame('fr_FR', $App->getLang());
    }

    public function testAnonymousVisitorsAreRestrictedEvenWithAnAnonymousSession(): void
    {
        $App = $this->getApp('de_DE');
        $App->Users = new ReflectionClass(AnonymousUser::class)->newInstanceWithoutConstructor();
        $App->Session->set('is_auth', 1);
        $App->Session->set('is_anon', 1);
        $App->Session->set('lang', 'fr_FR');

        self::assertSame(array('de_DE' => 'German'), $App->getLanguages());
        self::assertSame('de_DE', $App->getLang());
    }

    public function testUnknownLanguageIsRejectedBeforeSavingThePolicy(): void
    {
        $App = $this->getApp();
        $this->expectException(UnprocessableContentException::class);

        $App->Config->patch(Action::Update, array('allowed_unauthenticated_languages' => 'en_GB,invalid'));
    }

    public function testInvalidPolicyTypeIsRejectedBeforeSaving(): void
    {
        $App = $this->getApp();
        $this->expectException(UnprocessableContentException::class);

        $App->Config->patch(Action::Update, array('allowed_unauthenticated_languages' => array('en_GB')));
    }

    private function getApp(string $allowed = '', string $default = 'en_GB'): App
    {
        $Config = new ReflectionClass(Config::class)->newInstanceWithoutConstructor();
        $Config->configArr = array('lang' => $default, 'allowed_unauthenticated_languages' => $allowed);

        return new App(
            Request::create('/login.php'),
            new Session(new MockArraySessionStorage()),
            $Config,
            $this->createStub(LoggerInterface::class),
            new ReflectionClass(Users::class)->newInstanceWithoutConstructor(),
        );
    }
}
