# Nginx

If you already have an Nginx webserver running, you can use it to forward requests to the Docker container (this is called a reverse proxy).

## Prerequisite: running the container on a custom port

Because our Nginx server is already running on port 443, we will want the Docker container to run on another port.

In the `ports` section of this configuration file, expose the container on port 3148 as follows:

~~~yaml
ports:
  - '127.0.0.1:3148:443'
~~~

**Note**: We're using the 127.0.0.1 localhost ip to prevent Docker from exposing the port by bypassing the firewall configuration (see [this issue](https://github.com/moby/moby/issues/22054)).

## Nginx configuration

In the following example, the URL https://elab.example.org is forwarded to local port 3148, where the Docker container is listening. In this example, Nginx is listening to port 8888, and HAProxy is handling TLS termination. Adapt this example to your needs. If HAProxy is not handling TLS termination in your case, use https in the proxy_pass instruction and make sure DISABLE_HTTPS is false in the elabftw.yml config.

~~~nginxconf
server {
    server_name elab.example.org;

    listen 8888;
    listen [::]:8888;

    access_log /var/log/nginx/elab.example.org.log proxy;

    location / {
        proxy_pass       http://localhost:3148; # use httpS here if needed
        proxy_set_header Host      $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        # add this if nginx is terminating TLS
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
~~~

Add this to /etc/nginx/nginx.conf to get the real IP address in the logs:

~~~nginxconf
log_format proxy '$proxy_add_x_forwarded_for - $remote_user [$time_local] '
    '"$request" $status $body_bytes_sent '
    '"$http_referer" "$http_user_agent" "$gzip_ratio"';
~~~
