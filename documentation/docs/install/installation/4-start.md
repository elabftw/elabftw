---
sidebar_position: 4
---

# Launch services

You should now have a working configuration file (`/etc/elabftw.yml`) and can start the services with:

~~~bash
elabctl start
# same as: docker compose -f /etc/elabftw.yml up -d
~~~

## Install the database structure

After the first start, you must install the database with:

~~~bash
elabctl initialize
# same as: docker exec -it elabftw bin/init db:install
~~~
