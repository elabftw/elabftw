# Dev image for eLabFTW
# we use the locally built elabimg
# but we can override it with elabftw/elabimg:edge for instance
ARG ELABIMG_BASE=elabimg
FROM ${ELABIMG_BASE}

# COREPACK/YARN
ENV COREPACK_HOME=/var/cache/elabftw/corepack
ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0

# npm is to install corepack (which enables yarn)
# php84-phar, unzip is for composer
# php84-tokenizer is required by PHP-CS-Fixer and Psalm
RUN apk add --no-cache \
    npm \
    php84-phar \
    php84-tokenizer \
    unzip \
    && mkdir -p "$COREPACK_HOME" \
    && npm install -g corepack@0.35.0 \
    && corepack enable

# COMPOSER
ENV COMPOSER_HOME=/var/cache/elabftw/composer
# https://github.com/composer/composer/releases/tag/2.11.0
COPY --from=composer@sha256:278dfc7bd35169965d03f87194816f43bf4e6b6ea61707c2114e52ad12fe24ce /usr/bin/composer /usr/local/bin/composer
