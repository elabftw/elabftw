<?php
define('ROOT_PATH', '/srv/http/elabftw/');
$locale = 'fr_FR.UTF8';
$domain = 'messages';
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain($domain, ROOT_PATH."locale/nocache");
bindtextdomain($domain, ROOT_PATH."locale");
textdomain($domain);
