# traefik

If you are already using [traefik](https://traefik.io/traefik/) to manage your containers, here is how to run eLabFTW:

* Get a docker-compose configuration file:

~~~bash
curl -sL -o docker-compose.yml "https://get.elabftw.net/?config"
~~~

Edit this file with your favorite editor and:

For the `web` service:

* Remove the `container_name`
* Set `DISABLE_HTTPS=true`
* Set `ENABLE_LETSENCRYPT=false`
* Remove the `ports` section
* Remove the `networks` section (or adapt it to your network)

For the `mysql` service:

* Remove everything if you already have a MySQL service running
* Remove the `container_name`
* Remove the `ports` section
* Remove the `networks` section (or adapt it to your network)

And remove the final `networks` section.

Add a label to the `web` service so traffic is routed to it. See traefik documentation.

Configure TLS accordingly. See traefik documentation.

Use docker-compose to bring the containers up and traefik should detect it and route requests accordingly.
