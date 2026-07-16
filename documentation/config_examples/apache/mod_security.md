# Apache mod_security for eLabFTW

If you use [mod_security](https://github.com/SpiderLabs/ModSecurity) with your Apache server. It is necessary to have this configuration:

~~~apacheconf
<VirtualHost *:443>
        ServerName <ELABFTW_SUBDOMAIN>

        <TLS PARAMETERS...>

        # Disable outbound anomaly score
        SecRuleRemoveById 959100

        # Special config for /api endpoint
        <Location /api >
                SecRequestBodyAccess Off
                SecRuleRemoveById 949110
        </Location>
</VirtualHost>
~~~

List of rules is available here: https://www.netnea.com/cms/core-rule-set-inventory/

Please note that this is not a full-fledged configuration but rather a tweak that allows eLabFTW to work.
