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
# php84-xmlwriter is required by PHPUnit (via Codeception)
RUN apk add --no-cache \
    npm \
    php84-phar \
    php84-tokenizer \
    php84-xmlwriter \
    unzip \
    && mkdir -p "$COREPACK_HOME" \
    && npm install -g corepack@0.35.0 \
    && corepack enable

# COMPOSER
ENV COMPOSER_HOME=/var/cache/elabftw/composer
# 2.10.2
COPY --from=composer@sha256:5946476338742b200bb9ff88f8be56275ddae4b3949c72305cb0dbf10cfcb760 /usr/bin/composer /usr/local/bin/composer
