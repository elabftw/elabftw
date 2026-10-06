---
sidebar_position: 2
title: Prerequisites
---

Before installing eLabFTW, make sure your environment meets the requirements below. You should be comfortable maintaining a server: using the command line, applying updates, configuring backups, and hardening the host OS.

## Platform

- **64-bit GNU/Linux OS**

## Hardware specifications

eLabFTW can run fine on modest hardware. It really depends if you're aiming to support 6 users or 6.000.

### Minimal

- 2 Gb of RAM
- 1 CPU with at least 2 cores
- Some disk space for uploaded files
- a MySQL container running alongside eLabFTW on the same VM

### Recommended for small instances

- 4 Gb of RAM
- 1 CPU with at least 4 cores
- Some disk space for uploaded files
- a MySQL container running alongside eLabFTW on the same VM

### Recommended for big instances

- Load balancer
- 2 workers nodes with each 8 Gb of RAM and 4+ CPU cores
- S3 or NFS backend for uploaded files
- Redis for user sessions
- MySQL cluster

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
- `borgbackup` (required if you plan to use `elabctl backup`; not needed just to install)

## Database note (important)

- The default configuration already includes a **MySQL** container, so you generally **do not install** a host package like `mysql-server`.
- If you use an existing database service instead of the bundled container, it must be **MySQL (not MariaDB)**.

When you’re ready, move on to the **Installation** section.
