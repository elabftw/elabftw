#!/bin/bash
find app -name '*.php' >> /tmp/list
find . -maxdepth 1 -name '*.php' >> /tmp/list
xgettext -f /tmp/list -o /tmp/xgettext.out -L PHP --from-code UTF-8
msgmerge -o /tmp/merge.out locale/fr_FR/LC_MESSAGES/messages.po /tmp/xgettext.out
mv /tmp/merge.out locale/fr_FR/LC_MESSAGES/messages.po
rm /tmp/list
rm /tmp/xgettext.out
