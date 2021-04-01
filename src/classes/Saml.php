<?php
/**
 * \Elabftw\Elabftw\Saml
 *
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
 * Saml settings
 */
class Saml
{
    public Config $Config;

    private Idps $Idps;

    private array $settings = array();

    public function __construct(Config $config, Idps $idps)
    {
        $this->Config = $config;
        $this->Idps = $idps;
    }

    /**
     * Get the settings array
     *
     * @param int|null $id id of the selected idp
     */
    public function getSettings(?int $id = null): array
    {
        $this->setSettings($id);
        return $this->settings;
    }

    /**
     * Set the settings array to $this->settings
     * On login we don't have an id but we don't need the settings
     * from a particular idp (just the service provider)
     *
     * @param int|null $id id of the idp we want
     */
    private function setSettings(?int $id = null): void
    {
        $idpsArr = $this->Idps->getActive($id);

        $this->settings = array(
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
                'attributeConsumingService' => array(
                        'ServiceName' => 'eLabFTW',
                        'serviceDescription' => 'Electronic Lab Notebook',
                        'requestedAttributes' => array(
                            array(
                                'name' => '',
                                'isRequired' => false,
                                'nameFormat' => '',
                                'friendlyName' => '',
                                'attributeValue' => '',
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
            ),

            // Identity Provider Data that we want connect with our SP
            'idp' => array(
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => $idpsArr['entityid'],
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => array(
                    // URL Target of the IdP where the SP will send the Authentication Request Message
                    'url' => $idpsArr['sso_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-POST binding only
                    'binding' => $idpsArr['sso_binding'],
                ),
                // SLO endpoint info of the IdP.
                'singleLogoutService' => array(
                    // URL Location of the IdP where the SP will send the SLO Request
                    'url' => $idpsArr['slo_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => $idpsArr['slo_binding'],
                ),
                // Public x509 certificate of the IdP
                'x509cert' => $idpsArr['x509'],
                /*
                 *  Instead of use the whole x509cert you can use a fingerprint
                 *  (openssl x509 -noout -fingerprint -in "idp.crt" to generate it,
                 *   or add for example the -sha256 , -sha384 or -sha512 parameter)
                 *
                 *  If a fingerprint is provided, then the certFingerprintAlgorithm is required in order to
                 *  let the toolkit know which Algorithm was used. Possible values: sha1, sha256, sha384 or sha512
                 *  'sha1' is the default value.
                 */
                // 'certFingerprint' => '',
                // 'certFingerprintAlgorithm' => 'sha1',
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
