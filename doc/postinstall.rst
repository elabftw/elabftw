.. _postinstall:

Post install things to do
=========================

Setting up email
----------------

By default, eLabFTW will try to use the local MTA aka Sendmail. However, it is recommended to use an authenticated SMTP account to avoid the emails going to the spam folders of recipients. That is, unless your MTA is perfectly configured (with DKIM and SPF).

Go to the Sysadmin panel (`elabftw/sysconfig.php`) and add the requested infos.

If you don't know what to do, register a new account on `Mandrill <http://www.mandrill.com>`_ or `Mailgun <http://www.mailgun.com>`_. These services will give you a free SMTP account. :)

Setting up the teams
--------------------

The Sysadmin panel (`elabftw/sysconfig.php`) allows you to add another team to your install.

Setting up timestamping
-----------------------

eLabFTW provides an easy way to do `Trusted Timestamping <https://en.wikipedia.org/wiki/Trusted_timestamping>`_ for your experiments, so you can have strong legal value for your lab notebook.

By default, it is setup to use `pki.dfn.de <https://www.pki.dfn.de/zeitstempeldienst/>`_ as :abbr:`TSA (TimeStampingAuthority)`. It is free for researchers. The only problem, is that they don't have ETSI certification for this service (although their PKI infrastructure is certified ETSI TS 102 042).

So if you need a stronger certification, you should go with a commercial solution providing an RFC 3161 way of timestamping documents. We recommend Universign.eu, as they are one of the most serious and recognized :abbr:`TSA (TimeStampingAuthority)` out there, but feel free to use the one you prefer.

Ok, so how do I setup timestamping if I don't want to use the default setup ?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

1. Create an account on the :abbr:`TSA (TimeStampingAuthority)` website (for Universign it's `this page <https://www.universign.eu/en/signup/>`_)
2. Go to the sysadmin or admin page of elabftw (sysadmin if you want to use one account for all the teams, or admin panel if you want to set it up just for your team)
3. The URL is : https://ws.universign.eu/tsa
4. The chain of certificate is : vendor/universign-tsa-root.pem (eLabFTW already provides the universign certificate in the right format for ease of use)
5. Login and passwords are the ones you provided on step 1.

Remember: no data is sent to the TSA, only the hash of the data is sent, so no information can leak!


Using other languages
---------------------

eLabFTW uses `gettext <https://en.wikipedia.org/wiki/Gettext>` to translate text. This means that you need to have the associated locales on the server.
To see what locale you have::

    locale -a

To add a locale, edit the file `/etc/locale.gen` and uncomment (remove the #) the locales you want. If you don't find this file you can try directly the command::

    locale-gen fr_FR.UTF-8

Replace with the locale you want, of course.
See :doc:`here <contributing>` to see a list of languages (and locales) supported by eLabFTW.
Then do::

    sudo locale-gen

And reload the webserver.

Setting up backup
-----------------

See the :ref:`backup` page.

Allowing more memory to php
---------------------------

By default the amount of memory a script (page loading) can take is quite low compared to how much memory any modern computer has. It is a good idea to increase this value in order to prevent future errors (when you try to export a lot of experiments for instance).

Edit the file `/etc/php/php.ini` or any file called php.ini somewhere on your filesystem. Try `sudo updatedb;locate php.ini`. For XAMPP install, it is in the config folder of XAMPP.
Now that you have located the file and opened it in a text editor, search for `memory_limit` and increase it to what you wish. `Official documentation on memory_limit <http://php.net/manual/en/ini.core.php#ini.memory-limit>`_.

Making sure that the `uploads` folder cannot be accessed
--------------------------------------------------------

Just visit `/uploads/` (add this at the end of the URL) and see if you see the files there (or at least the `tmp` folder). If you see a white page, all is good. If not, either configure Apache to AllowOverride All (so the .htaccess will work), or put an empty index.php file in the folder.

Updating
--------

It is important to keep your install up to date with the latest bug fixes and new features.

See instructions on updating eLabFTW on :ref:`how-to-update`.

Watch the repository to receive an email on new updates.



.. blah







