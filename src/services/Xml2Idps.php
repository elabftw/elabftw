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
use Elabftw\Models\Idps;

/**
 * Convert XML metadata about IDPs into eLabFTW's IDP
 */
class Xml2Idps
{
    public function __construct(private DOMDocument $dom) {}

    public function getIdpsFromDom(): array
    {
        $ssoBindings = array(Idps::SSO_BINDING_POST, Idps::SSO_BINDING_REDIRECT);
        $sloBindings = array(Idps::SLO_BINDING_POST, Idps::SSO_BINDING_REDIRECT);

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
                if (in_array($node->getAttribute('Binding'), $ssoBindings, true)) {
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
                if (in_array($node->getAttribute('Binding'), $sloBindings, true)) {
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
