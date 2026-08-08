<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Request;

use function setcookie;
use function time;

final class SamlRequestState
{
    private const string REQUEST_ID_COOKIE = 'saml_request_id';

    private const string IDP_ID_COOKIE = 'saml_request_idp';

    private const int LIFETIME_SECONDS = 300;

    public function __construct(
        private readonly Request $request,
    ) {}

    public function store(string $requestId, int $idpId): void
    {
        $expires = time() + self::LIFETIME_SECONDS;

        $this->setCookie(
            self::REQUEST_ID_COOKIE,
            $requestId,
            $expires,
        );

        $this->setCookie(
            self::IDP_ID_COOKIE,
            (string) $idpId,
            $expires,
        );
    }

    public function getRequestId(): string
    {
        $requestId = $this->request->cookies->getString(
            self::REQUEST_ID_COOKIE,
        );

        if ($requestId === '') {
            throw new UnauthorizedException();
        }

        return $requestId;
    }

    public function getIdpId(): int
    {
        $idpId = $this->request->cookies->getInt(
            self::IDP_ID_COOKIE,
        );

        if ($idpId < 1) {
            throw new UnauthorizedException();
        }

        return $idpId;
    }

    public function clear(): void
    {
        $expires = time() - 3600;

        $this->setCookie(
            self::REQUEST_ID_COOKIE,
            '',
            $expires,
        );

        $this->setCookie(
            self::IDP_ID_COOKIE,
            '',
            $expires,
        );
    }

    private function setCookie(
        string $name,
        string $value,
        int $expires,
    ): void {
        setcookie(
            $name,
            $value,
            array(
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None',
            ),
        );
    }
}
