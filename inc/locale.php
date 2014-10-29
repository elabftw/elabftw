<?php
if (isset($_SESSION['prefs']['lang'])) {
    $locale = $_SESSION['prefs']['lang'].'.utf8';
} else {
    $locale = 'en_GB.utf8';
}
$domain = 'messages';
putenv("LC_ALL=$locale");
$res = setlocale(LC_ALL, $locale);
# uncomment this line to remove cache from gettext (need to do :
# "cd locale;ln -s nocache ." before)
#bindtextdomain($domain, ELAB_PATH."locale/nocache");
bindtextdomain($domain, ELAB_ROOT."locale");
textdomain($domain);
