<?php
/**
 * \Elabftw\Elabftw\Saml
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Saml settings
 */
class Saml
{
    /** @var Config $Config instance of Config */
    public $Config;

    /** @var Idps $Idps instance of Idps */
    private $Idps;

    /** @var array $settings the saml settings array */
    private $settings = array();

    /**
     * Constructor
     *
     * @param Config $config
     * @param Idps $idps
     */
    public function __construct(Config $config, Idps $idps)
    {
        $this->Config = $config;
        $this->Idps = $idps;
    }

    /**
     * Set the settings array to $this->settings
     * If the $id is null, the idp part of the settings will be empty
     * but it's ok because we don't always need it
     *
     * @param int|null $id Id of the IDP
     */
    private function setSettings($id)
    {
        $idpsArr = array();
        if ($id !== null) {
            $idpsArr = $this->Idps->read($id);
        }

        $this->settings = array (
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
            'sp' => array (
                // Identifier of the SP entity  (must be a URI)
                'entityId' => $this->Config->configArr['saml_entityid'],
                // Specifies info about where and how the <AuthnResponse> message MUST be
                // returned to the requester, in this case our SP.
                'assertionConsumerService' => array (
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => $this->Config->configArr['saml_acs_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => $this->Config->configArr['saml_acs_binding'],
                ),
                // If you need to specify requested attributes, set a
                // attributeConsumingService. nameFormat, attributeValue and
                // friendlyName can be omitted. Otherwise remove this section.
                "attributeConsumingService"=> array(
                        "ServiceName" => "eLabFTW",
                        "serviceDescription" => "Electronic Lab Notebook",
                        "requestedAttributes" => array(
                            array(
                                "name" => "",
                                "isRequired" => false,
                                "nameFormat" => "",
                                "friendlyName" => "",
                                "attributeValue" => ""
                            )
                        )
                ),
                // Specifies info about where and how the <Logout Response> message MUST be
                // returned to the requester, in this case our SP.
                'singleLogoutService' => array (
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => $this->Config->configArr['saml_slo_url'],
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
            'idp' => array (
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => $idpsArr['entityid'],
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => array (
                    // URL Target of the IdP where the SP will send the Authentication Request Message
                    'url' => $idpsArr['sso_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-POST binding only
                    'binding' => $idpsArr['sso_binding'],
                ),
                // SLO endpoint info of the IdP.
                'singleLogoutService' => array (
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
        );
    }

    /**
     * Get the settings array
     *
     * @param int|null $id Return the settings array with infos from Idp with id $id
     * @return array
     */
    public function getSettings($id = null)
    {
        $this->setSettings($id);
        return $this->settings;
    }
}
