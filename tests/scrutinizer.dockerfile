FROM elabftw/elabimg:hypernext

RUN apk upgrade -U -a && apk add --no-cache php8-simplexml

# phpStan
ADD https://github.com/phpstan/phpstan/releases/download/1.4.2/phpstan.phar /usr/bin/phpstan
RUN chmod +x /usr/bin/phpstan

# Psalm
ADD https://github.com/vimeo/psalm/releases/download/4.18.1/psalm.phar /usr/bin/psalm
RUN chmod +x /usr/bin/psalm

#Phan
ADD https://github.com/phan/phan/releases/download/5.3.1/phan.phar /usr/bin/phan
RUN chmod +x /usr/bin/phan

COPY ./bin /elabftw/bin
COPY ./src /elabftw/src 
COPY ./tests /elabftw/tests
COPY ./web /elabftw/web
COPY ./.eslintrc.js /elabftw
COPY ./.php-cs-fixer.dist.php /elabftw
COPY ./.stylelintrc.json /elabftw
COPY ./builder.js /elabftw
COPY ./codeception.yml /elabftw
COPY ./composer.json /elabftw
COPY ./composer.lock /elabftw
COPY ./cypress.json /elabftw
COPY ./node-builder.js /elabftw
COPY ./package.json /elabftw
COPY ./yarn.lock /elabftw
