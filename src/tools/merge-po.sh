#!/bin/bash
# https://www.elabftw.net

# call this from inside the docker dev container
# generate the .po file for french lang
# it can then be imported in poeditor for the other languages

set -eu

apk add --update gettext
# first generate all the cache files of twig templates
./bin/console dev:gencache

# add it to the list
find cache/twig -name '*.php' >> /tmp/list

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
