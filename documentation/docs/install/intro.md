---
sidebar_position: 1
title: Introduction
---

eLabFTW is a web-based electronic lab notebook (ELN) that you can self-host for your team or organization.

This installation guide covers the supported deployment methods and the steps you need to get a working instance running.

:::note
This documentation is for On-Premise installations. For Software-as-a-Service (SaaS) offers, see [Deltablot's website](https://www.deltablot.com/elabftw/).
:::

## Supported platforms

eLabFTW is supported **only on GNU/Linux**, and **only in containers**.

Other operating systems or installations without containers are not supported for production installations.

## What you will install

A typical eLabFTW deployment includes:

- The **web application container** running nginx+php-fpm with eLabFTW
- A **MySQL database service**
- Persistent storage for application data and uploads

Continue to **Prerequisites** to prepare your system.
