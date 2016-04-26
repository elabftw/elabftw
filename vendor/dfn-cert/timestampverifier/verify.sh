#!/bin/sh
# Runs the TimeStampVerifier
# modified for use in eLabFTW

request=$1
response=$2
chain=chain.txt
root=rootcert.crt
crl=crl.txt

class=de.dfncert.timestampverifier.TimeStampVerifier
libs=libs/bcpkix-jdk15on-152.jar:libs/bcprov-jdk15on-152.jar

java -cp $libs:. $class "$request" "$response" "$chain" "$root" "$crl"
