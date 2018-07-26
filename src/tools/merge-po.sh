#!/bin/bash
# https://www.elabftw.net

# generate the .po file for french lang
# it can then be imported in poeditor for the other languages
# first generate the cache files of twig templates
# $ php src/tools/generate-cache.php

# add it to the list
find /tmp/elabftw-twig-cache -name '*.php' >> /tmp/list

# add normal php files to the list too
find web/app -name '*.php' >> /tmp/list
find src -name '*.php' >> /tmp/list

# convert with xgettext
xgettext -f /tmp/list -o /tmp/xgettext.out -L PHP --from-code UTF-8

# merge with existing translations
msgmerge -o /tmp/merge.out src/langs/fr_FR/LC_MESSAGES/messages.po /tmp/xgettext.out

# copy to final destination
mv /tmp/merge.out src/langs/fr_FR/LC_MESSAGES/messages.po

# cleanup
rm /tmp/list
rm /tmp/xgettext.out
