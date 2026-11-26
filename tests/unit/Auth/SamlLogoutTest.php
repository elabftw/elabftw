<?php

declare(strict_types=1);

namespace Elabftw\Auth;

use Codeception\Attribute\DataProvider;
use OneLogin\Saml2\Auth;
use OpenSSLCertificateSigningRequest;
use PHPUnit\Framework\TestCase;

use function base64_encode;
use function gmdate;
use function gzdeflate;
use function openssl_csr_new;
use function openssl_csr_sign;
use function openssl_pkey_new;
use function openssl_sign;
use function openssl_x509_export;
use function parse_str;
use function preg_replace_callback;
use function str_replace;
use function strtolower;
use function urlencode;

use const OPENSSL_ALGO_SHA256;
use const OPENSSL_KEYTYPE_RSA;

final class SamlLogoutTest extends TestCase
{
    public static function redirectCases(): array
    {
        return array(
            'lowercase escapes' => array(true, false, true),
            'uppercase escapes' => array(false, false, true),
            'tampered relay state' => array(true, true, false),
        );
    }

    #[DataProvider('redirectCases')]
    public function testSignedLogoutResponse(bool $lowercase, bool $tamper, bool $expected): void
    {
        $originalGet = $_GET;
        $originalServer = $_SERVER;
        try {
            // Generate a disposable identity provider; no installation credentials are used.
            $key = openssl_pkey_new(array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA));
            self::assertNotFalse($key);
            $csr = openssl_csr_new(array('commonName' => 'idp.example.test'), $key, array('digest_alg' => 'sha256'));
            self::assertInstanceOf(OpenSSLCertificateSigningRequest::class, $csr);
            $certificate = openssl_csr_sign($csr, null, $key, 1, array('digest_alg' => 'sha256'));
            self::assertNotFalse($certificate);
            self::assertTrue(openssl_x509_export($certificate, $cert));

            $settings = array(
                'strict' => true,
                'debug' => false,
                'sp' => array(
                    'entityId' => 'https://sp.example.test',
                    'assertionConsumerService' => array('url' => 'https://sp.example.test/acs'),
                    'singleLogoutService' => array('url' => 'https://sp.example.test/app/logout.php'),
                ),
                'idp' => array(
                    'entityId' => 'https://idp.example.test',
                    'singleSignOnService' => array('url' => 'https://idp.example.test/sso'),
                    'singleLogoutService' => array('url' => 'https://idp.example.test/slo'),
                    'x509cert' => $cert,
                ),
                'security' => array('wantMessagesSigned' => true, 'wantXMLValidation' => true),
            );
            $xml = '<samlp:LogoutResponse xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"'
                . ' xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_response" Version="2.0"'
                . ' IssueInstant="' . gmdate('Y-m-d\TH:i:s\Z') . '" Destination="https://sp.example.test/app/logout.php">'
                . '<saml:Issuer>https://idp.example.test</saml:Issuer><samlp:Status>'
                . '<samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>'
                . '</samlp:Status></samlp:LogoutResponse>';
            $encode = static function (string $value) use ($lowercase): string {
                $encoded = urlencode($value);
                return $lowercase ? preg_replace_callback('/%[0-9A-F]{2}/', static fn(array $match): string => strtolower($match[0]), $encoded) : $encoded;
            };
            $compressed = gzdeflate($xml);
            self::assertNotFalse($compressed);
            $query = 'SAMLResponse=' . $encode(base64_encode($compressed))
                . '&RelayState=' . $encode('https://sp.example.test/home?a=b+c')
                . '&SigAlg=' . $encode('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
            self::assertTrue(openssl_sign($query, $signature, $key, OPENSSL_ALGO_SHA256));
            if ($tamper) {
                $query = str_replace('home', 'elsewhere', $query);
            }
            $query .= '&Signature=' . $encode(base64_encode($signature));
            parse_str($query, $_GET);
            $_SERVER = array(
                'HTTPS' => 'on',
                'HTTP_HOST' => 'sp.example.test',
                'SERVER_PORT' => '443',
                'SCRIPT_NAME' => '/app/logout.php',
                'PHP_SELF' => '/app/logout.php',
                'REQUEST_URI' => '/app/logout.php?sls&' . $query,
                'QUERY_STRING' => 'sls&' . $query,
            );
            $destroyed = false;
            $auth = new Auth($settings);
            $redirect = SamlLogout::process($auth, static function () use (&$destroyed): void {
                $destroyed = true;
            });
            self::assertSame('', $redirect);
            self::assertSame($expected, $auth->getErrors() === array(), $auth->getLastErrorReason() ?? '');
            self::assertSame($expected, $destroyed);
        } finally {
            $_GET = $originalGet;
            $_SERVER = $originalServer;
        }
    }
}
