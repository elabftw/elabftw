#!/bin/bash
# generate the .po file

# first generate the cache files of twig templates
php app/locale/genCache.php
# add it to the list
find /tmp/elabftw-twig-cache -name '*.php' >> /tmp/list

# add normal php files to the list too
find app -name '*.php' >> /tmp/list
find . -maxdepth 1 -name '*.php' >> /tmp/list

# convert with xgettext
xgettext -f /tmp/list -o /tmp/xgettext.out -L PHP --from-code UTF-8

# merge with existing translations
msgmerge -o /tmp/merge.out app/locale/fr_FR/LC_MESSAGES/messages.po /tmp/xgettext.out

# copy to final destination
mv /tmp/merge.out app/locale/fr_FR/LC_MESSAGES/messages.po

# cleanup
rm /tmp/list
rm /tmp/xgettext.out
