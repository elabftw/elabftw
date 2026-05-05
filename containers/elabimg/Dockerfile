# Dockerfile for elabftw web container
# nginx custom + php-fpm + elabftw complete production files
# https://github.com/elabftw/elabftw

FROM golang:1.25-alpine3.23 AS go-builder
# using an explicit default argument for TARGETPLATFORM will override the buildx implicit value
ARG TARGETPLATFORM
ENV TARGETPLATFORM=${TARGETPLATFORM:-linux/amd64}
WORKDIR /app
COPY containers/elabimg/helpers .
# allow building for ARM, disable CGO to have full static build, target linux, add -s and -w ldflags to remove debug symbols
RUN if [ "$TARGETPLATFORM" = "linux/amd64" ]; then ARCH=amd64; elif [ "$TARGETPLATFORM" = "linux/arm/v7" ]; then ARCH=arm; elif [ "$TARGETPLATFORM" = "linux/arm64" ]; then ARCH=arm64; else ARCH=amd64; fi \
    && CGO_ENABLED=0 GOOS=linux GOARCH=$ARCH go build -ldflags="-s -w" -o invoker invoker.go \
    && go build -ldflags="-s -w" -o chronos chronos.go

# build nginx with only the bare minimum of features or modules
# Note: no need to chain the RUN commands here as it's a builder image and nothing will be kept
FROM alpine:3.23 AS nginx-builder

ENV NGINX_VERSION=1.28.3
# pin nginx modules versions
# see https://github.com/google/ngx_brotli/issues/120 for the lack of tags
# BROKEN HASH: ENV NGX_BROTLI_COMMIT_HASH=63ca02abdcf79c9e788d2eedcc388d2335902e52
ENV NGX_BROTLI_COMMIT_HASH=6e975bcb015f62e1f303054897783355e2a877dc
# https://github.com/openresty/headers-more-nginx-module/tags
ENV HEADERS_MORE_VERSION=v0.39
# releases can be signed by any key on this page https://nginx.org/en/pgp_keys.html
# so this might need to be updated for a new release
# available keys: mdounin, maxim, sb, thresh
# the "signing key" is used for linux packages, see https://trac.nginx.org/nginx/ticket/205
# do not use KEY in the env name to avoid warning by getSecretsRegex() from buildkit
ENV PGP_SIGNING_PUBK_OWNER=thresh

# install dependencies: here we use brotli-dev, newer brotli versions we can remove that and build it
RUN apk add --no-cache git libc-dev pcre2-dev make gcc zlib-dev openssl-dev binutils gnupg cmake brotli-dev

# create a builder user and group
RUN addgroup -S -g 3148 builder && adduser -D -S -G builder -u 3148 builder
RUN mkdir /build && chown builder:builder /build
WORKDIR /build
USER builder

# clone the nginx modules
# NEW broken brotli code
#RUN git clone --recurse-submodules  --depth 25 https://github.com/google/ngx_brotli && cd ngx_brotli && git reset --hard $NGX_BROTLI_COMMIT_HASH && cd deps/brotli && mkdir out && cd out && \
#    cmake -DCMAKE_BUILD_TYPE=Release -DBUILD_SHARED_LIBS=OFF -DCMAKE_C_FLAGS="-Ofast -march=native -mtune=native -flto -funroll-loops -ffunction-sections -fdata-sections -Wl,--gc-sections" -DCMAKE_CXX_FLAGS="-Ofast -m64 -march=native -mtune=native -flto -funroll-loops -ffunction-sections -fdata-sections -Wl,--gc-sections" -DCMAKE_INSTALL_PREFIX=./installed .. && \
#    cmake --build . --config Release --target brotlienc
# OLD working brotli stuff
# removed the depth param
RUN git clone https://github.com/google/ngx_brotli && cd ngx_brotli && git reset --hard $NGX_BROTLI_COMMIT_HASH && cd ..
RUN git clone --depth 1 -b $HEADERS_MORE_VERSION https://github.com/openresty/headers-more-nginx-module

# now start the build
# get nginx source
ADD --chown=builder:builder https://nginx.org/download/nginx-$NGINX_VERSION.tar.gz nginx.tgz
# get nginx signature file
ADD --chown=builder:builder https://nginx.org/download/nginx-$NGINX_VERSION.tar.gz.asc nginx.tgz.asc
# get the corresponding public key
ADD --chown=builder:builder https://nginx.org/keys/$PGP_SIGNING_PUBK_OWNER.key nginx-signing.key
# import it and verify the tarball
RUN gpg --import nginx-signing.key
# only run on amd64 because it fails on arm64 for some weird unknown reason
RUN if [ "$TARGETPLATFORM" = "linux/amd64" ]; then gpg --verify nginx.tgz.asc; fi
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
        --pid-path=/run/nginx.pid \
        --error-log-path=/var/log/nginx/error.log \
        --http-log-path=/var/log/nginx/access.log \
        --lock-path=/run/nginx.lock \
        --http-client-body-temp-path=/run/nginx-client_body \
        --http-fastcgi-temp-path=/run/nginx-fastcgi \
        --user=nginx \
        --group=nginx \
        --with-threads \
        --with-http_ssl_module \
        --with-http_v2_module \
        --with-http_realip_module \
        --with-http_gzip_static_module \
        --with-http_stub_status_module \
        --add-module=/build/ngx_brotli \
        --add-module=/build/headers-more-nginx-module \
        --without-http_autoindex_module \
        --without-http_browser_module \
        --without-http_empty_gif_module \
        --without-http_geo_module \
        --without-http_limit_conn_module \
        --without-http_limit_req_module \
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

#############################
# ELABFTW + NGINX + PHP-FPM #
#############################
FROM alpine:3.23

# set build version with --build-arg ELABFTW_VERSION=X.Y.Z
ARG ELABFTW_VERSION=dev
ENV ELABFTW_VERSION=$ELABFTW_VERSION

LABEL net.elabftw.name="elabftw" \
    net.elabftw.description="eLabFTW web service" \
    net.elabftw.url="https://www.elabftw.net" \
    net.elabftw.vcs-url="https://github.com/elabftw/elabftw" \
    net.elabftw.elabftw-version=$ELABFTW_VERSION

# NGINX
# copy our nginx from the build image
COPY --from=nginx-builder /usr/sbin/nginx /usr/sbin/nginx
COPY --from=nginx-builder /etc/nginx/mime.types /etc/nginx/mime.types
COPY --from=nginx-builder /etc/nginx/fastcgi.conf /etc/nginx/fastcgi.conf

# create the log folder and make the logfiles links to stdout/stderr so docker logs will catch it
RUN mkdir -p /var/log/nginx \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log
# END NGINX

# install required packages
# php8-bcmath is required to send emails via Exchange (NTLM authenticator)
# php8-gd is required by mpdf for transparent png
# php8-tokenizer and php8-xmlwriter are for dev only
# php8-iconv is required by LdapRecord php library
# git is required by yarn
# imagemagick-svg is for mathjax support in pdfs
# imagemagick-heic is for support for heic/heif
# don't put line comments inside this instruction
RUN apk upgrade -U -a && apk add --no-cache \
    bash \
    brotli \
    curl \
    freetype \
    ghostscript \
    git \
    imagemagick-heic \
    imagemagick-svg \
    nodejs-current \
    openssl \
    php84 \
    php84-pecl-apcu \
    php84-bcmath \
    php84-curl \
    php84-ctype \
    php84-dev \
    php84-dom \
    php84-exif \
    php84-gd \
    php84-gettext \
    php84-fileinfo \
    php84-fpm \
    php84-iconv \
    php84-json \
    php84-intl \
    php84-ldap \
    php84-mbstring \
    php84-opcache \
    php84-openssl \
    php84-pdo_mysql \
    php84-pecl-imagick \
    php84-phar \
    php84-redis \
    php84-simplexml \
    php84-session \
    php84-sodium \
    php84-tokenizer \
    php84-xml \
    php84-xmlwriter \
    php84-zip \
    php84-zlib \
    tzdata \
    unzip \
    zopfli

# add a symlink to php8
RUN ln -f /usr/bin/php84 /usr/bin/php

# S6-OVERLAY
# install s6-overlay, our init system. Workaround for different versions using TARGETPLATFORM
# platform see https://docs.docker.com/engine/reference/builder/#automatic-platform-args-in-the-global-scope
ARG S6_OVERLAY_VERSION=3.2.1.0
ENV S6_OVERLAY_VERSION=$S6_OVERLAY_VERSION

# using an explicit default argument for TARGETPLATFORM will override the buildx implicit value
ARG TARGETPLATFORM
ENV TARGETPLATFORM=${TARGETPLATFORM:-linux/amd64}
RUN if [ "$TARGETPLATFORM" = "linux/amd64" ]; then ARCHITECTURE=x86_64; elif [ "$TARGETPLATFORM" = "linux/arm/v7" ]; then ARCHITECTURE=arm; elif [ "$TARGETPLATFORM" = "linux/arm64" ]; then ARCHITECTURE=aarch64; else ARCHITECTURE=amd64; fi \
    && curl -sS -L -O --output-dir /tmp/ --create-dirs "https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-${ARCHITECTURE}.tar.xz" \
    && curl -sS -L -O --output-dir /tmp/ --create-dirs "https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-noarch.tar.xz" \
    && tar xpJf "/tmp/s6-overlay-${ARCHITECTURE}.tar.xz" -C / \
    && tar xpJf "/tmp/s6-overlay-noarch.tar.xz" -C / \
    && rm /tmp/s6-overlay*
# create nginx s6 service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/nginx && echo "longrun" > /etc/s6-overlay/s6-rc.d/nginx/type
COPY ./containers/elabimg/nginx/run /etc/s6-overlay/s6-rc.d/nginx/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/nginx
# create php s6 service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/php && echo "longrun" > /etc/s6-overlay/s6-rc.d/php/type
COPY ./containers/elabimg/php/run /etc/s6-overlay/s6-rc.d/php/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/php
# END S6-OVERLAY

# ELABFTW
RUN mkdir /elabftw
WORKDIR /elabftw
# we only copy the strict necessary
COPY ./bin /elabftw/bin
COPY ./.babelrc /elabftw
COPY ./builder.js /elabftw
COPY ./composer.json /elabftw
COPY ./composer.lock /elabftw
COPY ./node-builder.js /elabftw
COPY ./package.json /elabftw
COPY ./src /elabftw/src
COPY ./tsconfig.json /elabftw
COPY ./var /elabftw/var
COPY ./web /elabftw/web
COPY ./yarn.lock /elabftw
COPY ./.yarnrc.yml /elabftw

# COMPOSER
ENV COMPOSER_HOME=/composer
# 2.9.7
COPY --from=composer@sha256:dc292c5c0f95f526b051d4c341bf08e7e2b18504c74625e3203d7f123050e318 /usr/bin/composer /usr/local/bin/composer

# this allows to skip the (long) build in dev mode where /elabftw will be bind-mounted anyway
# pass it to build command with --build-arg BUILD_ALL=0
ARG BUILD_ALL=1
# install php and js dependencies and build assets
# avoid installing cypress
ENV CYPRESS_INSTALL_BINARY=0
# avoid download prompt
ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0
# enable new yarn
RUN corepack enable
# some ini settings are set on the command line to override the restrictive production ones already set
# IMPORTANT: the yarn/build step must be done before the composer/install step because a source file (advancedQuery) will be generated by yarn
# so in order for composer to take it into account, it must exist before we call the install command of composer.
RUN if [ "$BUILD_ALL" = "1" ]; then yarn install \
    && yarn run buildall:prod \
    && yarn workspaces focus -A --production \
    && /usr/bin/php84 -d memory_limit=256M /usr/local/bin/composer install --prefer-dist --no-cache --no-progress --no-dev -a \
    && yarn cache clean && rm -r /root/.cache /root/.yarn; fi

# PHP: add php config after composer step so open_basedir is not an issue
COPY ./containers/elabimg/php/php.ini /etc/php84/php.ini
COPY ./containers/elabimg/php/php-fpm.conf /etc/php84/php-fpm.conf
COPY ./containers/elabimg/php/elabpool.conf /etc/php84/php-fpm.d/elabpool.conf
# END PHP

# declare a default value for these ENV values so they are accessible in php-cli
# they will still be overridden by runtime env, but if they are not declared here, php-cli won't see them ever
ENV PUBCHEM_PUG_URL=https://pubchem.ncbi.nlm.nih.gov/rest/pug
ENV PUBCHEM_PUG_VIEW_URL=https://pubchem.ncbi.nlm.nih.gov/rest/pug_view/data
# END ELABFTW

# NGINX PART 2
# copy nginx config files
COPY ./containers/elabimg/nginx/ /etc/nginx/
# the healthcheck.sh script checks if nginx replies to requests
# the HEALTHCHECK instruction allows to show healthy/unhealthy in "docker ps" output next to the container name
HEALTHCHECK --interval=2m --timeout=5s --retries=3 CMD sh /etc/nginx/healthcheck.sh
# nginx will run on port 443
EXPOSE 443
# END NGINX PART 2

# ENTRYPOINT
# create a oneshot service
RUN mkdir -p /etc/s6-overlay/s6-rc.d/entrypoint && echo "oneshot" > /etc/s6-overlay/s6-rc.d/entrypoint/type
COPY ./containers/elabimg/entrypoint/up /etc/s6-overlay/s6-rc.d/entrypoint/
RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/entrypoint

# docker-entrypoint.sh must run before nginx and php are started
RUN echo "entrypoint" > /etc/s6-overlay/s6-rc.d/nginx/dependencies
RUN echo "entrypoint" > /etc/s6-overlay/s6-rc.d/php/dependencies

COPY ./containers/elabimg/entrypoint/docker-entrypoint.sh /usr/sbin/docker-entrypoint.sh
# END DOCKER-ENTRYPOINT.SH

# INVOKER
COPY --from=go-builder /app/invoker /usr/local/bin/invoker
RUN chmod 755 /usr/local/bin/invoker

# CHRONOS
COPY --from=go-builder /app/chronos /usr/local/bin/chronos
RUN chmod 755 /usr/local/bin/chronos

# add a helper script to reload services easily
COPY ./containers/elabimg/entrypoint/reload.sh /usr/local/bin/reload
RUN chmod 700 /usr/local/bin/reload

# this file contains secrets
RUN chmod 400 /etc/php84/php-fpm.d/elabpool.conf

# add a cache busting query param for spreadsheet iframe assets
RUN sed -i -e "s/%REPLACED_AT_BUILD_TIME%/$(openssl rand -hex 4)/" /elabftw/web/spreadsheet.html

# start s6
ENTRYPOINT ["/init"]
