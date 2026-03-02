<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use DOMDocument;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\CertPurpose;
use Elabftw\Enums\SamlBinding;
use Elabftw\Exceptions\ImproperActionException;

use function openssl_x509_parse;

/**
 * Convert XML metadata about IDPs into eLabFTW's IDP
 */
final class Xml2Idps
{
    public function __construct(private DOMDocument $dom) {}

    public static function processCert(string $x509Pem): array
    {
        // first pass to remove any BEGIN/END strings
        $x509Pem = Filter::pem($x509Pem);
        // then we add them because it's required so openssl_x509_read will detect what it is
        $x509PemWithHeaders = "-----BEGIN CERTIFICATE-----\n" . $x509Pem . "\n-----END CERTIFICATE-----\n";
        $OpenSSLCertificate = openssl_x509_read($x509PemWithHeaders);

        if ($OpenSSLCertificate === false) {
            throw new ImproperActionException(sprintf('Invalid x509 cert value: %s', openssl_error_string()));
        }
        $pem = '';
        openssl_x509_export($OpenSSLCertificate, $pem);
        if (empty($pem)) {
            throw new ImproperActionException('Error exporting x509 cert!');
        }
        $data = openssl_x509_parse($OpenSSLCertificate);
        if ($data === false) {
            throw new ImproperActionException('Invalid x509 cert value!');
        }
        return array(
            $pem,
            openssl_x509_fingerprint($OpenSSLCertificate, 'sha256'),
            new DateTimeImmutable('@' . $data['validFrom_time_t']),
            new DateTimeImmutable('@' . $data['validTo_time_t']),
        );
    }

    public function getIdpsFromDom(): array
    {
        $res = array();
        $entities = $this->dom->getElementsByTagNameNS('*', 'EntityDescriptor');
        if (count($entities) === 0) {
            throw new ImproperActionException('Could not find any EntityDescriptor node from the provided XML data!');
        }

        foreach ($entities as $entity) {
            $idp = array('certs' => array(), 'endpoints' => array());

            // NAME
            $names = $entity->getElementsByTagNameNS('*', 'DisplayName');
            foreach ($names as $node) {
                // TODO use server lang
                if ($node->getAttribute('xml:lang') === 'en') {
                    $idp['name'] = $node->textContent;
                }
            }

            // ENTITYID
            if ($entity->hasAttribute('entityID')) {
                $idp['entityid'] = $entity->getAttribute('entityID');
            }
            // if we cannot find an entityId, skip this one
            if (empty($idp['entityid'])) {
                continue;
            }

            // use entityid as name if the name could not be found
            if (empty($idp['name'])) {
                $idp['name'] = $idp['entityid'];
            }

            // SSO
            $ssoServiceNodes = $entity->getElementsByTagNameNS('*', 'SingleSignOnService');
            foreach ($ssoServiceNodes as $node) {
                $binding = SamlBinding::fromUrn($node->getAttribute('Binding'));
                if ($binding === null) {
                    continue;
                }
                $endpoint = array(
                    'binding' => $binding,
                    'location' => $node->getAttribute('Location'),
                    'is_slo' => BinaryValue::False,
                );
                $idp['endpoints'][] = $endpoint;
            }
            // SLO
            $sloServiceNodes = $entity->getElementsByTagNameNS('*', 'SingleLogoutService');
            foreach ($sloServiceNodes as $node) {
                $binding = SamlBinding::fromUrn($node->getAttribute('Binding'));
                if ($binding === null) {
                    continue;
                }
                $endpoint = array(
                    'binding' => $binding,
                    'location' => $node->getAttribute('Location'),
                    'is_slo' => BinaryValue::True,
                );
                $idp['endpoints'][] = $endpoint;
            }

            // X509
            $idpSSODescriptors = $entity->getElementsByTagNameNS('*', 'IDPSSODescriptor');
            foreach ($idpSSODescriptors as $descriptor) {
                $keyDescriptors = $descriptor->getElementsByTagNameNS('*', 'KeyDescriptor');
                foreach ($keyDescriptors as $keyDescriptor) {
                    $use = $keyDescriptor->getAttribute('use'); // "signing", "encryption", or empty
                    $purpose = CertPurpose::Signing;
                    if ($use === 'encryption') {
                        $purpose = CertPurpose::Encryption;
                    }
                    $x509Nodes = $keyDescriptor->getElementsByTagNameNS('*', 'X509Certificate');
                    foreach ($x509Nodes as $node) {
                        $cert = $node->textContent;
                        [$pem, $sha256, $notBefore, $notAfter] = self::processCert($cert);
                        $idp['certs'][] = array(
                            'purpose' => $purpose,
                            'x509'    => $pem,
                            'sha256' => $sha256,
                            'not_before' => $notBefore,
                            'not_after' => $notAfter,
                        );
                    }
                }
            }
            $res[] = $idp;
        }
        return $res;
    }
}
