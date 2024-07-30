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

use DOMDocument;
use Elabftw\Exceptions\ImproperActionException;

/**
 * Convert XML metadata about IDPs into eLabFTW's IDP
 */
class Xml2Idps
{
    public function __construct(private DOMDocument $dom, private string $ssoBinding, private string $sloBinding) {}

    public function getIdpsFromDom(): array
    {
        $res = array();
        $entities = $this->dom->getElementsByTagNameNS('*', 'EntityDescriptor');
        if (count($entities) === 0) {
            throw new ImproperActionException('Could not find any EntityDescriptor node from the provided XML data!');
        }

        foreach ($entities as $entity) {
            $idp = array();

            // NAME
            $names = $entity->getElementsByTagNameNS('*', 'DisplayName');
            foreach ($names as $node) {
                // TODO use server lang
                if ($node->getAttribute('xml:lang') === 'en') {
                    $idp['name'] = $node->nodeValue;
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
                if ($node->getAttribute('Binding') === $this->ssoBinding) {
                    $idp['sso_url'] = $node->getAttribute('Location');
                }
            }
            // no sso_url found means we skip it
            if (empty($idp['sso_url'])) {
                continue;
            }
            // SLO
            $sloServiceNodes = $entity->getElementsByTagNameNS('*', 'SingleLogoutService');
            foreach ($sloServiceNodes as $node) {
                if ($node->getAttribute('Binding') === $this->sloBinding) {
                    $idp['slo_url'] = $node->getAttribute('Location');
                }
            }

            // X509
            $idpSSODescriptors = $entity->getElementsByTagNameNS('*', 'IDPSSODescriptor');
            foreach ($idpSSODescriptors as $descriptor) {
                $x509Nodes = $descriptor->getElementsByTagNameNS('*', 'X509Certificate');
                foreach ($x509Nodes as $node) {
                    $idp['x509'] = $node->nodeValue;
                }
            }
            $res[] = $idp;
        }
        return $res;
    }
}
