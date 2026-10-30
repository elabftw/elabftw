# elabimg

## Overview

This is the main Docker image for [eLabFTW](https://www.elabftw.net). It runs four services:

* nginx: the webserver
* php-fpm: to handle requests to PHP files
* chronos: executes tasks on a schedule (similar to a cronjob)
* invoker: executes tasks asynchronously (useful for exports for instance)

These services are managed by s6-overlay and are all heavily customized for running eLabFTW.

## Tags

Tags, in the context of Docker images, are what comes after the image name (`elabftw/elabimg`), separated by a colon. If you leave it empty, it defaults to `latest`, which will point to the latest stable release. But it is recommended to explicitly define the version you wish to run, e.g.: `elabftw/elabimg:5.3.4`.

When defining which image version to use, you can use different tags:

* `x.y.z`: the actual explicit version: recommended
* `stable`: the latest stable version
* `latest`: the latest stable version
* `edge`: the latest tagged version, can be a stable version or an alpha or beta, whatever is present on `master` branch of eLabFTW

## Building this image

From the repository root folder:

~~~bash
docker build -t elabftw/elabimg:X.Y.Z --build-arg ELABFTW_VERSION=X.Y.Z -f containers/elabimg/Dockerfile .
~~~

## Usage

An example configuration file for docker compose can be fetched like this:

~~~bash
curl -so docker-compose.yml "https://get.elabftw.net/?config"
~~~

After downloading the configuration file, open it in your preferred text editor to modify settings as necessary.

For usage of eLabFTW, see [documentation](https://doc.elabftw.net).

## ImageMagick build and security policy

ImageMagick and PHP's Imagick extension are built from checksum-pinned source archives in a separate stage. Only the BMP, GIF, HEIC, JPEG, PDF, PNG, PNM, PS, SVG, TIFF and WebP modules are compiled and installed. Unused parsers, loadable filters, Magick++, Perl bindings, X11, OpenCL, OpenMP, distributed pixel caches, filename pipes and pixel encryption are excluded. The runtime contains stripped shared objects and the `magick` utility, without development headers, static libraries or build tools.

The build retains Alpine's Q16 HDRI representation, Little CMS color support, FreeType/Fontconfig/Raqm text rendering, and linked librsvg. These also serve QR labels and MathJax rasterization in PDF exports. Codec and font libraries remain Alpine packages and receive updates through `apk upgrade`; ImageMagick and Imagick source versions and their checksums must be updated explicitly in the Dockerfile. Compiler/linker options enable stack protection, fortified functions, format-security checks, full RELRO and non-executable stacks.

The image installs an explicit policy at `/etc/ImageMagick-7/policy.xml`, shared by Imagick and ImageMagick command-line tools. It denies modules, coders, external delegates, and filters by default, then permits the formats needed for image previews. JPEG and PNG are the only output formats, used for thumbnails, QR labels, and rasterized MathJax.

PDF, EPS/PostScript, and TIFF input remain enabled because `MakeThumbnailFactory` supports them. Ghostscript is retained for document thumbnails, with ImageMagick 7.1.2-33 or newer required for its scene-selection limit fix. Only the four upstream Ghostscript rasterization delegates are installed, retaining their `-dSAFER` options; Ghostscript is not linked into PHP. SVG rendering uses linked librsvg; external SVG converters, HTTP(S) coders, MSL, MVG, TEXT, EPHEMERAL, and XPS are absent. PNM formats are permitted as intermediate Ghostscript output.

Every image build runs `containers/elabimg/imagemagick/smoke-test.php` against the runtime libraries and PHP extension. It exercises stream-based thumbnails for every supported format, JPEG/PNG output, nested SVGs, font annotation, rejected pseudo-images/outputs and oversized dimensions. Fixtures are mounted only for that build step. Application tests in `MakeThumbnailTest`, `MakeQrPngTest`, `Tex2SvgTest` and `ImageMagickPolicyTest` provide further coverage.

Each ImageMagick process is limited to 256 MiB of pixel-cache memory, 512 MiB of mapped cache, 1 GiB of disk cache, 64 open cache files, two threads, and 60 seconds. Width and height are capped at 16,384 pixels, and image sequences at 128 frames/pages. The 32 MP area limit moves larger images to disk-backed cache; it is not a maximum pixel count. Uploads that exceed these limits are still stored, but their thumbnail generation is skipped by the existing error handler.

Inspect the policy loaded in a running container with:

~~~bash
docker exec elabftw magick identify -list policy
~~~

## Reloading services

If for some reason you wish to reload a service without restarting the container (for instance when trying configuration changes), you can use ``reload``:

~~~bash
# reload php and nginx
reload
# reload only php
reload php
# reload only nginx
reload nginx
~~~

## Deleting GitHub build cache

If there is a CVE fixed upstream, delete build cache with:

~~~bash
gh cache delete --all
~~~

Requires GitHub CLI: https://cli.github.com/
