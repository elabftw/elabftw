.. _postinstall:

Post install
============

Setting up email
----------------

If there is only one thing to do after an install, it's setting up email. Otherwise users will not be able to reset their password!

If a mail server is present, it will work out of the box. However, it is recommended to use an authenticated SMTP account to avoid the emails going to the spam folders of recipients. That is, unless your mail server is perfectly configured (with DKIM and SPF).

Go to the Sysadmin panel (`sysconfig.php`) and add the requested infos.

If you don't know what to do, register a new account on `Mailgun <http://www.mailgun.com>`_. This service will give you a free SMTP account that you can use for eLabFTW. :)

Make sure that the `uploads` folder cannot be accessed
------------------------------------------------------

Just visit `/uploads/` (add this at the end of the URL) and see if you see the files there (or at least the `tmp` folder). If you see a white page, all is good. If not, either configure Apache to AllowOverride All (so the .htaccess will work), or put an empty index.php file in the folder.

Set up backup
-------------

See the :ref:`backup <backup>` page.

Set up the teams :sup:`(optionnal)`
-----------------------------------

The Sysadmin panel (`sysconfig.php`) allows you to add another team to your install. You should also edit your team name.

Set up timestamping :sup:`(optionnal)`
--------------------------------------

eLabFTW provides an easy way to do `Trusted Timestamping <https://en.wikipedia.org/wiki/Trusted_timestamping>`_ for your experiments, so you can have strong legal value for your lab notebook.

By default, it is setup to use `pki.dfn.de <https://www.pki.dfn.de/zeitstempeldienst/>`_ as :abbr:`TSA (TimeStampingAuthority)`. It is free for researchers. The only problem, is that they don't have ETSI certification for this service (although their PKI infrastructure is certified ETSI TS 102 042).

So if you need a stronger certification, you should go with a commercial solution providing an :rfc:`3161` way of timestamping documents. We recommend Universign.eu, as they are one of the most serious and recognized :abbr:`TSA (TimeStampingAuthority)` out there, but feel free to use the one you prefer.

Remember: no data is sent to the `TSA (TimeStampingAuthority)`, only the hash of the data is sent, so no information can leak!

Update often
------------

It is important to keep your install up to date with the latest bug fixes and new features.

`Subscribe to the newsletter <http://eepurl.com/bTjcMj>`_ to be warned when a new release is out.

See instructions on updating eLabFTW on :ref:`how-to-update`.

