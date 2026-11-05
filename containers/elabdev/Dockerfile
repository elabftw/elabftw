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

# CLAUDE CODE
# start it with containers/elabdev/claude.sh, see README.md
# libgcc and libstdc++ are needed by the musl build of Claude Code
# on Alpine it must use the system ripgrep (USE_BUILTIN_RIPGREP=0)
ARG CLAUDE_CODE_VERSION=2.1.318
RUN apk add --no-cache \
    git \
    libgcc \
    libstdc++ \
    ripgrep \
    && npm install -g "@anthropic-ai/claude-code@${CLAUDE_CODE_VERSION}"
ENV USE_BUILTIN_RIPGREP=0
# the root filesystem is read-only: keep config, login and history in the persistent cache volume
ENV CLAUDE_CONFIG_DIR=/var/cache/elabftw/claude
# no telemetry, error reporting or auto-updates: also keeps the egress allowlist short
ENV CLAUDE_CODE_DISABLE_NONESSENTIAL_TRAFFIC=1
