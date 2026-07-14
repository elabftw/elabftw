---
sidebar_position: 3
title: Developer documentation
---

# Developer documentation

## Word of caution

It is possible that the gap between the current development version and the current stable version will render this documentation obsolete in parts. As we currently have no versioning of the doc to match stable releases of eLabFTW. I don't know, just do your best.

Another thing is that this documentation is targeted towards GNU/Linux users. If you are on Windows or MacOS, you will need to adapt some things. We currently do not provide detailed documentation for Windows or MacOS users, as we are avid open source software aficionados, and consider these operating systems as spyware.

## Introduction

Depending on your background, the eLabFTW project might seem daunting at first, because it uses a lot of different technologies: Docker, Yarn, Webpack, Composer, TypeScript, Scss, ...

But fear not, because there is a whole documentation about getting started, and you're already reading it ;)

## Note about repositories

The eLabFTW project is split in different repositories. The main one with the actual PHP code is [elabftw/elabftw](https://github.com/elabftw/elabftw>). The present document is generated from markdown files in [elabftw/documentation](https://github.com/elabftw/documentation). So if you need to change the documentation, it will be in there.

The Docker image is built from the code at [elabftw/elabimg](https://github.com/elabftw/elabimg).

Other interesting repositories are:

- [elabftw/elabctl](https://github.com/elabftw/elabctl) for the elabctl tool
- [elabftw/elabapi-python](https://github.com/elabftw/elabapi-python) for the python API library

The rest of this documentation is about elabftw/elabftw.

## Note about branches

The target for Pull Requests is the `master` branch.

Releases get tagged from a `release/X.Y` branch. And patch releases are built from cherry-picking bugfixes commits in `master`.

### Code organization

* Real accessible pages are in the web/ directory (experiments.php, database.php, login.php, etc…)
* The rest is in app/ or src/ for PHP classes
* src/models will contain classes with CRUD (Create, Read, Update, Destroy)
* src/classes will contain services or utility classes
* A new class will be loaded automagically thanks to the use of PSR-4 with composer (namespace Elabftw\\Elabftw)
* app/controllers will contain pages that send actions to models (like destroy something), and generally output json for an ajax request, or redirect the user.
* Check out the scripts in `src/tools` too

## Working with JavaScript
All JavaScript code is written in `TypeScript <https://www.typescriptlang.org/>`_ in `src/ts`. During build, it is converted in JS by `tsc`. It is then bundled by [Webpack](https://webpack.js.org/). A full build can be quite time consuming, especially on hardware with limited CPU power.

When working on some JS, what you want is to be able to save the file and immediately see the changes. For that, use `yarn watchjs` to build the JS and watch for changes. Now changes will take a very small time to compile and be visible.

You'll also want to configure your favorite text editor to display TypeScript errors when writing the code.

Use vanilla JS and ban the use of jQuery selectors or functions.

## Miscellaneous

* if you make a change to the SQL structure, you need to add a schema file in `src/sql`. See the existing files for an example. Then increment the required version in `src/classes/Update`. Modify `src/sql/structure.sql` so new installs will get the correct structure. See also `dev:genschema` command.
* comment your code wisely, what is important is the why, not the what
* your code must follow `the PSR standards <https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md>`_
* add a plugin to your editor to show trailing whitespaces in red
* add a plugin to your editor to show PSR-1 errors
* see `editorconfig.org <https://editorconfig.org/>`_ and configure your editor to follow the settings from `.editorconfig`
* remove BOM
* if you want to work on the documentation, clone the `documentation repo <https://github.com/elabftw/documentation>`_
* if you want to make backups of your dev install, you'll need to edit `elabctl.conf` to point to the correct folders/config files. See `example <https://github.com/elabftw/elabctl/blob/master/elabctl.conf>`_
* in php camelCase; in html, dash separation for CSS stuff, camelCase for JS
* check the commands in the "scripts" part of the `package.json` file, a lot of nice things in there ;)

## Glossary

* Experiments + Database items + Experiments Templates = Entities. So when you see Entity it means it can be an experiment/template or a database item.

## Build
The javascript and css files are stored unminified in the source code. But the app uses the minified versions, so if you make a change to the javascript or css files, you need to rebuild them.

* To minify files:

~~~bash
# install the packages first
yarn install
yarn buildall
~~~

Other commands exist, see `builder.js` (webpack), the `scripts` part of `package.json` (yarn). If you just want to rebuild the CSS, use `yarn buildcss`.

When working on the code, it is best to have `yarn watchjs` and `yarn watchcss` running so changes are immediately picked up.

## Tests

The tests run on the Codeception framework for unit and api tests. End to end testing is done with Cypress.

~~~bash
yarn unit # will run the unit tests
yarn test # will run the full test suite
~~~

A good contribution you can make would be adding Cypress tests.

In order for the tests to run successfully, you'll want to have a file in `tests/elabftw-user.env` with the following content:

~~~bash
ELABFTW_USER=sam
ELABFTW_GROUP=wheel
ELABFTW_USERID=1000
ELABFTW_GROUPID=1000
~~~

In the example above, the user is `sam` and the main group is `wheel`. Find out this info with `id` command. This file will make the test container run as your user and prevent permissions issues.

## Exceptions handling

Here are some ground rules for exceptions thrown in the code:

* Code should not throw a generic Exception, but one of Elabftw\Exceptions
* ImproperActionException when something forbidden happens but it's not suspicious. Error is not logged, and message is shown to user
* DatabaseErrorException when a SQL query failed, the error is logged and message is shown to user
* IllegalActionException when something should not happen in normal conditions unless someone is poking around by editing the requests. Error is logged and generic permission error is shown
* FilesystemErrorException, same as DatabaseErrorException but for file operations
* For the rest, the error is logged and a generic error message is shown to user
* Code should throw an Exception as soon as something goes wrong
* Exceptions should not be caught in the code (models), only in the controllers
* Instead of returning bool, functions should throw exception if something goes wrong. This removes the need to check for return value in consuming code (something often forgotten!)

## Making a pull request

1. Before working on a feature, it's a good idea to open an issue first to discuss its implementation
1. Create a branch from **master**
1. Work on a feature
1. Make sure `yarn full` exits with no errors
1. Make a pull request on GitHub to include it in **master**

~~~bash
cd $dev/elabftw
git checkout -b my-feature
# modify the code, commit and push to your fork
# go to github.com and create a pull request
~~~


## Adding a lang

* Add lang on poeditor.com
* Create a new folder in `src/langs`: `mkdir -p src/langs/<xx_YY>/LC_MESSAGES`
* Get the .po export from poeditor
* Save it as `messages.po` in that folder
* Open with poeditor and fix issues
* Save the .mo
* Upload .po fixed to poeditor
* Add it to `Enums/Language.php`
* Get the tinymce translation from [this repository](https://github.com/mklkj/tinymce-i18n/tree/master/langs6)
* Rename file to 4 letters code in `src/js/tinymce-langs`
* Edit first line of file to match code
* Import it in `tinymce.ts`
* Run `bin/console dev:i18n4js`
* Import it in `ts/i18n.ts`


## Adding a new term for js i18n

* Add the new term to src/Elabftw/i8n4Js.php in the `getTerms()` function returned array
* Run `bin/console dev:i18n4js`

## Accessing Docker MySQL database with phpmyadmin

You might be used to access your local MySQL dev database with PHPMyadmin. Just uncomment the part related to phpmyadmin in the config file and `elabctl restart`.

This will launch a docker container with phpmyadmin that you can reach on port 8080. Go to [localhost:8080](http://localhost:8080). Login with your mysql user (elabftw by default) and your mysql password found in the .yml configuration file. You should see the `elabftw` database now.

## Using a trusted certificate for local dev

When working locally, the docker image will generate a self-signed TLS certificate. This will show a warning in the browser address bar and multiple warnings in the console (when you press F12). To fix this, it is possible to generate certificates that are trusted by your local browser.

We'll use [FiloSottile/mkcert](https://github.com/FiloSottile/mkcert) project to achieve this.

### Step 1: use a real domain name

I like to use elab.local on port 3148. Edit `/etc/hosts` and add a line with elab.local pointing to localhost like this:

`127.0.0.1 elab.local`

### Step 2: get certs

Install [mkcert](https://github.com/FiloSottile/mkcert) and generate certificates for `elab.local`. Create a new folder somewhere to hold them:

~~~bash
mkdir -p $dev/certs/live/elab.local
mv elab.local+3.pem $dev/certs/live/elab.local/fullchain.pem
mv elab.local+3-key.pem $dev/certs/live/elab.local/privkey.pem
~~~

### Step 3: edit config to use certificates

Edit the .yml file for elabftw, change `ENABLE_LETSENCRYPT` to `true`. Uncomment the volume line with `/ssl` and make it point to where you have the certs.

Example:

~~~yaml
volumes:
    - /home/user/.dev/elabftw:/elabftw
    - /home/user/.dev/certs:/ssl
~~~

### Step 4: restart containers

`elabctl restart`, and you should now have a valid certificate on your local dev install of elabftw :)

## How to test external auth

To easily test external authentication, edit in the container `/etc/php8/php-fpm.d/elabpool.conf` and at the end add:

~~~proto
env[auth_user] = ntesla
env[auth_username] = Nicolas
env[auth_lastname] = Tesla
env[auth_email] = "nico@example.com"
env[auth_team] = "Alpha"
~~~

Restart the php process with: `s6-svc -r /var/run/s6/services/php`.

Next, configure the correct keys in the Sysconfig panel and external authentication should be working as expected.

## How to test ldap

Uncomment the ldap and ldap-admin containers definitions in the config file. Then use the ldap-admin (running on port 6443 by default) to login with "cn=admin,dc=example,dc=org" and password "admin". Then click the "dc=example,dc=org" in the left menu and "Create a child entry". Create a "Generic: Posix Group". We don't care about the name but it is necessary to have one before creating our test user.

Click again the "dc=example,dc=org" in the left to be at the root, "Create a child entry" and select "Generic: User Account". In GID Number you can assign the previously created group. Once the user is created, go select it in the left menu and "Add new attribute": Email. And add the email for that user. Now you should be able to login with that user after activating ldap from the sysconfig menu. Default values from the populate script should be good to go without changes.

## Install a pre-commit hook

It is a good idea to use a pre-commit hook to run linters before the commit is actually done. It prevents doing another commit afterwards for "fix phpcs" or "fix linting". Go into `.git/hooks`. And `cp pre-commit.sample pre-commit`. Edit it and before the last line with the "exec", add this:

~~~bash
# eLabFTW linting pre-commit hook
reset="\e[0m"
red="\e[0;31m"
set -e
if ! docker exec elabftw yarn pre-commit
then
    printf "${red}error${reset} Pre-commit script found a problem!.\n"
    exit 1
fi
~~~

Now when you commit it should run this script and prevent the commit if there are errors.

## Running cypress locally

In docker: `yarn run cy` (where `yarn` is the local command, not the one in container, because this starts docker images)

Locally: current workaround:

~~~bash
cd /tmp
git clone https://github.com/elabftw/elabftw
cd elabftw
npm i --no-save --no-lockfile cypress cypress-terminal-report
./node_modules/.bin/cypress open
# then once it's fixed
git checkout -- yarn.lock
rm -rf node_modules
~~~

Not great, not terrible.

## Debugging mysql queries

Connect as root in the MySQL container:

~~~bash
docker exec -it mysql
mysql -uroot -p$MYSQL_ROOT_PASSWORD
mysql> SET GLOBAL general_log = ON;
# check where it is saved
mysql> SHOW VARIABLES WHERE Variable_name IN ('general_log','log_output','general_log_file');
# exit mysql
tail -f /var/lib/mysql/abcd1234.log
~~~

This will log ALL queries.

## Accessibility

Tools useful to work on accessibility:

- WAVE plugin: https://addons.mozilla.org/en-US/firefox/addon/wave-accessibility-tool/
- Accessibility tab of firefox dev tools
- HeadingsMap: https://addons.mozilla.org/en-US/firefox/addon/headingsmap/
- Accessibility section of Chrome's Lighthouse
