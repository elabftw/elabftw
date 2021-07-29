# Security Policies and Procedures

This document outlines security procedures and general policies for **eLabFTW**.

## Reporting a security issue

All security bugs will be considered seriously.

Thank you for improving the security of **eLabFTW** with a responsible disclosure.

If you have found a security issue within this project, please contact me securely through my [Keybase profile](https://keybase.io/nicolascarpi).

## Steps taken in the development process

GitHub/Dependabot is regularly scanning the dependencies and will report if a version used has a known vulnerability.

The code itself is checked by various static analyzers to try and detect bugs sooner. And best practices are used to prevent SQL injection (with prepared statements), XSS (with a Content-Security-Policy header), and other nasty things.

If you scan the live demo for good practices and security headers, you'll find that eLabFTW scores very very high:

[![observatory score a+](https://i.imgur.com/mT9GH9I.png)](https://observatory.mozilla.org/analyze/demo.elabftw.net)

## Best practices

[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/2766/badge)](https://bestpractices.coreinfrastructure.org/projects/2766)

**eLabFTW** follows the best practices edicted by The Linux Foundation [Core Infrastructure Initiative](https://bestpractices.coreinfrastructure.org/en).

You can see the criteria [on this page](https://bestpractices.coreinfrastructure.org/en/projects/2766).

## Running an elabftw instance securely

Great you've installed elabftw. But you're a little bit paranoid because you're working on some top secret project and you don't want anyone to be able to look at it.

Here is a list of steps you should follow to increase the security of your instance.

### Run the Docker container

**eLabFTW**'s container has been fine tuned for maximizing safety. For instance, the default PHP configuration has been modified to make the session identifier longer, in a specific folder with tight permissions, a lot of functions not used in elabftw are forbidden, the cookies have the httpOnly, SameSite and secure flags, and other details that might not be easily modifiable if installed outside Docker.

Nginx also has a custom configuration and binary compilation options with secure headers sent to the client.

Running **eLabFTW** outside Docker is possible, of course, but discouraged.

### Using a BSD family OS

If your webserver is of the BSD family, Docker is not an option. Before installing eLabFTW on a BSD, consider the higher amount of maintenance that will be required on updates, as webserver configuration will change over time. It is recommended to use [nginx](https://nginx.org/) as a webserver so you can copy/paste the configuration from the official Docker image.

It is recommended to use a jail and make sure to have an appropriate webserver configuration (especially the security headers).

You will find configuration files for nginx in [this folder](https://github.com/elabftw/elabimg/tree/master/src/nginx).

### Stay updated

Subscribe to the [Newsletter](http://eepurl.com/bTjcMj) to receive a notification on a new release. Or use GitHub's notification system (you can elect to be notified only on new releases). And update your instance to the latest version quickly!

### Have backups

See [Backup Documentation](https://doc.elabftw.net/backup.html). And secure your backups on a filesystem with immutable snapshots! ;)
