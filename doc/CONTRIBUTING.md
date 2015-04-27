Ok, so you know a bit of HTML/CSS/JS/PHP and want to participate building a better free and open-source lab notebook software ?

Then you are in the right place :)


# What can you do to help this project ?
* Have a look at [the current issues](https://github.com/elabftw/elabftw/issues)
* [Translate it!](https://github.com/elabftw/elabftw/wiki/Contributing#translating-i18n)
* Talk about it to your colleagues, spread the word!
* Read the code and spot places needing improvements (git grep TODO)
* Open Github issues to discuss bugs or features suggestions

# Misc
- before doing a pull request, open an issue so we can discuss about it (unless it is obvious that your code should be merged ;).
- base your PR on the ***next*** branch, which is the development branch. ***Master*** being the "release" branch.
- most of the code is procedural, but a progressive transition to object oriented code is on the way.
- classes should be in `inc/classes`
- you need to add your class to the `composer.json` file.
- for i18n, we use gettext
- if you change a string in gettext _('they look like this'), change it also in a .po file (generally the french one) and generate a .mo file (with poedit)
- same if you add a string shown to the user, it needs to be gettexted
- if you make a change to the SQL stucture, you need to put it in `update.php` and also `install/elabftw.sql`
- the `update.php` script is sequential. So add a block of code before the END comment. Make a check to see if you need to alter something,
and if yes, do it.
- most of the functions are in `inc/functions.php`
- you can use the constant ELAB_ROOT (which ends with a /) to have a full path
- comment your code wisely

# Code formatting
- your code must follow [the PSR standards](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
- add a plugin to your editor to show trailing whitespaces in red
- add a plugin to your editor to show PSR-1 errors
