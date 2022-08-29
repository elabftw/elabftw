# syntax=docker/dockerfile:1.3
# Dockerfile for CircleCI
FROM elabtmp

COPY .. /elabftw
# hidden files are not copied
COPY ../.stylelintrc.json /elabftw
COPY ../.eslintrc.js /elabftw
COPY ../.php-cs-fixer.dist.php /elabftw
