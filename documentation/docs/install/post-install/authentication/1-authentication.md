---
sidebar_position: 1
title: Available methods
---

# Available methods

There are several ways to allow users to authenticate on an eLabFTW instance:

1. Local authentication: email address + locally stored password
2. SAML authentication: use a centralized authentication service with SAML2 protocol
3. LDAP authentication: use a centralized LDAP service to authenticate
4. External authentication: use request headers added by your own middleware to authenticate the user (_e.g._ Apache’s `auth_mellon`)
