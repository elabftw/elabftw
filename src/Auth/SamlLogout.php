<?php

declare(strict_types=1);

namespace Elabftw\Auth;

use OneLogin\Saml2\Auth;

/** Process redirect-binding logout messages without changing their signed encoding. */
final class SamlLogout
{
    // An empty string means no LogoutResponse redirect is required.
    public static function process(Auth $auth, callable $destroySession): string
    {
        // Redirect signatures cover the original query bytes. Re-encoding decoded
        // parameters can change percent-escape casing and invalidate a valid signature.
        // Normalize php-saml's null fall-through for incoming LogoutResponse messages.
        return (string) $auth->processSLO(
            keepLocalSession: false,
            requestId: null,
            retrieveParametersFromServer: true,
            cbDeleteSession: $destroySession,
            stay: true,
        );
    }
}
