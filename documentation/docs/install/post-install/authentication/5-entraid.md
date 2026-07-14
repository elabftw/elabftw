---
sidebar_position: 5
title: Microsoft Entra ID
---

# Entra ID configuration

Microsoft's Entra ID can be used with eLabFTW. We can configure SAML authentication with it.

Note: this documentation is very much a work-in-progress. Contributions would be welcome.

## Entra ID panel

### Create application

The first step is to **Register an application**.

Set the name to eLabFTW or whatever you prefer.

Select supported account types and for the Redirect URI, add your eLabFTW FQDN followed by: `/index.php?acs`.

This should give you an App ID (GUID).

### Setup SSO

From the SAML-based Sign-on panel register:

* Identifier (Entity ID): `https://<your-elabftw-fqdn>`
* Reply URL: `https://<your-elabftw-fqdn>/index.php?acs`


### Add users

Add users to the application so they can use it.

## eLabFTW configuration

### Configuring the IdP

Get the XML URL of the application from Entra ID, it should look like:

`https://login.microsoftonline.com/<uuid>/federationmetadata/2007-06/federationmetadata.xml?appid=<appid-uuid>`

Where `<uuid>` is the organization UUID visible in all URLs and `<appid-uuid>` is the application's id.

In eLabFTW, on the Sysconfig Panel, in SAML, add that URL and click Refresh to create the IdP.

Edit the IdP to configure the attributes like this:

* Email: http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name
* Firstname: http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname
* Lastname: http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname

(remove team name and internal id values)

### Configuring the SP (eLabFTW)

The EntityId value for the SP should be: `spn:<appid-uuid>`. Make sure it is set to that app id.
