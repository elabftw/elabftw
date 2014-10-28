<?php
define('ROOT_P_(at)H', '/srv/http/elabftw/');
$locale = 'fr_FR.UTF8';
$domain = 'messages';
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain($domain, ROOT_P_(at)H."locale/nocache");
bindtextdomain($domain, ROOT_P_(at)H."locale");
textdomain($domain);
