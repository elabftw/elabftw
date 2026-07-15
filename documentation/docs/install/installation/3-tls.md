---
sidebar_position: 3
---

# Note about TLS certificates

The eLabFTW container can run an HTTP or HTTPS server. Both will run internally on port 443. This page describes several options you can choose regarding TLS configuration.

## Option A: HTTP mode with a reverse proxy (Apache, nginx, HAProxy, traefik, ...)

You can run the container in HTTP mode (internal port 443) only if you have a reverse proxy in front doing TLS termination and sending the `X-Forwarded-Proto` header.

- Set `DISABLE_HTTPS=true`

Reverse proxy configuration examples can be found here: https://github.com/elabftw/documentation/tree/master/config_examples/.

## Option B: HTTPS mode with Let's Encrypt certificates

In order to request Let's Encrypt certificates, you need to install `certbot` and have your server publicly accessible. See the official Let's Encrypt documentation for your system: https://letsencrypt.org/getting-started/.

When requesting a new certificate, make sure that port 80 is open (and also port 443 for eLabFTW if it is the one you want to use). Once certbot is installed, the command to use might look like this:

`certbot certonly --standalone -d elab.example.org`

- Set `DISABLE_HTTPS=false`
- Set `ENABLE_LETSENCRYPT=true`
- Set `SERVER_NAME` with a correct value
- Uncomment the line `- /etc/letsencrypt:/ssl` in the `volumes:` part of the yml config file.

## Option C: HTTPS mode with custom certificates

Have the private key and certificate in PEM format in the folder `/etc/letsencrypt/live/SERVER_NAME/` where `SERVER_NAME` matches the `SERVER_NAME` configuration variable. The files need to be named `fullchain.pem` and `privkey.pem`.

The webserver in the container expects TLS certificates to be in a particular order and format. Make sure that your `fullchain.pem` file contains certificates in this order: `<certificate> <intermediate ca> <root ca>`, with PEM encoding.

- Set `DISABLE_HTTPS=false`
- Set `ENABLE_LETSENCRYPT=true`
- Set `SERVER_NAME` with a correct value
- Uncomment the line `- /etc/letsencrypt:/ssl` in the `volumes:` part of the yml config file

## Option D: HTTPS mode with self-signed certificate

:::warning
Only use this for testing purposes!
:::

The container can generate its own self-signed certificate. This certificate will not be trusted and users will see a warning that they'll need to ignore. Do not use this option.

- Set `DISABLE_HTTPS=false`.
- Set `ENABLE_LETSENCRYPT=false`.

## Configure TLS certificate renewal

If you used Option A, B or C (meaning you have previously configured a TLS certificate) and are unfamiliar with the TLS certificate renewal procedure, follow the instructions below.

**You need to configure a recurring task to renew the certificate**, so that it does not expire and lock users out.

This section will only describe how to renew certificates generated via Let's Encrypt, as this will be the most likely case. Let's Encrypt certificates are valid only for a short period, and renewal must be automated ([learn more here](https://letsencrypt.org/2015/11/09/why-90-days)).

You will find the documentation for renewal with `certbot` here: [certbot renew certificates](https://eff-certbot.readthedocs.io/en/stable/using.html#renewing-certificates).

Example if you use the container in HTTPS mode (with certificates mounted in the container, Options B or C).

~~~bash title="/usr/local/bin/elabftw-renew-cert.sh"
certbot renew --post-hook "docker exec elabftw reload nginx"
~~~

In the example above, we reload the nginx service in the container so the new certificate is picked up by the webserver.

If you used Option A, you might need to reload your reverse proxy, and there is no need to reload eLabFTW's webserver. Example with Apache:

~~~bash title="/usr/local/bin/elabftw-renew-cert.sh"
certbot renew --post-hook "systemctl restart apache2"
~~~

You then need to execute this daily with a cronjob or a systemd timer.

Example cronjob to execute at 03:12 every day:

~~~cron
12 3 * * * /usr/local/bin/elabftw-renew-cert.sh
~~~

If you're encountering issues, or if something is not clear, do not hesitate to join the eLabFTW chat room to find help: [gitter chat (matrix room)](https://gitter.im/elabftw/elabftw).
