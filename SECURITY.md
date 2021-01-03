# Security Policies and Procedures

This document outlines security procedures and general policies for **eLabFTW**.

## Reporting a security issue

All security bugs will be considered seriously.

Thank you for improving the security of **eLabFTW** with a responsible disclosure.

Please send me an email (preferentially encrypted) to the address listed here:

~~~bash
gpg --search-keys "Nicolas CARPi"
~~~

Alternatively, you can contact me securely through my [Keybase profile](https://keybase.io/nicolascarpi).

If you don't get a reply in the next 48h, it means I'm probably dead.

## Steps taken in the development process

For every new commit, the dependencies are checked by [Snyk.io](https://snyk.io/) to look for vulnerabilities in the dependencies. A vulnerability found means a broken test suite.

There is also GitHub notifying me of new vulnerabilities it finds in the dependencies.

The code itself is checked by various static analyzers to try and detect bugs sooner. And best practices are used to prevent SQL injection (with prepared statements), XSS (with a Content-Security-Policy header), and other nasty things.

If you scan the live demo for good practices and security headers, you'll find that eLabFTW scores very very high:

[![observatory score a+](https://i.imgur.com/2qI796u.png)](https://observatory.mozilla.org/analyze/demo.elabftw.net)

## Best practices

[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/2766/badge)](https://bestpractices.coreinfrastructure.org/projects/2766)

**eLabFTW** follows the best practices edicted by The Linux Foundation [Core Infrastructure Initiative](https://bestpractices.coreinfrastructure.org/en).

You can see the criteria [on this page](https://bestpractices.coreinfrastructure.org/en/projects/2766).

## Running an elabftw instance securely

Great you've installed elabftw. But you're a little bit paranoid because you're working on some top secret project and you don't want anyone to be able to look at it.

Here is a list of steps you should follow to increase the security of your instance.

### Run the Docker container

**eLabFTW**'s container has been fine tuned for maximizing safety. For instance, the default PHP configuration has been modified to make the session identifier longer, in a specific folder with tight permissions, a lot of functions not used in elabftw are forbidden, the cookies have the httpOnly, SameSite and secure flags, and other details that might not be easily modifiable if installed outside Docker.

Nginx also has a custom configuration with secure headers sent to the client.

Running **eLabFTW** outside Docker is possible, of course, but discouraged.

### But Docker is bad and GNU+Linux is bad

Ok you're one of the BSD people, right? And you like to run things your way, in a jail. That's fine. Just make sure to check the [configuration examples](https://github.com/elabftw/elabdoc/tree/master/config_examples), as they contain the secure configuration lines you need to have.

### Stay updated

Subscribe to the [Newsletter](http://eepurl.com/bTjcMj) to receive a notification on a new release. Or use GitHub's notification system (you can elect to be notified only on new releases). And update your instance to the latest version quickly!

### Have backups

See https://doc.elabftw.net/backup.html. And secure your backups ;)
