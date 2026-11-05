# Security Policy

This document explains how to report a vulnerability in **eLabFTW**, what we do to keep it secure, and what you can do to keep your instance secure.

## Reporting a vulnerability

Please **do not open a public issue** for a security problem. Follow our [Responsible Disclosure Policy](https://www.deltablot.com/security/) instead.

Only the latest release receives security fixes, so check that the issue is still present on it before reporting.

### Reports made with AI tools

Using AI tools to find bugs is fine. Sending us their raw output is not. A useful report:

- is reproduced by you, on a current version, with a working proof of concept;
- explains the actual impact, not just that a pattern looks dangerous;
- is short and written for a human reader.

Unverified, generated reports cost maintainer time and will be closed without a detailed answer. The same applies to code contributions: AI-assisted code is welcome, but it must be reviewed and tested by a human who can explain it, and it goes through the same review as any other pull request.

## Development

- **Safe defaults in the code**: prepared statements against SQL injection, a strict Content-Security-Policy against XSS, CSRF tokens, and authorization checks enforced in the models.
- **Static analysis and tests**: phpstan, psalm, CodeQL, unit, API and end-to-end tests run on every pull request.
- **Human review**: every change is reviewed by a maintainer before it is merged.
- **Dependencies**: Dependabot reports vulnerable versions, and [roave/security-advisories](https://github.com/Roave/SecurityAdvisories) prevents installing a PHP dependency with a known vulnerability.
- **Best practices**: eLabFTW holds the [OpenSSF Best Practices badge](https://www.bestpractices.dev/projects/2766).

[![OpenSSF Best Practices](https://www.bestpractices.dev/projects/2766/badge)](https://www.bestpractices.dev/projects/2766)

The live demo also scores very high on the [Mozilla Observatory](https://developer.mozilla.org/en-US/observatory/analyze?host=demo.elabftw.net).

## Build and release

- **Transparent builds**: the Docker image is built by GitHub Actions from this repository, with public logs. Actions are pinned to a commit hash.
- **Network allow-list during build**: [Cimon](https://github.com/CycodeLabs/cimon-action) blocks any connection to a host that is not on a short list, to stop supply chain attacks during the build.
- **Pinned inputs**: base images are pinned by digest. The nginx, ImageMagick, Imagick and s6-overlay archives are pinned to an exact version and checked against a SHA-256 checksum, and the nginx release is also verified with the GPG keys of its developers. The nginx modules (`ngx_brotli` and `headers-more`) are pinned to an exact Git commit.
- **Provenance and SBOM**: each release has a signed [build provenance attestation](https://github.com/elabftw/elabftw/attestations) and a Software Bill of Materials. Verify an image with:

```bash
gh attestation verify oci://docker.io/elabftw/elabimg:<version> --owner elabftw
```

- **Signed tags**: release tags are signed with GPG, and GitHub shows them as verified.

Image vulnerability scans with Trivy are currently paused, following the compromise of the Trivy GitHub Action. They will be added back once it can be used safely.

## Container image

The image (`elabftw/elabimg`) is built to have as little attack surface as possible. Its full description is in [containers/elabimg/README.md](containers/elabimg/README.md).

### Small runtime

- Multi-stage build: compilers, build tools and headers stay in the build stages and never reach the final image.
- Only what is needed is compiled in. Programs built from source are stripped of debug symbols.
- **Ghostscript is not installed.** PDF thumbnails are rendered by Poppler (`pdftoppm`), which has no PostScript or JavaScript interpreter, through a wrapper that limits its CPU time and memory.

### Hardened compilation

nginx, Brotli, ImageMagick and Imagick are compiled from source with:

- position-independent executables, full RELRO and a non-executable stack;
- stack protector, stack clash protection and zero-initialized stack variables;
- `_FORTIFY_SOURCE=3`, with musl's fortify headers so it actually applies;
- `-Werror=format-security`, and no optimizations that rely on undefined behavior (`-fno-strict-overflow`, `-fno-delete-null-pointer-checks`, `-fno-strict-aliasing`);
- return address signing on arm64.

The Go helpers (`invoker` and `chronos`) are built without debug symbols or local paths, and as position-independent executables on amd64 and arm64 (Go cannot build them as PIE without cgo on 32-bit arm).

### Services

- **nginx**: built with only the modules the configuration uses (no gzip, SSI, autoindex, upstream balancing, memcached, uwsgi, scgi…). PHP is only executed for an explicit list of files. The `Proxy` request header is not passed to PHP ([httpoxy](https://httpoxy.org/)).
- **PHP**: a custom `php.ini` and php-fpm pool with dangerous functions disabled, `open_basedir` restrictions and strict session settings.
- **ImageMagick**: a custom build with only the BMP, GIF, HEIC, JPEG, PNG, SVG, TIFF and WebP coders. It cannot read PDF or PostScript, uses no external delegate, and a strict `policy.xml` denies everything else and limits memory, disk, time and image size. It ignores policy files from the user's home directory. A smoke test checks all of this at every build.
- **invoker** (background tasks): listens on a socket only its user can access, checks a pre-shared key in constant time, and only runs an exact list of commands with validated arguments.
- **chronos** (scheduled tasks): each run has a timeout, and runs of the same task never overlap.
- **JavaScript and PHP dependencies**: installed from the lockfiles only, without development dependencies.

### Runtime

- The container **refuses to start as root**. It runs as an unprivileged user, and s6 stops the container if a service fails to start.
- The example [docker-compose.yml](containers/elabimg/docker-compose.yml-EXAMPLE) runs it with a read-only filesystem, all capabilities dropped, `no-new-privileges`, and a limit on the number of processes.

## Running an instance securely

- **Use the example configuration**: keep its security settings (non-root user, `read_only`, `cap_drop`, `no-new-privileges`, `pids_limit`).
- **Limit exposure**: only expose the eLabFTW service, ideally only to the networks that need it.
- **Use a reverse proxy**: it lets you add a Web Application Firewall, and with external authentication (_e.g._ `mod_auth_mellon`), unauthenticated users never reach eLabFTW.
- **Enforce 2FA**: TOTP can be required for Sysadmins, Admins or all users. Encourage password managers and long, unique passwords.
- **Using SSO?** Disable local registration and login. A Sysadmin with database access can re-enable them if the IdP fails.
- **Prevent identity changes**: since [version 5.1.0](https://www.deltablot.com/posts/release-50100/#new-sysconfig-setting-to-prevent-users-from-changing-their-identity), Sysadmins can stop users from changing their name or email. Unless it is required, disable it.
- **Keep optional features off** unless you need them (_e.g._ link sharing).
- **Separate instances, not just teams**: sensitive research and short-lived student projects should not share an instance. Separate instances limit the impact of a bug and allow different settings for access, logging and backups.
- **Update quickly**: subscribe to the [newsletter](https://eepurl.com/bTjcMj) or to GitHub release notifications, and pin an explicit image version (`elabftw/elabimg:X.Y.Z`).
- **Back up**: see the [backup documentation](https://doc.elabftw.net/docs/install/backups), and keep backups on storage with immutable snapshots.

## Hall of fame

Thank you to these people for reporting vulnerabilities responsibly:

- Alexander Haller
- Anders Märak Leffler (@anargam)
- Matthias Grönewald
- HuajiHD
- @krastanoel
- Rafal Lykowski (@mgrRaf)
- Piyush Patil (@xoffense)
- @xskullboyx
- Harinder Singh
- Mahadi Xion
- Bryan Lynch
