---
sidebar_position: 2
title: Prerequisites
---

Before installing eLabFTW, make sure your environment meets the requirements below. You should be comfortable maintaining a server: using the command line, applying updates, configuring backups, and hardening the host OS.

## Platform

- **64-bit GNU/Linux OS**

## Required dependencies

### Container runtime

- **Docker** (recommended for most setups)
- **Podman** (recommended on <abbr title='Red Hat Entreprise Linux'>RHEL</abbr> family hosts)
- **Kubernetes (k8s)** (recommended for large or managed deployments)
- Any other <abbr title='Open Container Initiative'>OCI</abbr> compatible container engine

This guide will focus on Docker + Compose plugin, as this is the easiest and most straightforward method to deploy eLabFTW.

:::warning
On Ubuntu, **Docker installed via snap is known to cause issues**; prefer a non-snap installation method.
:::

### Strongly recommended (especially if you use `elabctl`)

- `curl` (fetch files from the command line; probably already installed)
- **[Docker Compose plugin](https://docs.docker.com/compose/)** (required by `elabctl`; do not use the legacy `docker-compose` tool/package)
- `dialog` (used by `elabctl install`)
- `borgbackup` (required if you plan to use `elabctl backup`; not needed just to install)

## Database note (important)

- The default configuration already includes a **MySQL** container, so you generally **do not install** a host package like `mysql-server`.
- If you use an existing database service instead of the bundled container, it must be **MySQL (not MariaDB)**.

When you’re ready, move on to the **Installation** section.
