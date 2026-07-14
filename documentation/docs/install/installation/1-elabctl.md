---
sidebar_position: 1
---

# Install elabctl

`elabctl` is a tool to help you manage your eLabFTW installation. It is not strictly required to install it but it is quite handy so it is recommended (also it's just a bash script, nothing fancy).


~~~bash
# get the program and make it executable
curl -sL https://get.elabftw.net -o elabctl && chmod +x elabctl
# add it to a directory in your $PATH
sudo mv elabctl /usr/local/bin/
~~~

:::note
If you do not wish to install `elabctl`, you can get a Docker compose configuration file with:

~~~bash
curl -so docker-compose.yml "https://get.elabftw.net/?config"
~~~
:::
