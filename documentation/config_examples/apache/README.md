# Apache

If you already have an Apache webserver running, you can use it to forward requests to the Docker container (this is called a reverse proxy).

For that you will need to install/enable the `mod_proxy` Apache module, along with `mod_headers`:

~~~bash
sudo a2enmod proxy
sudo a2enmod headers
~~~

We recommend that you run the Docker container in HTTP mode and let Apache handle
TLS termination. However, letting the Docker container deal with TLS is also an option. Both configurations are presented below.

## Prerequisite: running the container on a custom port

Because our Apache server is already running on port 443, we will want the Docker container to run on another port.

In the `ports` section of this configuration file, expose the container on port 3148 as follows:

~~~yaml
ports:
  - '127.0.0.1:3148:443'
~~~

**Note**: We're using the 127.0.0.1 localhost ip to prevent Docker from exposing the port by bypassing the firewall configuration (see [this issue](https://github.com/moby/moby/issues/22054)).

## Reverse proxy for a container in http mode (recommended)

### Running the container in HTTP mode

By default, the eLabFTW (elabftw/elabimg) container runs in HTTPS mode, so you'll need to edit your `elabftw.yml` file (or `docker-compose.yml`) and add:

~~~yaml
DISABLE_HTTPS=true
~~~

### Configuring the reverse proxy

In your VirtualHost configuration block for eLabFTW, add the following lines:

~~~apacheconf
RequestHeader set X-Forwarded-Proto "https"
ProxyPreserveHost On
ProxyPass "/" "http://localhost:3148/"
ProxyPassReverse "/" "http://localhost:3148/"
~~~

## Reverse proxy for a container in https mode

You will need to make sure `mod_ssl` is activated.

Add these lines to your Apache configuration file (probably in `/etc/apache2/apache.conf` or in your VirtualHosts files).

~~~apacheconf
SSLEngine on
SSLProxyEngine on
ProxyPreserveHost On
ProxyPass "/" "https://localhost:3148/"
ProxyPassReverse "/" "https://localhost:3148/"
~~~
