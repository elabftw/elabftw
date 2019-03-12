#!/bin/bash
# test api, only for local use
set -eu

# read/write access token for the phpunit user
token="a76b51a2e0d007150093c858418d5a2add9827f359b81a01fd29aea290b8c483401160146289bbfc730e"
endpoint="https://elab.local/api/v1/"
expid="1"
itemid="1"
curloptions="-ks -o /dev/null -w %{http_code}"

testfile="/tmp/elabftw-curl-test.txt"

GREEN='\033[30;42m'
RED='\033[43;91m'
NC='\033[0m' # No Color

function ascii()
{
    echo "*****************************************"
    echo "Starting elabftw REST API automated tests"
    echo "*****************************************"
}

# swap the config files back
function cleanup {
    sudo cp config.php.docker config.php
    sudo chown 100:101 config.php
}

function init_db()
{
    # swap config file for docker with the one for localhost
    # sudo is needed because config file for docker is owned by 100:101
    sudo cp config.php config.php.docker
    sudo cp tests/config-home.php config.php
    sudo chmod +r config.php
    mysql -uroot -e 'DROP DATABASE IF EXISTS phpunit; CREATE DATABASE phpunit;'
    mysql -uroot phpunit < src/sql/structure.sql
    mysql -uroot phpunit < tests/_data/phpunit.sql
}

# do HTTP request with curl, add token
# $1 is GET or POST
# $2 is URL
function req()
{
    curl $curloptions -H "Authorization: $token" -X ${1} ${endpoint}${2}
}

# $1 is the result
# $2 is the expected result
# $3 is the test name
function assert_return_code()
{
    if [ "$1" != "$2" ]; then
        echo -e "${RED}$1\tKO\t$3${NC}"
        exit 1
    else
        echo -e "${GREEN}$1\tOK\t$3${NC}"
    fi
}

trap cleanup EXIT
ascii
init_db

# test no token sent (error 401)
res=$(curl $curloptions -X GET ${endpoint}experiments/${expid})
assert_return_code $res "401" "Test no token sent"

# test invalid token (error 400)
res=$(curl $curloptions -H "Authorization: invalid" -X GET ${endpoint}experiments/${expid})
assert_return_code $res "400" "Test invalid token"

# get all experiments
res=$(req GET "experiments")
assert_return_code $res "200" "Get all experiments"

# get experiment
res=$(req GET "experiments/${expid}")
assert_return_code $res "200" "Get an experiment"

# get all items
res=$(req GET "items")
assert_return_code $res "200" "Get all items"

# get item
res=$(req GET "items/${itemid}")
assert_return_code $res "200" "Get an item"

# test invalid method (error 405)
res=$(req DELETE "experiments/${expid}")
assert_return_code $res "405" "Test invalid method"

# file upload
echo 'blah' > $testfile
res=$(curl $curloptions -H "Authorization: $token" -X POST -F "file=@${testfile}" ${endpoint}experiments/${expid})
rm $testfile
assert_return_code $res "200" "Test upload file"

# get upload
res=$(req GET "uploads/1")
assert_return_code $res "200" "Get a file"

# update title date body
res=$(curl $curloptions -H "Authorization: $token" -X POST -d "title=newtitle&date=20181010&body=blah" ${endpoint}experiments/${expid})
assert_return_code $res "200" "Test update experiment"

# add tag
res=$(curl $curloptions -H "Authorization: $token" -X POST -d "tag=blah" ${endpoint}experiments/${expid})
assert_return_code $res "200" "Test add tag"

# add link
res=$(curl $curloptions -H "Authorization: $token" -X POST -d "link=${itemid}" ${endpoint}experiments/${expid})
assert_return_code $res "200" "Test add link"

# create experiment
res=$(req POST "experiments")
# TODO
#assert_return_code $res "200" "Test create experiment"

echo -e "${GREEN}All tests passed :)${NC}"
exit 0
