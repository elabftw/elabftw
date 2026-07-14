---
sidebar_position: 4
title: LDAP authentication
---

# LDAP authentication

This page describes the configuration of LDAP authentication for an eLabFTW instance.

It is possible to configure your LDAP instance to authenticate users through an LDAP service. Various settings are possible, to adapt to the different cases of LDAP servers.

## How does eLabFTW query LDAP servers?

The overall schematic for each query with the options set in the sysadmin menu looks like this:

1. Connect to the LDAP server (-> TLS?, host, port)
2. Bind (login) with username and password, or anonymously (-> username, password)
3. Search for users within a certain region of the LDAP world directory (-> base DN) and extract certain properties/fields (-> filter attribute, team name, email, firstname, lastname)
4. Unbind (log out)

For this to work, eLabFTW needs information from you, which is what the LDAP configuration options documented in the next section are for.

## Sysadmin LDAP settings

The LDAP settings are found on the LDAP tab of the Sysconfig Panel.

### Toggle LDAP login

This is your general ON/OFF switch to toggle LDAP authentication from the login page. Note that if local login is left available, a radio button will allow users to select local/ldap login, and ldap will be selected by default. Generally, you'll want to disable local login once LDAP login is working (see setting for local login in first tab of sysconfig panel).

### LDAP Scheme

This setting allows you to override the scheme (`ldap` or `ldaps`) used when connecting. This is because different LDAP implementations will require a different protocol depending on the use of TLS/StartTLS and the port. This setting is present to make sure we can cover all cases.

### LDAP Host

Enter the domain name or IP address of the LDAP server.
This should **not** include the protocol or the port, *i.e.* not `ldaps://my.ldap.server:636`, but only `my.ldap.server`.
For selecting the protocol and port, use the dedicated "LDAP Port" and "Use TLS" options.

### LDAP Port

The port the LDAP server listens on, `389` by default.
Use port `636` for LDAPS or any custom port you might have configured.

### LDAP Base DN

This is the "Distinguished Name" for the search, *i.e.* which part of the (global) LDAP tree you want to find the users in.
It is probably something like `dc=example,dc=org`, but might also include "Organizational Units", so something like `ou=myinstitute,dc=example,dc=org`.
Importantly, this is something completely different from the LDAP username, even though the notation might be similar.

### LDAP Username

This is the DN or username that connects (binds) to the LDAP server.
Examples include `myuser` or `cn=AnAdminOf,ou=MyUnit,dc=example,dc=org`.
The user must have permission to query other users.

### LDAP Password

This password is needed for the authentication on the LDAP server with the username specified above.

### Use TLS

Select this if using LDAPS (= LDAP with TLS).

### By which LDAP attribute the user will be found

This is the "filter attribute" from above.
Common choices include `cn`, `uid` and the default `mail`.
This is the LDAP field that holds the information you want users to enter into the "login" field on the start page.

### What attribute to look for the team name

The LDAP server will reply with the information associated with the user trying to authenticate.
Which field will be the one used to determine the team in which to create the user?

### Create team sent by server if it doesn't exist already

If the team found doesn't exist already, do you want to create one?

### If no team attribute is found, to which team user is assigned?

Use this to add users to this team by default.
Useful if the LDAP server doesn't answer with a team attribute that can be used for eLabFTW.

### What attribute to look for ...

The last three fields are to specify the fields to look for for the user email, firstname and lastname.

.. note::

    If you encounter difficulties, make sure to get a useful log message before opening an issue, :doc:`see debug documentation <debug>`.


## Using a custom cert file

When authenticating with a LDAPS server, a custom certfile might be needed.
It can easily be added by modifying the web/volumes part of `elabftw.yml` like so:

~~~yaml
services:
  web:
    ...
    volumes:
      ...
      - /path/to/eLabFTW/certpath:/custom_certs
      - /path/to/eLabFTW/openldap:/etc/openldap
  ...
...
~~~

Then, add your custom cert file to `/path/to/eLabFTW/certpath` and at `/path/to/eLabFTW/openldap/` add a new file `ldap.conf` with the content

~~~bash
TLS_CACERT /custom_certs/<certname>.pem
TLS_REQCERT hard
~~~

where you substitute `<certname>` for the name of the cert file for authenticating against the LDAP server.
This informs `openldap` of the cert file and instructs it to always require a valid certificate from servers.

After (re)starting using `elabctl restart`, the LDAP server should now be reachable from inside the container.
You can check this via searching for a known user (like yourself?) via

~~~bash
docker exec elabftw bash -c "apk add openldap openldap-back-mdb openldap-clients && \
  ldapsearch -v -LLL \
    -H 'ldaps://<LDAP Host>' \
    -b '<LDAP Base DN>' \
    -D '<LDAP Username>' \
    -w '<LDAP Password>' \
    '<filter>'"
~~~

where you might need to use `sudo docker` if you are not `root`.
Be sure to substitute the `<...>` fields with your values.
The command above installs the needed `openldap` packages in the `elabftw` container using Alpine Linux's package manager `apk` and then launches a ldap search query.
`<filter>` can for example be `cn=MyOwnName`, or `uid=5`.
If trying to connect to a LDAP server that listens on a port other than 636, specify it like `-H 'ldaps://<host>:<port>'`.

For more information on the `ldapsearch` command, consider

~~~bash
docker exec elabftw ldapsearch --help
~~~

after installing the `openldap` packages.
