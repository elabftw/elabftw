# Dev image for eLabFTW
# we use the locally built elabimg
# but we can override it with elabftw/elabimg:edge for instance
ARG ELABIMG_BASE=elabimg
FROM ${ELABIMG_BASE}

# COREPACK/YARN
ENV COREPACK_HOME=/var/cache/elabftw/corepack
ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0
ENV CYPRESS_INSTALL_BINARY=0

RUN apk add --no-cache \
    npm \
    && mkdir -p "$COREPACK_HOME" \
    && npm install -g corepack@0.35.0 \
    && corepack enable

# COMPOSER
# 2.10.2
COPY --from=composer@sha256:5946476338742b200bb9ff88f8be56275ddae4b3949c72305cb0dbf10cfcb760 /usr/bin/composer /usr/local/bin/composer
