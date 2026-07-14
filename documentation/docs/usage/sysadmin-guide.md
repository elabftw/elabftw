---
sidebar_position: 4
title: Sysadmin guide
---

# Sysadmin guide

## General overview

The Sysadmin:

* Is the first registered user
* Has access to the Sysconfig Panel with general settings impacting every team hosted on the server
* Can create/edit/delete teams
* Can send a mass email to all users
* Can set the default language
* Can configure timestamping services
* Can change security settings (number of login attempts, manual validation of new users, multi-factor authentication, password complexity, etc...)
* It is possible to have multiple 'Sysadmin' accounts

Generally, a Sysadmin account will be assigned to a technical person. The same person that has console access to the server.

## Setting up email (required)

If there is only one thing to do after an install, it's setting up email. Otherwise users will not be able to reset their password nor receive notifications!

In order to configure eLabFTW to send emails, you will need credentials to a SMTP service. If you are using a SMTP service without authentication, it works, too.

Go to the Sysadmin panel. You will see this warning:

<figure>
  <img src="/img/sysconfig-email-warning.png" alt="sysconfig-email-warning" />
  <figcaption>Email warning.</figcaption>
</figure>

Click the tab "Email" and add the requested information to connect to your SMTP server.

If you don't know what to do, I highly recommend using [SMTP2GO](https://get.smtp2go.com/xj1zy4rvle6v) (use this referral link), they provide a free plan that will probably be enough for your use case.

![smtp2go](/img/smtp2go.jpg)

Register an account using this link: [SMTP2GO](https://get.smtp2go.com/xj1zy4rvle6v). Once logged in, add an SMTP User: it will provide you with a login and password to connect to the SMTP server "mail.smtp2go.com". Input these credentials on the Sysadmin panel and test sending an email.

## Set up backup

See the dedicated [backup page](/docs/install/backups).


## Configure teams `(optional)`
If your instance hosts different research groups/teams, you can create them from the Sysadmin panel (`sysconfig.php`).

You might also want to edit the Default team name. In the screenshot below, there are four teams. The "Organization ID" is an attribute that you can set in case you wish to use the information sent by an external authentication service (such as SAML or LDAP) to assign a team to a new user. It basically allows you to match a team on eLabFTW with a team in your directory.

The "Visible" attribute allows you to hide a team from the "Register" page. Use it for the administrative team for instance. Only a completely empty team can be deleted.

<figure>
  <img src="/img/sysconfig-teams.png" alt="sysconfig-teams" />
  <figcaption>Teams.</figcaption>
</figure>

## Configure authentication `(optional)`

eLabFTW currently supports four authentication mechanisms:

* Local authentication: email + password stored locally (in the eLabFTW database), this is the default
* SAML authentication: use one or several Identity Provider (IDP) to authenticate users. See dedicated [SAML documentation page](/docs/install/post-install/authentication/saml).
* LDAP authentication: verify the login with an LDAP service. See dedicated [LDAP documentation page](/docs/install/post-install/authentication/ldap).
* External authentication: use request headers added by your own middleware to authenticate the user (_e.g._ Apache's auth_mellon)

It is possible to have several mechanisms at the same time but recommended to only leave one visible to users. So if you configure LDAP or SAML, disable the Local login so Users are not confused.

### Example configuration 1: only Admins can create accounts

If you want to prevent users from registering accounts from the Register page but still want to allow Admins to create local user accounts in their team, use these settings:

- enable "Admins can create local accounts" from the Server tab of Sysconfig panel (it is enabled by default)
- in the Local Auth tab, disable "Enable local account creation"

### Example configuration 2: accounts can only be created from SAML

If you want user accounts to only exist through a valid SAML response, use these settings:

- disable "Admins can create local accounts" from the Server tab of Sysconfig panel (it is enabled by default)
- in the Local Auth tab, disable "Enable local account creation" and "Show local login form"
- in the SAML tab, set "If the user doesn't exist yet, what to do?" to "Create the user on the fly"

### Example configuration 3: use LDAP mainly, but allow Admins to create local user accounts for external collaborators

Configure the LDAP settings from the LDAP tab. Then you have a choice: you can either mask local login form with "Show local login form" disabled from the Local Auth tab, or keep it enabled and users can select "Local" or "LDAP" from the login page (LDAP will be selected by default), which might be confusing to some users (as they don't know what Local or LDAP corresponds to). It's up to you. If you mask local login, external collaborators will need to append "?letmein" to the login page URL so they can select Local login.

Make sure that "Admins can create local accounts" from first tab is enabled, and that if you have a filter on email domains, the external collaborators emails can fit.

### Help, I'm locked out!

If you disabled Local authentication and cannot login back because your other method fails, you'll want to run this SQL query (`elabctl mysql` will give you a MySQL prompt if you're using Dockerized MySQL service):

~~~sql
update config set conf_value = '1' where conf_name = 'local_auth_enabled';
~~~


### Set up timestamping `(optional)`

eLabFTW provides an easy way to do `Trusted Timestamping <https://en.wikipedia.org/wiki/Trusted_timestamping>`_ for your experiments, so you can have strong legal value for your lab notebook.

By default, it is setup to use [pki.dfn.de](https://www.pki.dfn.de/zeitstempeldienst/) as <abbr title='Time Stamping Authority'>TSA</abbr>. It is free for researchers. The only problem, is that they don't have ETSI certification for this service (although their PKI infrastructure is certified ETSI TS 102 042).

So if you need a stronger certification, you should go with a commercial solution providing an :rfc:`3161` way of timestamping documents. We recommend [Universign.com](https://www.universign.com), as they are one of the most serious and recognized <abbr title='Time Stamping Authority'>TSA</abbr> out there, but feel free to use the one you prefer.

You can select from the list of pre-configured TSA or use a custom one by providing the URL and login/password.

Remember: no data is sent to the `TSA (TimeStampingAuthority)`, only the hash of the data is sent, so no information can leak!

#### Configure automatic timestamping

You might want to automate the timestamping of experiments, and not rely on users triggering the action manually. For that, you need to setup a recurrent task (cronjob or systemd timer) that will call a console command.

The command is: `bin/console experiments:timestamp --help`. It requires an argument which is the id of the user doing the timestamp. In most cases, using `1` will work fine. Alternatively you can create a specific user for this purpose.

By default, all experiments modified in the past week will be timestamped. So you'll want to adjust your recurrent task periodicity to align with the `--modified-since / -m` setting. Use english terms like "1 month" or "2 weeks" for this option.

Example:

~~~cron
# timestamp all the modified experiments from past week
# run on every Saturday at 3AM
0 3 * * 6 docker exec elabftw bin/console experiments:timestamp 1

# timestamp all the modified experiments of past month
# run the first day of the month at 3AM, in verbose mode
0 3 1 * *  docker exec elabftw bin/console experiments:timestamp 1 -m "1 month" -v
~~~


## Configure storage `(optional)`

By default, uploaded files are stored in the `uploads/` folder that is mounted outside the container for persistence. You can choose instead to use an S3 compatible service (buckets) to store these files. The "Uploads" tab allows you to configure this storage.

To use S3 storage, set `ELAB_AWS_ACCESS_KEY` and `ELAB_AWS_SECRET_KEY` in your config file. You can use `bin/console uploads:migrate` to migrate existing locally uploaded files to S3 storage.

Example setting with Scaleway S3:

* Bucket name: elabftw-example
* S3 region: fr-par
* S3 endpoint: https://s3.fr-par.scw.cloud
* S3 path prefix: uploads
* Verify TLS certificate: enabled

## Update often

It is important to keep your install up to date with the latest bug fixes and new features.

Subscribe to [the newsletter](http://eepurl.com/bTjcMj) to be warned when a new release is out or select "Releases only" from GitHub's Watch button on the [repo page](https://github.com/elabftw/elabftw).

See instructions on updating eLabFTW on [upgrade page](/docs/install/update).

## Sysadmin Panel

### Server tab

#### Settings
Note about "Allow the base permission setting «Only me»": if you wish to prevent users from using this Base Permission, select No here.

In order to go over existing entries and change all entries with the Base Permission "Only me" and set it to "Only me + Admins", you can execute these SQL requests:

~~~sql
-- experiments read permissions
update experiments set canread = json_set(canread, '$.base', 20) where
json_extract(canread, '$.base') = 10;

-- experiments write permissions
update experiments set canwrite = json_set(canwrite, '$.base', 20) where
json_extract(canwrite, '$.base') = 10;
~~~

#### Remote directory configuration

Ignore this if you are not a CNRS lab.

This allows to query an external endpoint to get a list of users that can be added to the eLabFTW local users database.

### Users tab

The Sysadmin can manage Users the same way Admins can manage Users from their Team, but with more options, such as changing the password or attributes, and manage the teams for users.

To make a User part of multiple teams, search for a User, and double click its row to open a modal window. From the section "Manage teams for user" you can add or remove teams, or archive a User in a given team. A User can be Admin in a team and not Admin in another.

<figure>
  <img src="/img/sysadmin-manage-teams.png" alt="sysadmin-manage-teams" />
  <figcaption>Manage teams for a user.</figcaption>
</figure>

The attribute "Is Owner" allows you to determine which of the Admins in a Team are the "owner" of the team. It has no impact anywhere and is purely an informational attribute.

Note that you can give an Admin the rights to also manage the association between users and teams.

#### Reinitializing 2FA

In case a user lost their phone, they might need to re-initialize two factors authentication. This action can only be done by a Sysadmin account. Double click the user row from the users table and select "Disable 2FA" at the bottom of the modal window.

<figure>
  <img src="/img/disable-2fa.png" alt="disable-2fa" />
  <figcaption>Disable 2FA button on user modal.</figcaption>
</figure>


## Monitoring

### Healthchecks

To monitor the nginx service, use `/healthcheck` endpoint, which replies with status code `204` if nginx is working.

To monitor the php-fpm service, use `/php-ping`, which replies with status code `200` if php-fpm is working.

To monitor the nginx, the php-fpm and the MySQL connection, use `/healthcheck.php`, which replies with status code `200` and `ok` in the body of the response if everything is working.

### Metrics

To gather metrics from the php-fpm service, use `/php-status`, which replies with information about php-fpm processes. See the [PHP FPM Status page documentation](https://www.php.net/manual/en/fpm.status.php) for more details.

To gather metrics from the nginx service, use `/nginx-status`, which replies with information about nginx processes. See the [Nginx status module documentation](https://nginx.org/en/docs/http/ngx_http_stub_status_module.html) for more details.

To gather metrics on the eLabFTW instance itself such as the number of experiments or uploaded files, an OpenMetrics 1.0 endpoint is exposed on `/metrics`. Point your collector such as Prometheus to this endpoint to gather metrics periodically.

The `/php-status`, `/nginx-status` and `/metrics` pages are protected with **Basic authentication**:

* user: `elabftw`
* password: the value of `STATUS_PASSWORD` environment variable

If this variable is not set (default), then a random unknown password will be generated but never displayed, effectively disabling access to these pages.

All of the above endpoints will not produce an access log entry.

## Logging

### Access and error logs

Logs produced by eLabFTW, nginx or PHP-FPM are redirected to `/dev/stdout` and `/dev/stderr` of the container. They appear with the command `docker logs elabftw`. By default, the logging driver is `local`, which means it will create files on the host.

It is recommended to emit logs to a centralized service, by using a different logging driver, such as `syslog` or `graylog` or `loki` (by grafana). For this, change the compose file configuration to include the correct settings for your infrastructure.

### Audit logs

It is possible to configure Audit Logs to be emitted in the general logs, in addition to being stored in the database. It might be important to you to keep a trace of these actions in a centralized, deported service. To enable this behavior, go to the Audit Logs tab of the Sysconfig Panel and enable "Emit audit logs with PHP error log". The message of the event is is JSON, and has this structure:


~~~javascript
{
category_value: number,
category_name: string,
message: string,
requester_userid: number,
target_userid: number
}
~~~

Example:

~~~javascript
{
    "category_value": 10,
    "category_name": "Login",
    "message": "User logged in",
    "requester_userid": 1,
    "target_userid": 1
}
~~~

You can then configure your centralized login system to interpret these messages to build advanced queries.

## Staging instance

A staging instance, sometimes called "pre-production", is another eLabFTW instance running alongside the production instance, and used to test upgrades or configuration changes without impacting the production instance.

It is highly recommended to setup a staging instance.

### How to create a staging instance

1. Copy the configuration file of the production instance (`/etc/elabftw.yml`) into another folder (`/path/to/elabftw-staging/docker-compose.yml`)
2. Change the `SITE_URL` value: for example from elab.uni.de to elab-test.uni.de
3. Similarly, change the `SERVER_NAME` value
4. Modify the `volumes` so it points to another folder than the production one, for both containers

Of course, adjust these instructions relative to your setup. Try and have the most similar setup between both. If one is using NFS storage with external MySQL server, do the same for staging instance.

Start your staging instance with: `docker compose up -d`. Make sure to adjust DNS, certificates, load balancers, reverse proxies accordingly.

Use the [Restore backup](/docs/install/backups#how-to-restore-a-backup) instructions to copy your production data into the staging instance. It is recommended to do that regularly, especially before updates, so the staging data is the same as production data and you will not have surprises. It's also a good opportunity to test your backups, if this has not been automated.

Before a major release, update the staging instance, optionally asking users if everything looks good on this instance, and once everything is validated, you can upgrade the production instance.

.. note:: It is recommended to post a general announcement from the Communications tab in the Sysconfig Panel to inform users that this is a test instance, preventing them from mistakenly entering data.

## Fix deprecation warning for old password storage

If your MySQL log is filled with "WARNING "sha256_password' is deprecated and will be removed in a future release." messages, you will want to update the password storage mechanism. This message is present because you have the line `command: --default-authentication-plugin=mysql_native_password` present in your compose file. It used to be necessary, but it is no longer the case and even deprecated.

:::warning
Make sure to have a working backup of your MySQL database first!
:::

First get a root mysql shell:

~~~bash
docker exec -it mysql bash
mysql -uroot -p$MYSQL_ROOT_PASSWORD
~~~

~~~sql
-- List users
mysql> select host, user, plugin from mysql.user;
-- Change password and use modern mechanism
mysql> alter user 'elabftw'@'%' identified with caching_sha2_password by '<elabftw password>';
mysql> alter user 'root'@'%' identified with caching_sha2_password by '<root password>';
mysql> alter user 'root'@'localhost' identified with caching_sha2_password by '<root password>';
~~~


Then delete the line in the docker compose file and restart the container.

## Migrate uploads from local storage to S3

In some contexts, it might be advantageous to use S3 backed storage for uploaded files. If you want to move your existing uploaded files (`uploads/` folder) to an S3 storage, here are the steps to follow:

1. Run `bin/console uploads:check` just to verify everything is nice and dandy
2. Configure the container to use S3 storage: add `ELAB_AWS_{ACCESS/SECRET}_KEY` env vars
3. In the Sysconfig panel, configure your bucket from the "UPLOADS" tab, test by uploading a file in an experiment
4. Once S3 is correctly configured, run `bin/console uploads:migrate`. This will copy all your locally stored files into the S3 bucket
5. The final step is to remove the bind mounted `/elabftw/uploads/` folder from the container runtime configuration

## Command line tools

The container comes with two CLI tools: `bin/console` and `bin/init`. They provide utilities for Sysadmins such as team export as .eln.

You can list available commands with `bin/console list` or `bin/init list`.

Example: `docker exec -it elabftw bin/console list`.

Check a command manual with `-h` flag. For example: `bin/console prune:experiments -h`.

## External connections

If you are running eLabFTW behind a proxy blocking all outgoing connections, you will need to allow these connections for the application to work properly:

- your timestamping service (for example, Universign will be https://ws.universign.eu)
- for blockchain timestamps: https://certify.bloxberg.org

You'll also want to make sure the eLabFTW container can connect to `chem-plugin` and `opencloning` services if they are not hosted on the same network or are firewalled out.

That's the only external endpoints that the service needs to access. Otherwise, it runs completely isolated from the external world.

For OpenCloning, the backend needs to access a few services, depending on the ones that you allow, see OpenCloning configuration: https://github.com/manulera/OpenCloning/blob/c6ab6e07477853369f042166f59803a81b7b7d96/docker-compose.yml#L38-L39.
