# syntax=docker/dockerfile:1.3
# Dockerfile for CI image
FROM elabftw/elabimg:ci

# add tests related folders and files to the image
COPY ./tests /elabftw/tests
COPY ./eslint.config.mjs /elabftw
COPY ./.php-cs-fixer.dist.php /elabftw
COPY ./.stylelintrc.json /elabftw
COPY ./codeception.yml /elabftw
COPY ./cypress.config.ts /elabftw

# install phpDocumentor
# https://github.com/phpDocumentor/phpDocumentor/releases/
ARG PHP_DOCUMENTOR_VERSION=v3.7.1
ADD --chmod=755 https://github.com/phpDocumentor/phpDocumentor/releases/download/$PHP_DOCUMENTOR_VERSION/phpDocumentor.phar phpdoc

# phpDocumentor requires ext-iconv and plantuml, graphviz for generating the svg graph
RUN apk add --update --no-cache plantuml graphviz php84-iconv

# Install xdebug for coverage
RUN apk add --update php84-pecl-xdebug
RUN printf "zend_extension=xdebug.so\nxdebug.mode=coverage" > /etc/php84/conf.d/50_xdebug.ini

# this yarn install is here because when running test suite we don't necessarily install js first in container elabtmp
RUN yarn install

# add routes used by c3.php (codecoverage) into nginx config
RUN sed -i '/# REST API v1/i #c3 codecoverage routes\nlocation ~ ^/c3/report/(clear|serialized|html|clover)/?$ {\n    rewrite /c3/report/.*$ /login.php last;\n}\n' /etc/nginx/common.conf

# add c3_wrapper.php as auto_prepend_file; See c3_wrapper.php for details
RUN sed -i 's|^auto_prepend_file =|& /elabftw/tests/c3_wrapper.php|' /etc/php84/php.ini
