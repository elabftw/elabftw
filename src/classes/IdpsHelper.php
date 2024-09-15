<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Config;
use Elabftw\Models\Idps;

use function rtrim;

/**
 * Helper class for Idps, mostly to get settings
 */
class IdpsHelper
{
    public function __construct(public Config $Config, private Idps $Idps) {}

    /**
     * Get the settings array
     * On login we don't have an id but we don't need the settings
     * from a particular idp (just the service provider)
     * So getEnabled will just grab the first enabled one
     *
     * @param int|null $id id of the selected idp
     */
    public function getSettings(?int $id = null): array
    {
        $idp = $this->Idps->getEnabled($id);

        return $this->getSettingsByIdp($idp);
    }

    /**
     * Get the settings array by entity id.
     *
     * @param string $entId Entity id of the selected idp
     */
    public function getSettingsByEntityId(string $entId): array
    {
        $idp = $this->Idps->getEnabledByEntityId($entId);

        return $this->getSettingsByIdp($idp);
    }

    private function getSettingsByIdp(array $idp): array
    {
        $idpSigningCerts = array($idp['x509']);

        if (!empty($idp['x509_new'])) {
            $idpSigningCerts[] = $idp['x509_new'];
        }

        return array(
            // If 'strict' is True, then the PHP Toolkit will reject unsigned
            // or unencrypted messages if it expects them signed or encrypted
            // Also will reject the messages if not strictly follow the SAML
            // standard: Destination, NameId, Conditions ... are validated too.
            'strict' => $this->Config->configArr['saml_strict'],

            // Enable debug mode (to print errors)
            'debug' => $this->Config->configArr['saml_debug'],

            // Set a BaseURL to be used instead of try to guess
            // the BaseURL of the view that process the SAML Message.
            // Ex. http://sp.example.com/
            //     http://example.com/sp/
            'baseurl' => $this->Config->configArr['saml_baseurl'],

            // Save IdP id
            'idp_id' => $idp['id'],

            // Service Provider Data that we are deploying
            'sp' => array(
                // Identifier of the SP entity  (must be a URI)
                'entityId' => $this->Config->configArr['saml_entityid'],
                // Specifies info about where and how the <AuthnResponse> message MUST be
                // returned to the requester, in this case our SP.
                'assertionConsumerService' => array(
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => rtrim($this->Config->configArr['saml_baseurl'] ?? '', '/') . '/index.php?acs',
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => $this->Config->configArr['saml_acs_binding'],
                ),
                // If you need to specify requested attributes, set a
                // attributeConsumingService. nameFormat, attributeValue and
                // friendlyName can be omitted. Otherwise remove this section.
                // Important: requestedAttributes.name cannot be empty or some IDP software will choke on it when reading SP metadata
                'attributeConsumingService' => array(
                    'ServiceName' => 'eLabFTW',
                    'serviceDescription' => 'Electronic Lab Notebook',
                    'requestedAttributes' => array(
                        array(
                            'name' => empty($idp['email_attr']) ? 'urn:oid:0.9.2342.19200300.100.1.3' : $idp['email_attr'],
                            'isRequired' => true,
                            'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
                            'friendlyName' => 'mail',
                        ),
                        array(
                            'name' => empty($idp['fname_attr']) ? 'urn:oid:2.5.4.42' : $idp['fname_attr'],
                            'isRequired' => false,
                            'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
                            'friendlyName' => 'givenName',
                        ),
                        array(
                            'name' => empty($idp['lname_attr']) ? 'urn:oid:2.5.4.4' : $idp['lname_attr'],
                            'isRequired' => false,
                            'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
                            'friendlyName' => 'sn',
                        ),
                        array(
                            'name' => empty($idp['team_attr']) ? 'urn:oid:1.3.6.1.4.1.5923.1.1.1.7' : $idp['team_attr'],
                            'isRequired' => false,
                            'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
                            'friendlyName' => 'team',
                        ),
                        array(
                            'name' => empty($idp['orgid_attr']) ? 'urn:oid:0.9.2342.19200300.100.1.1' : $idp['orgid_attr'],
                            'isRequired' => false,
                            'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
                            'friendlyName' => 'uid',
                        ),
                    ),
                ),
                // Specifies info about where and how the <Logout Response> message MUST be
                // returned to the requester, in this case our SP.
                'singleLogoutService' => array(
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => $this->Config->configArr['saml_baseurl'] . '/app/logout.php?sls',
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => $this->Config->configArr['saml_slo_binding'],
                ),
                // Specifies constraints on the name identifier to be used to
                // represent the requested subject.
                // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported
                'NameIDFormat' => $this->Config->configArr['saml_nameidformat'],

                // Usually x509cert and privateKey of the SP are provided by files placed at
                // the certs folder. But we can also provide them with the following parameters
                'x509cert' => $this->Config->configArr['saml_x509'],
                'privateKey' => $this->Config->configArr['saml_privatekey'],

                // For certificate rollover purposes, we can also add a second x509 certificate
                // It is not used for signing or encryption, just included in metadata
                'x509certNew' => $this->Config->configArr['saml_x509_new'],
            ),

            // Identity Provider Data that we want connect with our SP
            'idp' => array(
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => $idp['entityid'],
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => array(
                    // URL Target of the IdP where the SP will send the Authentication Request Message
                    'url' => $idp['sso_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-POST binding only
                    'binding' => $idp['sso_binding'],
                ),
                // SLO endpoint info of the IdP.
                'singleLogoutService' => array(
                    // URL Location of the IdP where the SP will send the SLO Request
                    'url' => $idp['slo_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => $idp['slo_binding'],
                ),
                // Public x509 certificate of the IdP
                'x509certMulti' => array(
                    'signing' => $idpSigningCerts,
                    'encryption' => array(
                        $idp['x509'],
                    ),
                ),
                'emailAttr' => $idp['email_attr'],
                'teamAttr' => $idp['team_attr'],
                'fnameAttr' => $idp['fname_attr'],
                'lnameAttr' => $idp['lname_attr'],
                'orgidAttr' => $idp['orgid_attr'],
            ),
            // Security settings
            'security' => array(

                /** signatures and encryptions offered */

                // Indicates that the nameID of the <samlp:logoutRequest> sent by this SP
                // will be encrypted.
                'nameIdEncrypted' => (bool) $this->Config->configArr['saml_nameidencrypted'],

                // Indicates whether the <samlp:AuthnRequest> messages sent by this SP
                // will be signed.              [The Metadata of the SP will offer this info]
                'authnRequestsSigned' => (bool) $this->Config->configArr['saml_authnrequestssigned'],

                // Indicates whether the <samlp:logoutRequest> messages sent by this SP
                // will be signed.
                'logoutRequestSigned' => (bool) $this->Config->configArr['saml_logoutrequestsigned'],

                // Indicates whether the <samlp:logoutResponse> messages sent by this SP
                // will be signed.
                'logoutResponseSigned' => (bool) $this->Config->configArr['saml_logoutresponsesigned'],

                /* Sign the Metadata
                 False || True (use sp certs) || array (
                                                            keyFileName => 'metadata.key',
                                                            certFileName => 'metadata.crt'
                                                        )
                */
                'signMetadata' => (bool) $this->Config->configArr['saml_signmetadata'],

                /** signatures and encryptions required */
                // Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and
                // <samlp:LogoutResponse> elements received by this SP to be signed.
                'wantMessagesSigned' => (bool) $this->Config->configArr['saml_wantmessagessigned'],

                // Indicates a requirement for the <saml:Assertion> elements received by
                // this SP to be encrypted.
                'wantAssertionsEncrypted' => (bool) $this->Config->configArr['saml_wantassertionsencrypted'],

                // Indicates a requirement for the <saml:Assertion> elements received by
                // this SP to be signed.        [The Metadata of the SP will offer this info]
                'wantAssertionsSigned' => (bool) $this->Config->configArr['saml_wantassertionssigned'],

                // Indicates a requirement for the NameID element on the SAMLResponse received
                // by this SP to be present.
                'wantNameId' => (bool) $this->Config->configArr['saml_wantnameid'],

                // Indicates a requirement for the NameID received by
                // this SP to be encrypted.
                'wantNameIdEncrypted' => (bool) $this->Config->configArr['saml_wantnameidencrypted'],

                // Authentication context.
                // Set to false and no AuthContext will be sent in the AuthNRequest,
                // Set true or don't present this parameter and you will get an AuthContext 'exact' 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport'
                // Set an array with the possible auth context values: array ('urn:oasis:names:tc:SAML:2.0:ac:classes:Password', 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509'),
                'requestedAuthnContext' => false,

                // Allows the authn comparison parameter to be set, defaults to 'exact' if
                // the setting is not present.
                'requestedAuthnContextComparison' => 'exact',

                // Indicates if the SP will validate all received xmls.
                // (In order to validate the xml, 'strict' and 'wantXMLValidation' must be true).
                'wantXMLValidation' => (bool) $this->Config->configArr['saml_wantxmlvalidation'],

                // If true, SAMLResponses with an empty value at its Destination
                // attribute will not be rejected for this fact.
                'relaxDestinationValidation' => (bool) $this->Config->configArr['saml_relaxdestinationvalidation'],

                // If true, the toolkit will not raise an error when the Statement Element
                // contains attribute elements with name duplicated
                'allowRepeatAttributeName' => (bool) $this->Config->configArr['saml_allowrepeatattributename'],

                // Algorithm that the toolkit will use on signing process. Options:
                //    'http://www.w3.org/2000/09/xmldsig#rsa-sha1'
                //    'http://www.w3.org/2000/09/xmldsig#dsa-sha1'
                //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
                //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384'
                //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512'
                // Notice that sha1 is a deprecated algorithm and should not be used
                'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

                // Algorithm that the toolkit will use on digest process. Options:
                //    'http://www.w3.org/2000/09/xmldsig#sha1'
                //    'http://www.w3.org/2001/04/xmlenc#sha256'
                //    'http://www.w3.org/2001/04/xmldsig-more#sha384'
                //    'http://www.w3.org/2001/04/xmlenc#sha512'
                // Notice that sha1 is a deprecated algorithm and should not be used
                'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',

                // ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses
                // uppercase. Turn it True for ADFS compatibility on signature verification
                'lowercaseUrlencoding' => (bool) $this->Config->configArr['saml_lowercaseurlencoding'],
            ),
        );
    }
}
