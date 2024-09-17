# Security Policies and Procedures

This document outlines security procedures and general policies for **eLabFTW**.

## Reporting a security issue

See our [Responsible Disclosure Policy](https://www.deltablot.com/security/).

## Steps taken in the development process

### Code quality

The code itself is checked by various static analyzers to try and detect bugs sooner. And best practices are used to prevent SQL injection (with prepared statements), XSS (with a Content-Security-Policy header), and other nasty things.

If you scan the live demo for good practices and security headers, you'll find that eLabFTW scores very very high:

[![observatory score a+](https://i.imgur.com/mT9GH9I.png)](https://observatory.mozilla.org/analyze/demo.elabftw.net)

### Best practices

[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/2766/badge)](https://bestpractices.coreinfrastructure.org/projects/2766)

**eLabFTW** follows the best practices edicted by The Linux Foundation [Core Infrastructure Initiative](https://bestpractices.coreinfrastructure.org/en).

You can see the criteria [on this page](https://bestpractices.coreinfrastructure.org/en/projects/2766).

## Steps taken during the release process

### Dependencies scans

GitHub/Dependabot as well as Snyk.io are regularly scanning the dependencies and will report if a version used has a known vulnerability. [roave/security-advisories](https://github.com/Roave/SecurityAdvisories) is present as `devDependencies` and will prevent use of a PHP dependency with known vulnerability.

### Docker image

The Docker image is built by a GitHub Action, so the build process is transparent, as logs are public.

After the main step of the build, a full scan of the Alpine Linux container is done with [Trivy](https://github.com/aquasecurity/trivy-action) vulnerability scanner. [Ci/mon](https://cycode.com/cimon-build-hardening/) is used to prevent supply chain attacks during build.

## Container security

The container image (elabftw/elabimg) is built with many custom instructions. It uses multi-stages build so building dependencies are not present in the final image.

### Nginx

**nginx** (the webserver) is built from the sources in a dedicated build layer:

#### Before compilation

* installation of build dependencies
* creation of a `builder` user and working directory
* download of `ngx_brotli` and `headers-more-nginx-module` modules at specific versions
* download from nginx.org of a specific release
* verification of correct GPG signature of the archive

#### Compilation

**nginx** is compiled without the modules that are not needed. It also uses the following compilation flags:

* -g0: Disable debugging symbols generation (decreases binary size)
* -O3: Enable aggressive optimization level 3 (improves code execution speed)
* -fstack-protector-strong: Enable stack protection mechanisms (prevents stack-based buffer overflows)
* -flto: Enable Link Time Optimization (LTO) (allows cross-source-file optimization)
* -pie: Generate position-independent executables (PIE) (enhances security)
* --param=ssp-buffer-size=4: Set the size of the stack buffer for stack smashing protection to 4 bytes
* -Wformat -Werror=format-security: Enable warnings for potentially insecure usage of format strings (treats them as errors)
* -D_FORTIFY_SOURCE=2: Enable additional security features provided by fortified library functions
* -Wl,-z,relro,-z,now: Enforce memory protections at runtime:
*    - Mark the Global Offset Table (GOT) as read-only after relocation
*    - Resolve all symbols at load time, making them harder to manipulate
* -Wl,-z,noexecstack: Mark the stack as non-executable (prevents execution of code placed on the stack)
* -fPIC: Generate position-independent code (PIC) (suitable for building shared libraries)

#### Configuration

**nginx** is configured to serve php files from a list of allowed php files, which prevents running php from unknown locations.

### cronie

**cronie** is built from the sources of a specific version, with a custom `APKBUILD` removing unneeded modules and enable security flags (relro, pie).

### php and php-fpm

**PHP-fpm** is configured with a customized configuration file, and **php** is configured with a customized `php.ini` file changing many parameters to increase security (increase session id entropy, secure sessions directory, disable functions, use openbasedir restrictions, etc...).

### Javascript dependencies

The dependencies are installed with the `--pure-lockfile` flag, so only the known, specific, tested versions can be downloaded and installed. Only the productiion dependencies are installed (`--prod`).

### Trivy scan

Once the image is built, a scan is performed by Trivy Vulnerability Scanner and a found vulnerability will prevent the push to the registry.

## Running an eLabFTW instance securely

Here is a list of steps you should follow to increase the security of your instance.

### Prevent external access

Exposing the eLabFTW service only internally is a good way to avoid having the whole world attacking it.

### Add a reverse proxy

A reverse proxy in front of the eLabFTW container can allow you to enable additional mitigation strategies such as Apache's modsecurity or a Web Application Firewall.

### Enable 2FA

You can enforce multi factors authentication (with TOTP mechanism) for Sysadmins, Admins or all users. It is also important to educate users on the importance of password managers and the use of unique and long passwords.

### Stay updated

Subscribe to the [Newsletter](http://eepurl.com/bTjcMj) to receive a notification on a new release. Or use GitHub's notification system (you can elect to be notified only on new releases). And update your instance to the latest version quickly!

### Have backups

See [Backup Documentation](https://doc.elabftw.net/backup.html). And secure your backups on a filesystem with immutable snapshots! ;)

### Verifying releases

Releases are tagged with a GPG key, and GitHub displays a green check macaron next to the tag.

# Responsible disclosure hall of fame

These users reported vulnerabilities responsibly:

- Alexander Haller
- Anders Märak Leffler (@anargam)
- Matthias Grönewald
- @krastanoel
- Rafal Lykowski (@mgrRaf)
- Piyush Patil (@xoffense).
- @xskullboyx
- Harinder Singh
