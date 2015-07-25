.. _contributing:

Contributing
============


What can you do to help this project ?
--------------------------------------

* Have a look at `the current issues <https://github.com/elabftw/elabftw/issues>`_
* Translate it
* Talk about it to your colleagues, spread the word!
* Open GitHub issues to discuss bugs or suggest features
* Star it on GitHub

Translating
-----------

.. image:: img/i18n.png

Do you know several languages? Are you willing to help localize eLabFTW? You're in the right place.

Languages 100% translated:

* British English : en_GB.UTF8
* Catalan : ca_ES.UTF8
* German : de_DE.UTF8
* French : fr_FR.UTF8
* Italian : it_IT.UTF8
* Spanish : es_ES.UTF8
* Brazilian Portuguese : pt_BR.UTF8
* Simplified Chinese : zh_CN.UTF8


How to translate ?

* `Join the project on poeditor.com <https://poeditor.com/join/project?hash=aeeef61cdad663825bfe49bb7cbccb30>`_
* Select elabftw
* Add a language (or select an existing one)
* Start translating
* Ignore things like `<strong>, <br>, %s, %d` and keep the ponctuation like it was!

Then email me the result, or send a pull request.


Contributing to the code
------------------------

* before doing a pull request, open an issue so we can discuss about it (unless it is obvious that your code should be merged ;).
* base your PR on the **next** branch, which is the development branch; **master** being the *release* branch.
* most of the code is procedural, but a progressive transition to object oriented code is on the way.
* classes should be in `inc/classes` with namespace Elabftw\Elabftw
* a new class will be loaded automagically thanks to the use of PSR-4 with composer
* for i18n, we use gettext
* if you change a string in gettext _('they look like this'), change it also in a .po file (generally the french one) and generate a .mo file (with poedit)
* same if you add a string shown to the user, it needs to be gettexted
* if you make a change to the SQL stucture, you need to put it in `update.php` and also `install/elabftw.sql`
* the `update.php` script is sequential. So add a block of code before the END comment. Make a check to see if you need to alter something, and if yes, do it.
* most of the functions are in `inc/functions.php`
* you can use the constant ELAB_ROOT (which ends with a /) to have a full path
* comment your code wisely
* your code must follow `the PSR standards <https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md>`_
* add a plugin to your editor to show trailing whitespaces in red
* add a plugin to your editor to show PSR-1 errors
* remove BOM

Reminders
---------

* update of SwiftMailer and mPDF is done with `composer update`
* update of the js components is done with `bower update`
* after update of tinymce, lang files need to be downloaded again, and the ones without proper name (ca instead of ca_ES) need to be edited (change first line to ca_ES)
