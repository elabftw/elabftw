#!/bin/bash
# https://www.elabftw.net

# call this from inside the docker dev container
# generate the .po file for french lang
# it can then be imported in poeditor for the other languages


gettext_installed=$(apk list --installed gettext)
if [ -z "$gettext_installed" ]; then
    apk add --update gettext
fi

set -eu

# first generate all the cache files of twig templates
./bin/console dev:gencache

# add it to the list with the normal php files too
find cache/twig  web src -name '*.php' >> /tmp/list

# convert with xgettext
xgettext -f /tmp/list -o /tmp/xgettext.out -L PHP --from-code UTF-8

# merge with existing translations
msgmerge -o /tmp/merge.out src/langs/fr_FR/LC_MESSAGES/messages.po /tmp/xgettext.out

# this will only work on my computer but I doubt other people are using this script anyway...
chown 1000:998 /tmp/merge.out
# copy to final destination
mv /tmp/merge.out src/langs/fr_FR/LC_MESSAGES/messages.po

# cleanup
rm /tmp/list /tmp/xgettext.out
