# Dockerfile for elabftw web container
# nginx custom + php-fpm + elabftw complete production files
# https://github.com/elabftw/elabftw

###############################
# GO BUILDER: CHRONOS/INVOKER #
###############################
# https://hub.docker.com/layers/library/golang/1.27.3-alpine3.24/images/sha256-7eadb9cbb0aab2649418025b8472a770195e9a94921309519896514de85bba7f
FROM golang@sha256:d200d2dd76b03a99b52651fd1325aff7dcd35422c35c01bac56571b0507f3deb AS go-builder

# using an explicit default argument for TARGETPLATFORM will override the buildx implicit value
ARG TARGETPLATFORM
ENV TARGETPLATFORM=${TARGETPLATFORM:-linux/amd64}
WORKDIR /app
COPY containers/elabimg/helpers .
# allow building for ARM, disable CGO to have full static build, target linux, add -s and -w ldflags to remove debug symbols
RUN if [ "$TARGETPLATFORM" = "linux/amd64" ]; then ARCH=amd64; elif [ "$TARGETPLATFORM" = "linux/arm/v7" ]; then ARCH=arm; elif [ "$TARGETPLATFORM" = "linux/arm64" ]; then ARCH=arm64; else ARCH=amd64; fi \
    && CGO_ENABLED=0 GOOS=linux GOARCH=$ARCH go build -ldflags="-s -w" -o invoker invoker.go \
    && go build -ldflags="-s -w" -o chronos chronos.go

#################
# NGINX BUILDER #
#################
# build nginx with only the bare minimum of features or modules
# https://hub.docker.com/layers/library/alpine/3.24.3/images/sha256-6708ca119efec5c3fdcad5dc95d8a28a529aad8debc95872b7e12b3941882f55
FROM alpine@sha256:be2656061b536c1883c4034790e21d0a09c5aaaf51968b89368a3c0371ea2c9a AS nginx-builder

ENV NGINX_VERSION=1.30.6
# pin nginx modules versions
# see https://github.com/google/ngx_brotli/issues/120 for the lack of tags
ENV NGX_BROTLI_COMMIT=53e0fb55f250f48fe80c0ddbf6c0f7103cdbaae1
# https://github.com/openresty/headers-more-nginx-module/tags
ENV HEADERS_MORE_VERSION=v0.39

# install dependencies: here we use brotli-dev, newer brotli versions we can remove that and build it
RUN apk add --no-cache git libc-dev pcre2-dev make gcc zlib-dev openssl-dev binutils gnupg cmake brotli-dev

# create a builder user and group
RUN addgroup -S -g 3148 builder && adduser -D -S -G builder -u 3148 builder
RUN mkdir /build && chown builder:builder /build
WORKDIR /build
USER builder

# build brotli
RUN git clone https://github.com/google/ngx_brotli \
    && cd ngx_brotli \
    && git checkout "$NGX_BROTLI_COMMIT" \
    && git submodule update --init --recursive \
    && cd deps/brotli \
    && mkdir out \
    && cd out \
    && cmake \
        -DCMAKE_BUILD_TYPE=Release \
        -DBUILD_SHARED_LIBS=OFF \
        -DCMAKE_C_FLAGS="-O2 -flto -funroll-loops -ffunction-sections -fdata-sections" \
        -DCMAKE_CXX_FLAGS="-O2 -flto -funroll-loops -ffunction-sections -fdata-sections" \
        -DCMAKE_EXE_LINKER_FLAGS="-Wl,--gc-sections" \
        .. \
    && cmake --build . --config Release --target brotlienc

RUN git clone --depth 1 -b $HEADERS_MORE_VERSION https://github.com/openresty/headers-more-nginx-module

# now start the build
# get nginx source
ADD --chown=builder:builder https://nginx.org/download/nginx-$NGINX_VERSION.tar.gz nginx.tgz
# get nginx signature file
ADD --chown=builder:builder https://nginx.org/download/nginx-$NGINX_VERSION.tar.gz.asc nginx.tgz.asc
# get all developer keys listed at https://nginx.org/en/pgp_keys.html
# nginx_signing.key is only used for packages and repositories
ADD --chown=builder:builder https://nginx.org/keys/arut.key nginx-arut.key
ADD --chown=builder:builder https://nginx.org/keys/pluknet.key nginx-pluknet.key
ADD --chown=builder:builder https://nginx.org/keys/sb.key nginx-sb.key
ADD --chown=builder:builder https://nginx.org/keys/thresh.key nginx-thresh.key
# import them and verify the tarball
RUN gpg --batch --import nginx-arut.key nginx-pluknet.key nginx-sb.key nginx-thresh.key \
    && gpg --batch --verify nginx.tgz.asc nginx.tgz
# all good now untar and build!
RUN tar xzf nginx.tgz
WORKDIR /build/nginx-$NGINX_VERSION
# Compilation flags
# -g0: Disable debugging symbols generation (decreases binary size)
# -O3: Enable aggressive optimization level 3 (improves code execution speed)
# -fstack-protector-strong: Enable stack protection mechanisms (prevents stack-based buffer overflows)
# -flto: Enable Link Time Optimization (LTO) (allows cross-source-file optimization)
# -pie: Generate position-independent executables (PIE) (enhances security)
# --param=ssp-buffer-size=4: Set the size of the stack buffer for stack smashing protection to 4 bytes
# -Wformat -Werror=format-security: Enable warnings for potentially insecure usage of format strings (treats them as errors)
# -D_FORTIFY_SOURCE=2: Enable additional security features provided by fortified library functions
# -Wl,-z,relro,-z,now: Enforce memory protections at runtime:
#    - Mark the Global Offset Table (GOT) as read-only after relocation
#    - Resolve all symbols at load time, making them harder to manipulate
# -Wl,-z,noexecstack: Mark the stack as non-executable (prevents execution of code placed on the stack)
# -fPIC: Generate position-independent code (PIC) (suitable for building shared libraries)
RUN ./configure \
        --prefix=/var/lib/nginx \
        --sbin-path=/usr/sbin/nginx \
        --with-cc-opt='-g0 -O3 -fstack-protector-strong -flto -pie --param=ssp-buffer-size=4 -Wformat -Werror=format-security -D_FORTIFY_SOURCE=2 -Wl,-z,relro,-z,now -Wl,-z,noexecstack -fPIC'\
        --modules-path=/usr/lib/nginx/modules \
        --conf-path=/etc/nginx/nginx.conf \
        --pid-path=/run/nginx/nginx.pid \
        --error-log-path=/dev/stderr \
        --http-log-path=/dev/stdout \
        --lock-path=/run/nginx/nginx.lock \
        --http-client-body-temp-path=/var/cache/elabftw/nginx/client_body \
        --http-fastcgi-temp-path=/var/cache/elabftw/nginx/fastcgi \
        --http-proxy-temp-path=/var/cache/elabftw/nginx/proxy \
        --with-threads \
        --with-http_ssl_module \
        --with-http_v2_module \
        --with-http_realip_module \
        --with-http_stub_status_module \
        --add-module=/build/ngx_brotli \
        --add-module=/build/headers-more-nginx-module \
        --without-http_autoindex_module \
        --without-http_browser_module \
        --without-http_empty_gif_module \
        --without-http_geo_module \
        --without-http_limit_conn_module \
        --without-http_map_module \
        --without-http_memcached_module \
        --without-http_referer_module \
        --without-http_scgi_module \
        --without-http_split_clients_module \
        --without-http_ssi_module \
        --without-http_upstream_ip_hash_module \
        --without-http_userid_module \
        --without-http_uwsgi_module \
    && make -j$(getconf _NPROCESSORS_ONLN) \
    && strip -s objs/nginx

USER root
RUN make install

#####################
# OPENBABEL BUILDER #
#####################
# https://hub.docker.com/layers/library/alpine/3.24.3/images/sha256-6708ca119efec5c3fdcad5dc95d8a28a529aad8debc95872b7e12b3941882f55
FROM alpine@sha256:be2656061b536c1883c4034790e21d0a09c5aaaf51968b89368a3c0371ea2c9a AS openbabel-builder

# Version 3.2.1
# https://github.com/openbabel/openbabel/releases/tag/openbabel-3-2-1
ARG OPENBABEL_REF=0e94434fa75c9f61095023e3c12e0d5f2ac035ff

RUN apk add --no-cache \
    build-base \
    cmake \
    git \
    perl

WORKDIR /build/openbabel

RUN git init \
    && git remote add origin https://github.com/openbabel/openbabel.git \
    && git fetch --depth 1 origin "$OPENBABEL_REF" \
    && git checkout --detach FETCH_HEAD


# Open Babel 3.2.1 incorrectly registers the InChI descriptor in static
# builds even when InChI support is disabled.
RUN sed -i \
        '/theInChIFilter.*GetID/d' \
        src/plugin.cpp

RUN cmake -S . -B build \
        -DCMAKE_BUILD_TYPE=Release \
        -DBUILD_SHARED=OFF \
        -DBUILD_MIXED=OFF \
        -DMINIMAL_BUILD=OFF \
        -DENABLE_TESTS=OFF \
        -DBUILD_GUI=OFF \
        -DBUILD_DOCS=OFF \
        -DWITH_INCHI=OFF \
        -DWITH_STATIC_INCHI=OFF \
        -DWITH_STATIC_LIBXML=OFF \
        -DPYTHON_BINDINGS=OFF \
        -DRUN_SWIG=OFF \
    && cmake --build build \
        --target obabel \
        --parallel "$(getconf _NPROCESSORS_ONLN)" \
    && strip -s build/bin/obabel

#######################
# IMAGEMAGICK BUILDER #
#######################
# Keep the builder and runtime on the same Alpine release and architecture.
FROM alpine@sha256:be2656061b536c1883c4034790e21d0a09c5aaaf51968b89368a3c0371ea2c9a AS imagemagick-builder

# Update each version together with its archive checksum.
# https://github.com/ImageMagick/ImageMagick/releases/tag/7.1.2-34
ARG IMAGEMAGICK_VERSION=7.1.2-34
ARG IMAGEMAGICK_SHA256=55fe0ec246245b46c429f9e6bc65294ad4a78a66b88abd9817971ca157856323
# https://pecl.php.net/package/imagick/3.8.1
ARG IMAGICK_VERSION=3.8.1
ARG IMAGICK_SHA256=3a3587c0a524c17d0dad9673a160b90cd776e836838474e173b549ed864352ee

# PIC for shared objects, stack/format/fortify checks, full RELRO and a non-executable stack.
ENV CFLAGS="-O2 -g0 -fPIC -fstack-protector-strong -D_FORTIFY_SOURCE=2 -Wformat -Werror=format-security" \
    LDFLAGS="-Wl,-z,relro,-z,now,-z,noexecstack,--as-needed"
# With modules enabled, these are the only image parsers compiled and installed.
# Keep this list in sync with policy.xml and MakeThumbnailFactory.
ENV IMAGEMAGICK_CODERS="coders/bmp.la coders/gif.la coders/heic.la coders/jpeg.la coders/pdf.la coders/png.la coders/pnm.la coders/ps.la coders/svg.la coders/tiff.la coders/webp.la"

RUN apk add --no-cache \
    autoconf build-base curl fontconfig-dev freetype-dev ghostscript lcms2-dev \
    libheif-dev libjpeg-turbo-dev libpng-dev libraqm-dev librsvg-dev libtool \
    libwebp-dev libxml2-dev php84-dev tiff-dev xz-dev zlib-dev

RUN addgroup -S -g 3148 builder && adduser -D -S -G builder -u 3148 builder \
    && mkdir /build && chown builder:builder /build
WORKDIR /build
USER builder

RUN curl -fsSL -o imagemagick.tar.xz "https://github.com/ImageMagick/ImageMagick/releases/download/${IMAGEMAGICK_VERSION}/ImageMagick-${IMAGEMAGICK_VERSION}.tar.xz" \
    && curl -fsSL -o imagick.tgz "https://pecl.php.net/get/imagick-${IMAGICK_VERSION}.tgz" \
    && printf '%s  %s\n' "$IMAGEMAGICK_SHA256" imagemagick.tar.xz "$IMAGICK_SHA256" imagick.tgz | sha256sum -c - \
    && tar xf imagemagick.tar.xz \
    && tar xf imagick.tgz

WORKDIR /build/ImageMagick-${IMAGEMAGICK_VERSION}
# Retain Alpine's Q16 HDRI pixel representation and text/color rendering support.
# Ghostscript runs as a separate process; its library is never loaded into PHP.
# Module enumeration uses magick.la as a directory marker; use the retained PNG module.
RUN sed -i 's/TagToCoderModuleName("magick",filename)/TagToCoderModuleName("png",filename)/' MagickCore/module.c \
    && ./configure \
        --prefix=/usr \
        --sysconfdir=/etc \
        --enable-shared \
        --disable-static \
        --disable-docs \
        --disable-openmp \
        --disable-opencl \
        --disable-dpc \
        --disable-pipes \
        --disable-cipher \
        --enable-hdri \
        --with-quantum-depth=16 \
        --with-modules \
        --with-fontconfig \
        --with-freetype \
        --with-raqm \
        --with-heic \
        --with-jpeg \
        --with-lcms \
        --with-lzma \
        --with-png \
        --with-rsvg \
        --with-tiff \
        --with-webp \
        --with-xml \
        --with-zlib \
        --without-autotrace \
        --without-bzlib \
        --without-djvu \
        --without-dmr \
        --without-fftw \
        --without-flif \
        --without-fpx \
        --without-gslib \
        --without-gvc \
        --without-jbig \
        --without-jxl \
        --without-lqr \
        --without-magick-plus-plus \
        --without-openexr \
        --without-openjp2 \
        --without-pango \
        --without-perl \
        --without-raw \
        --without-uhdr \
        --without-wmf \
        --without-x \
        --without-zip \
        --without-zstd \
    && make -j"$(getconf _NPROCESSORS_ONLN)" coders_LTLIBRARIES="$IMAGEMAGICK_CODERS" filters_LTLIBRARIES=

USER root
# Install headers in the builder for Imagick, then stage only runtime artifacts.
RUN make coders_LTLIBRARIES="$IMAGEMAGICK_CODERS" filters_LTLIBRARIES= install-strip \
    && make coders_LTLIBRARIES="$IMAGEMAGICK_CODERS" filters_LTLIBRARIES= DESTDIR=/imagemagick-root install-strip \
    && rm -rf /imagemagick-root/usr/include /imagemagick-root/usr/lib/pkgconfig /imagemagick-root/usr/share/doc /imagemagick-root/usr/share/man \
    && rm -f /imagemagick-root/usr/lib/*.la /imagemagick-root/usr/lib/libMagick*.so \
    && find /imagemagick-root/usr/bin -mindepth 1 ! -name magick -delete \
    && install -Dm644 LICENSE /imagemagick-root/usr/share/licenses/imagemagick/LICENSE \
    && sed -n '/<delegatemap>/p; /<delegate decode="ps:\(alpha\|cmyk\|color\|mono\)"/p; /<\/delegatemap>/p' \
        config/delegates.xml > /imagemagick-root/etc/ImageMagick-7/delegates.xml

WORKDIR /build/imagick-${IMAGICK_VERSION}
USER builder
# Alpine's php84-pecl-imagick depends on the stock imagemagick package.
# Imagick 3.8.1 probes OpenMP independently of ImageMagick; omit that unused dependency.
RUN sed -i '/AC_MSG_CHECKING(omp_pause_resource_all usability)/,/^  PHP_SUBST(IMAGICK_SHARED_LIBADD)/c\  PHP_SUBST(IMAGICK_SHARED_LIBADD)' config.m4 \
    && phpize84 \
    && ./configure --with-php-config=/usr/bin/php-config84 --with-imagick=/usr \
    && make -j"$(getconf _NPROCESSORS_ONLN)" \
    && strip --strip-unneeded modules/imagick.so

USER root
RUN install -Dm755 modules/imagick.so "/imagemagick-root$(php-config84 --extension-dir)/imagick.so" \
    && mkdir -p /imagemagick-root/etc/php84/conf.d \
    && printf 'extension=imagick.so\n' > /imagemagick-root/etc/php84/conf.d/imagick.ini \
    && install -Dm644 LICENSE /imagemagick-root/usr/share/licenses/imagick/LICENSE
COPY ./containers/elabimg/imagemagick/policy.xml /imagemagick-root/etc/ImageMagick-7/policy.xml

##############
# S6 BUILDER #
##############
# https://hub.docker.com/layers/library/alpine/3.24.3/images/sha256-6708ca119efec5c3fdcad5dc95d8a28a529aad8debc95872b7e12b3941882f55
FROM alpine@sha256:be2656061b536c1883c4034790e21d0a09c5aaaf51968b89368a3c0371ea2c9a AS s6-builder

ARG S6_OVERLAY_VERSION=3.2.1.0
# Pin the published archive checksums; update them together with S6_OVERLAY_VERSION.
# https://github.com/just-containers/s6-overlay/releases/tag/v3.2.1.0
ARG S6_OVERLAY_NOARCH_SHA256=42e038a9a00fc0fef70bf0bc42f625a9c14f8ecdfe77d4ad93281edf717e10c5
ARG S6_OVERLAY_X86_64_SHA256=8bcbc2cada58426f976b159dcc4e06cbb1454d5f39252b3bb0c778ccf71c9435
ARG S6_OVERLAY_ARM_SHA256=1fc849a1a4f099f25b2ebfc6c6fc107a3d8284d1c36a1948c10430b95939409a
ARG S6_OVERLAY_AARCH64_SHA256=c8fd6b1f0380d399422fc986a1e6799f6a287e2cfa24813ad0b6a4fb4fa755cc
# using an explicit default argument for TARGETPLATFORM will override the buildx implicit value
ARG TARGETPLATFORM
ENV TARGETPLATFORM=${TARGETPLATFORM:-linux/amd64}

RUN apk add --no-cache curl xz

# ADD cannot be used cleanly here because the architecture-specific URL needs a shell-computed
# mapping from Docker's TARGETPLATFORM to the architecture names used by s6-overlay.
RUN mkdir -p /s6-root \
    && case "$TARGETPLATFORM" in \
        linux/amd64) ARCHITECTURE=x86_64; S6_OVERLAY_ARCH_SHA256="$S6_OVERLAY_X86_64_SHA256" ;; \
        linux/arm/v7) ARCHITECTURE=arm; S6_OVERLAY_ARCH_SHA256="$S6_OVERLAY_ARM_SHA256" ;; \
        linux/arm64) ARCHITECTURE=aarch64; S6_OVERLAY_ARCH_SHA256="$S6_OVERLAY_AARCH64_SHA256" ;; \
        *) echo "Unsupported TARGETPLATFORM: $TARGETPLATFORM" >&2; exit 1 ;; \
    esac \
    && curl -fsSL -o /tmp/s6-overlay-arch.tar.xz "https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-${ARCHITECTURE}.tar.xz" \
    && curl -fsSL -o /tmp/s6-overlay-noarch.tar.xz "https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-noarch.tar.xz" \
    && printf '%s  %s\n' \
        "$S6_OVERLAY_ARCH_SHA256" /tmp/s6-overlay-arch.tar.xz \
        "$S6_OVERLAY_NOARCH_SHA256" /tmp/s6-overlay-noarch.tar.xz \
        | sha256sum -c - \
    && tar xpJf /tmp/s6-overlay-arch.tar.xz -C /s6-root \
    && tar xpJf /tmp/s6-overlay-noarch.tar.xz -C /s6-root

################
# RUNTIME BASE #
################
# https://hub.docker.com/layers/library/alpine/3.24.3/images/sha256-6708ca119efec5c3fdcad5dc95d8a28a529aad8debc95872b7e12b3941882f55
FROM alpine@sha256:be2656061b536c1883c4034790e21d0a09c5aaaf51968b89368a3c0371ea2c9a AS runtime-base

# set build version with --build-arg ELABFTW_VERSION=X.Y.Z
ARG ELABFTW_VERSION=dev
ENV ELABFTW_VERSION=$ELABFTW_VERSION

# NGINX
COPY --from=nginx-builder /usr/sbin/nginx /usr/sbin/nginx
COPY --from=nginx-builder /etc/nginx/mime.types /etc/nginx/mime.types
COPY --from=nginx-builder /etc/nginx/fastcgi.conf /etc/nginx/fastcgi.conf
RUN echo 'fastcgi_param ELABFTW_REQUEST_ID $request_id;' >> /etc/nginx/fastcgi.conf
# END NGINX

# OPENBABEL
COPY --from=openbabel-builder /build/openbabel/build/bin/obabel /usr/local/bin/obabel

# install required packages
# php8-bcmath is required to send emails via Exchange (NTLM authenticator)
# php8-gd is required by mpdf for transparent png
# php8-iconv is required by LdapRecord php library
# php84-xmlwriter is required for multipart uploads with S3
# ImageMagick codec/font libraries remain Alpine packages so apk upgrade updates them.
# don't put line comments inside this instruction
RUN apk upgrade -U -a && apk add --no-cache \
    bash \
    fontconfig \
    freetype \
    ghostscript \
    lcms2 \
    libheif \
    libjpeg-turbo \
    libltdl \
    libpng \
    libraqm \
    librsvg \
    libwebp \
    libwebpdemux \
    libwebpmux \
    libxml2 \
    nodejs \
    openssl \
    php84 \
    php84-pecl-apcu \
    php84-bcmath \
    php84-curl \
    php84-ctype \
    php84-dom \
    php84-exif \
    php84-gd \
    php84-gettext \
    php84-fileinfo \
    php84-fpm \
    php84-iconv \
    php84-intl \
    php84-ldap \
    php84-mbstring \
    php84-opcache \
    php84-openssl \
    php84-pdo_mysql \
    php84-redis \
    php84-simplexml \
    php84-session \
    php84-sodium \
    php84-xml \
    php84-xmlwriter \
    php84-zip \
    tiff \
    tzdata \
    xz-libs \
    zlib

# add a symlink to php8
RUN ln -f /usr/bin/php84 /usr/bin/php

ENV TMPDIR=/var/cache/elabftw/php/tmp

# Install the matching libraries, restricted modules, policy and PHP extension together.
COPY --from=imagemagick-builder /imagemagick-root/ /
# Exercise the installed stack without leaving test fixtures in the image.
RUN --mount=type=bind,source=containers/elabimg/imagemagick,target=/tmp/imagemagick-test \
    --mount=type=bind,source=tests/_data/dfn.pdf,target=/tmp/imagemagick-test.pdf \
    ! apk info --exists imagemagick \
    && magick -version \
    && TMPDIR=/tmp php84 /tmp/imagemagick-test/smoke-test.php /tmp/imagemagick-test.pdf /usr/share/ghostscript/Resource/Font/NimbusSans-Regular

###################
# ELABFTW BUILDER #
###################
FROM runtime-base AS app-builder

# this allows to skip the (long) build in dev mode where /elabftw will be bind-mounted anyway
# pass it to build command with --build-arg BUILD_ALL=0
ARG BUILD_ALL=1

# COREPACK
ENV COREPACK_HOME=/var/cache/elabftw/corepack
ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0
ENV COREPACK_VERSION=0.35.0

# brotli is used to compress assets after yarn build
# npm is to install corepack (which enables yarn)
# php84-phar, unzip is for composer
RUN apk add --no-cache \
    brotli \
    npm \
    php84-phar \
    unzip \
    && mkdir -p "$COREPACK_HOME" \
    && npm install -g corepack@$COREPACK_VERSION \
    && corepack enable

# COMPOSER
RUN mkdir /elabftw
WORKDIR /elabftw
ENV COMPOSER_HOME=/var/cache/elabftw/composer
# https://github.com/composer/composer/releases/tag/2.11.0
COPY --from=composer@sha256:278dfc7bd35169965d03f87194816f43bf4e6b6ea61707c2114e52ad12fe24ce /usr/bin/composer /usr/local/bin/composer
# copy PHP dependency manifests first so application source changes don't invalidate this layer
COPY ./composer.json /elabftw
COPY ./composer.lock /elabftw
RUN mkdir -p "$TMPDIR" "$COMPOSER_HOME"

# install PHP dependencies without generating the autoloader yet
RUN if [ "$BUILD_ALL" = "1" ]; then \
        /usr/bin/php84 -d memory_limit=256M /usr/local/bin/composer install --prefer-dist --no-cache --no-progress --no-dev --no-autoloader; \
    fi

# YARN
# copy JS dependency manifests and the files required by the custom yarn plugin
COPY ./package.json /elabftw
COPY ./yarn.lock /elabftw
COPY ./.yarnrc.yml /elabftw
COPY ./src/tools/yarn-plugin-tinymce.js /elabftw/src/tools/yarn-plugin-tinymce.js
COPY ./src/scss/_tinymce.scss /elabftw/src/scss/_tinymce.scss
RUN mkdir -p /elabftw/web/assets

# install JS dependencies before copying the rest of the application source
RUN if [ "$BUILD_ALL" = "1" ]; then \
        yarn install --immutable; \
    fi

# now copy the application source and build inputs
COPY ./bin /elabftw/bin
COPY ./.babelrc /elabftw
COPY ./builder.js /elabftw
COPY ./node-builder.js /elabftw
COPY ./src /elabftw/src
COPY ./tsconfig.json /elabftw
COPY ./var /elabftw/var
COPY ./web /elabftw/web

# build assets, then generate the final composer autoloader after Parser.php exists
RUN if [ "$BUILD_ALL" = "1" ]; then \
        yarn run buildall:prod \
        && /usr/bin/php84 -d memory_limit=256M /usr/local/bin/composer dump-autoload --no-dev -a \
        && rm -rf /var/cache/elabftw \
        && rm -f .pnp.cjs .pnp.loader.mjs; \
    fi

###########
# RUNTIME #
###########
FROM runtime-base AS runtime

WORKDIR /elabftw

LABEL org.opencontainers.image.description="eLabFTW web service" \
    org.opencontainers.image.documentation="https://github.com/elabftw/elabftw/blob/master/containers/elabimg/README.md" \
    org.opencontainers.image.license="AGPL-3.0-only" \
    org.opencontainers.image.source="https://github.com/elabftw/elabftw" \
    org.opencontainers.image.title="eLabFTW" \
    org.opencontainers.image.url="https://www.elabftw.net" \
    org.opencontainers.image.vendor="Deltablot" \
    org.opencontainers.image.version="$ELABFTW_VERSION"

# S6-OVERLAY
# install s6-overlay, our init system
COPY --from=s6-builder /s6-root/ /
# create nginx s6 service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/nginx && echo "longrun" > /etc/s6-overlay/s6-rc.d/nginx/type
COPY ./containers/elabimg/nginx/run /etc/s6-overlay/s6-rc.d/nginx/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/nginx
# create php s6 service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/php && echo "longrun" > /etc/s6-overlay/s6-rc.d/php/type
COPY ./containers/elabimg/php/run /etc/s6-overlay/s6-rc.d/php/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/php
# END S6-OVERLAY

COPY --from=app-builder /elabftw /elabftw
# PHP: add php config after composer step so open_basedir is not an issue
COPY ./containers/elabimg/php/php.ini /etc/php84/php.ini.tpl
RUN rm -f /etc/php84/php.ini && ln -s /run/php/php.ini /etc/php84/php.ini
COPY ./containers/elabimg/php/php-fpm.conf /etc/php84/php-fpm.conf
COPY ./containers/elabimg/php/elabpool.conf /etc/php84/php-fpm.d/elabpool.conf.tpl
# END PHP

# LDAP: keep the runtime configuration on the writable /run tmpfs
RUN mv /etc/openldap/ldap.conf /etc/openldap/ldap.conf.tpl \
    && ln -s /run/openldap/ldap.conf /etc/openldap/ldap.conf

# create invoker service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/invoker && echo "longrun" > /etc/s6-overlay/s6-rc.d/invoker/type
COPY ./containers/elabimg/invoker/run /etc/s6-overlay/s6-rc.d/invoker/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/invoker
# create chronos service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/chronos && echo "longrun" > /etc/s6-overlay/s6-rc.d/chronos/type
COPY ./containers/elabimg/chronos/run /etc/s6-overlay/s6-rc.d/chronos/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/chronos

# declare a default value for these ENV values so they are accessible in php-cli
# they will still be overridden by runtime env, but if they are not declared here, php-cli won't see them ever
ENV PUBCHEM_PUG_URL=https://pubchem.ncbi.nlm.nih.gov/rest/pug
ENV PUBCHEM_PUG_VIEW_URL=https://pubchem.ncbi.nlm.nih.gov/rest/pug_view/data
# END ELABFTW

# NGINX PART 2
# copy nginx config files
COPY ./containers/elabimg/nginx/ /etc/nginx/

COPY ./containers/elabimg/nginx/nginx.conf /etc/nginx/nginx.conf.tpl
RUN rm -f /etc/nginx/nginx.conf && ln -s /run/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./containers/elabimg/nginx/common.conf /etc/nginx/common.conf.tpl
RUN rm -f /etc/nginx/common.conf && ln -s /run/nginx/common.conf /etc/nginx/common.conf
COPY ./containers/elabimg/nginx/opencloning.conf /etc/nginx/opencloning.conf.tpl
RUN rm -f /etc/nginx/opencloning.conf && ln -s /run/nginx/opencloning.conf /etc/nginx/opencloning.conf
# the healthcheck.sh script checks if nginx replies to requests
# the HEALTHCHECK instruction allows to show healthy/unhealthy in "docker ps" output next to the container name
HEALTHCHECK --interval=15s --timeout=5s --retries=3 --start-interval=5s --start-period=30s CMD sh /etc/nginx/healthcheck.sh
# nginx will run on port 8080
EXPOSE 8080
# END NGINX PART 2

# ENTRYPOINT
# create a oneshot service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/entrypoint && echo "oneshot" > /etc/s6-overlay/s6-rc.d/entrypoint/type
COPY ./containers/elabimg/entrypoint/up /etc/s6-overlay/s6-rc.d/entrypoint/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/entrypoint

# docker-entrypoint.sh must run before nginx and php are started
RUN echo "entrypoint" > /etc/s6-overlay/s6-rc.d/nginx/dependencies
RUN echo "entrypoint" > /etc/s6-overlay/s6-rc.d/php/dependencies
# invoker needs /run/elabftw folder created by entrypoint
RUN echo "entrypoint" > /etc/s6-overlay/s6-rc.d/invoker/dependencies
RUN echo "entrypoint" > /etc/s6-overlay/s6-rc.d/chronos/dependencies

COPY ./containers/elabimg/entrypoint/docker-entrypoint.sh /usr/sbin/docker-entrypoint.sh
# END DOCKER-ENTRYPOINT.SH

# INVOKER
COPY --from=go-builder /app/invoker /usr/local/bin/invoker
RUN chmod 555 /usr/local/bin/invoker

# CHRONOS
COPY --from=go-builder /app/chronos /usr/local/bin/chronos
RUN chmod 555 /usr/local/bin/chronos

# add a helper script to reload services easily
COPY ./containers/elabimg/entrypoint/reload.sh /usr/local/bin/reload
RUN chmod 555 /usr/local/bin/reload

# add a cache busting query param for spreadsheet iframe assets
RUN sed -i -e "s/%REPLACED_AT_BUILD_TIME%/$(openssl rand -hex 4)/" /elabftw/web/spreadsheet.html

# necessary for s6 if running with read-only flag
ENV S6_READ_ONLY_ROOT=1

# start s6
ENTRYPOINT ["/init"]
