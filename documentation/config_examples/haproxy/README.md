# HAProxy configuration for eLabFTW

This is an example config for a case in which HAProxy is in front of one or several eLabFTW containers (image: elabftw/elabimg).

## Example docker-compose config

Use the official HAProxy image with a bind mount on the config folder:

~~~yaml
services:
  haproxy:
    image: haproxy:2.2-alpine
    container_name: haproxy
    restart: always
    read_only: true
    depends_on:
      - elabftw
    ports:
      - '80:80'
      - '443:443'
    volumes:
      - /path/to/haproxy:/usr/local/etc/haproxy:ro
~~~
