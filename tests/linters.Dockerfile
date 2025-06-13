# syntax=docker/dockerfile:1.3
# Dockerfile for CI Linters

FROM cimg/php:8.2-node

WORKDIR /elabftw

# Copy only what's needed for linting
COPY composer.json composer.lock ./
COPY package.json yarn.lock ./
COPY .php-cs-fixer.dist.php ./
COPY .stylelintrc.json ./
COPY eslint.config.mjs ./
COPY builder.js ./
COPY tsconfig.json ./
COPY src/ ./src/
COPY web/ ./web/
COPY bin/ ./bin/
COPY tests/ ./tests/

# Install dependencies
RUN yarn install --immutable --silent && \
    composer install --no-progress -q

# Default to running a linter (can override in docker run)
CMD ["yarn", "csslint"]
