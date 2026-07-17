---
sidebar_position: 6
title: Storage
---

# Storage

To store uploaded files, you have several options:

## Option A: bind-mount on the host

This is the most straightforward and simple option: Local storage. If you are using the provided `elabftw.yml`, you are already using that option. Uploaded files will be saved in the container to `/elabftw/uploads`, and this folder is mounted on the host (through the Volume section of the configuration file), to `/var/elabftw/web`. This means that by default, user uploaded files will appear in this directory in the host.

## Option B: bind-mount on an NFS mountpoint

Similar to Option A, except the host path is actually an NFS-mounted folder. Make sure to start the NFS daemon before the container service (use `After=` instruction in systemd unit file).

## Option C: using object storage (S3)

From the Uploads tab in the Sysconfig panel, select **S3 Bucket** and enter necessary information. Access and secret keys are provided as environment secrets to the container (`ELAB_AWS_ACCESS_KEY` and `ELAB_AWS_SECRET_KEY`).
