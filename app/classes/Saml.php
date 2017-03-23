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
 * SAMLFTW
 */
class Saml
{
    private $settings = array();

    public function __construct()
    {
        $this->setSettings();
    }

    public function getSettings()
    {
        return $this->settings;
    }

    private function setSettings()
    {
        $this->settings = array (
            // If 'strict' is True, then the PHP Toolkit will reject unsigned
            // or unencrypted messages if it expects them signed or encrypted
            // Also will reject the messages if not strictly follow the SAML
            // standard: Destination, NameId, Conditions ... are validated too.
            'strict' => false,

            // Enable debug mode (to print errors)
            'debug' => true,

            // Set a BaseURL to be used instead of try to guess
            // the BaseURL of the view that process the SAML Message.
            // Ex. http://sp.example.com/
            //     http://example.com/sp/
            'baseurl' => 'https://elab.local',

            // Service Provider Data that we are deploying
            'sp' => array (
                // Identifier of the SP entity  (must be a URI)
                'entityId' => 'https://elab.local',
                // Specifies info about where and how the <AuthnResponse> message MUST be
                // returned to the requester, in this case our SP.
                'assertionConsumerService' => array (
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => 'https://elab.local/index.php?acs',
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
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
                    'url' => 'https://elab.local',
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // Specifies constraints on the name identifier to be used to
                // represent the requested subject.
                // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',

                // Usually x509cert and privateKey of the SP are provided by files placed at
                // the certs folder. But we can also provide them with the following parameters
                'x509cert' => '',
                'privateKey' => '',
            ),

            // Identity Provider Data that we want connect with our SP
            'idp' => array (
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => 'https://app.onelogin.com/saml/metadata/639081',
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => array (
                    // URL Target of the IdP where the SP will send the Authentication Request Message
                    'url' => 'https://app.onelogin.com/saml/metadata/639081',
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-POST binding only
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ),
                // SLO endpoint info of the IdP.
                'singleLogoutService' => array (
                    // URL Location of the IdP where the SP will send the SLO Request
                    'url' => 'https://app.onelogin.com/saml/metadata/639081',
                    // SAML protocol binding to be used when returning the <Response>
                    // message.  Onelogin Toolkit supports for this endpoint the
                    // HTTP-Redirect binding only
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // Public x509 certificate of the IdP
                'x509cert' => '-----BEGIN CERTIFICATE-----
                MIIELDCCAxSgAwIBAgIUaFt6pFX/TrAJo207cGFEJEdGaLgwDQYJKoZIhvcNAQEF
                BQAwXzELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYD
                VQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAy
                OTU4MB4XDTE3MDMxOTExMzExNloXDTIyMDMyMDExMzExNlowXzELMAkGA1UEBhMC
                VVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJ
                ZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MIIBIjANBgkqhkiG
                9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzNKk3lhtLUJKvyl+0HZF3xpsjYRFT0HR30xA
                DhRUGT/7lwVl3SnkgN6Us6NtOdKRFqFntz37s4qkmbzD0tGG6GirIIvgFx8HKhTw
                YgjsMsC/+NcS854zB/9pDlwNpZwhjGXZgE9YQUXuiZp1W/1kE+KZANr1KJKjtlsi
                WjNWah9VXLKCjQfKHdgYxSiSW9mv/Phz6ZjW0M3wdnJQRGg0iUzDxWhYp7sGUvjI
                hPtdb+VCYVm2MymYESXbkXH60kG26TPvvJrELPkAJ54RWsuPkWADBZxIozeS/1He
                hjg2vIcH7T/x41+qSN9IzlhWQTYtVCkpR2ShNbXL7AUXMM5bsQIDAQABo4HfMIHc
                MAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPERoVBCoadgrSI2Wdy7zPWIUuWyMIGc
                BgNVHSMEgZQwgZGAFPERoVBCoadgrSI2Wdy7zPWIUuWyoWOkYTBfMQswCQYDVQQG
                EwJVUzEXMBUGA1UECgwOSW5zdGl0dXQgQ3VyaWUxFTATBgNVBAsMDE9uZUxvZ2lu
                IElkUDEgMB4GA1UEAwwXT25lTG9naW4gQWNjb3VudCAxMDI5NTiCFGhbeqRV/06w
                CaNtO3BhRCRHRmi4MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEA
                Z7CjWWuRdwJFBsUyEewobXi/yYr/AnlmkjNDOJyDGs2DHNHVEmrm7z4LWmzLHWPf
                zAu4w55wovJg8jrjhTaFiBO5zcAa/3XQyI4atKKu4KDlZ6cM/2a14mURBhPT6I+Z
                ZUVeX6411AgWQmohsESXmamEZtd89aOWfwlTFfAw8lbe3tHRkZvD5Y8N5oawvdHS
                urapSo8fde/oWUkO8I3JyyTUzlFOA6ri8bbnWz3YnofB5TXoOtdXui1SLuVJu8AB
                BEbhgv/m1o36VdOoikJjlZOUjfX5xjEupRkX/YTp0yfNmxt71kjgVLs66b1+dRG1
                c2Zk0y2rp0x3y3KG6K61Ug==
                -----END CERTIFICATE-----',
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
}
