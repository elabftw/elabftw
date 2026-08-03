---
sidebar_position: 10
title: Addons
---

# What are addons?

Addons are services that can be deployed to provide extended functionality for eLabFTW. They are not a strict requirement but are definitely recommended.

# OpenCloning Addon

## Description

[OpenCloning](https://opencloning.org/) is an application used to plan and document cloning. DNA data can be loaded from various sources, and the application is tightly integrated with eLabFTW. This means that you can easily use Resource entries in eLabFTW and their attached files to perform cloning operations. See [usage documentation](../tutorials/opencloning).

## How to install

To enable OpenCloning in eLabFTW, deploy an OpenCloning container. See this [example docker-compose.yml](https://github.com/elabftw/elabftw/blob/master/containers/elabimg/docker-compose.yml-EXAMPLE).

Then enable it in the `elabftw` container configuration:

~~~yaml
# This is for the integration of the DNA Cloning tool
- USE_OPENCLONING=true
- OPENCLONING_URL=http://opencloning-plugin:8000/
~~~

Restart the `elabftw` container to take these changes into account (`elabctl refresh`).
