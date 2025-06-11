# syntax=docker/dockerfile:1.3
# Dockerfile for CI image
# we allow specifying a base image branch so the build will happen on the corresponding branch of elabimg
ARG BASE_IMAGE_VERSION=hypernext
FROM elabftw/elabimg:$BASE_IMAGE_VERSION

# install phpcov
ADD --chmod=755 https://phar.phpunit.de/phpcov-11.0.0.phar /usr/bin/phpcov

RUN yarn install
# Install xdebug for coverage
RUN apk add --update php84-pecl-xdebug
RUN printf "zend_extension=xdebug.so\nxdebug.mode=coverage" > /etc/php84/conf.d/42_xdebug.ini

# add routes used by c3.php (codecoverage) into nginx config
RUN sed -i '/# REST API v1/i #c3 codecoverage routes\nlocation ~ ^/c3/report/(clear|serialized|html|clover)/?$ {\n    rewrite /c3/report/.*$ /login.php last;\n}\n' /etc/nginx/common.conf

# add c3_wrapper.php as auto_prepend_file; See c3_wrapper.php for details
RUN sed -i 's|^auto_prepend_file =|& /elabftw/tests/c3_wrapper.php|' /etc/php84/php.ini
