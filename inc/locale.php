<?php
if (isset($_SESSION['prefs']['lang'])) {
    $locale = $_SESSION['prefs']['lang'].'.utf8';
} else {
    $locale = 'en_GB.utf8';
}
$domain = 'messages';
putenv("LC_ALL=$locale");
$res = setlocale(LC_ALL, $locale);
bindtextdomain($domain, ELAB_PATH."locale");
textdomain($domain);
