.. _faq:

Frequently asked questions
==========================

Is it totally free ?
--------------------

YES. eLabFTW is free software, so it is totally free of charge and always will be. `Read more about the free software philosophy <https://www.gnu.org/philosophy/free-sw.html>`_.

But how is it better than something I can buy ?
-----------------------------------------------

The difference is huge. Because eLabFTW is not only free (as in beer), but it is free (as in speech). This means that you can have a look at the code (and improve it) and you can also redistribute the code with your improvements.

But more importantly, you cannot trust your data with something that acts like a **black box**. What if the data you upload on the server of a company can be read by someone else ? With eLabFTW, you install it on your own server, and you are the master of your data at all time.

What about patents and intellectual property ?
----------------------------------------------

eLabFTW allows rock solid `timestamping of your experiments <https://en.wikipedia.org/wiki/Trusted_timestamping#Trusted_.28digital.29_timestamping>`_, you can timestamp a pdf export with any TimeStamping Authority allowing :rfc:`3161` timestamping.

The timestamp system is integrated to eLabFTW and you are just one click away from having a legally certified experiment :)

Why use eLabFTW ?
-----------------

* **It's free and open source software**
* It improves the value of your experiments by allowing you to keep a good track of it
* It makes searching your data as easy as a google search
* Everything can be organized in your lab
* It makes it easy to share information between co-workers or collaborators
* It is simple to install and to keep up to date
* **It works for Windows, Mac OS X, Linux, BSD, Solaris, _etc…**
* Protected access with login/password (password is very securely stored as salted SHA-512 sum)
* It can be used by multiple users at the same time
* **It can be used by multiple teams**
* You can have templates for experiments you do often
* **You can export an experiment as a PDF**
* **You can timestamp an experiment so it is legally strong**
* You can export one or several experiments as a ZIP archive
* You can duplicate experiments in one click
* There is advanced search capabilities
* The tagging system allows you to keep track of family of experiments
* Experiments can have color coded statuses (that you can edit at will)
* You can link an experiment with an item from the database to retrieve in a click the plasmid/sirna/antibody/chemical you used
* And it works the other way around, you can find all experiments done with a particular item from the database !
* There is a locking mechanism preventing further edition
* You can prevent users from deleting experiments
* You can comment on an experiment (if it's not your experiment)
* You can import your old database stored in an excel file
* :doc:`and much more… <features>`


Is this system stable ? Can I trust my data with it ?
-----------------------------------------------------

New features are added very often and they don't break what exists already. However, having an automated :ref:`backup <backup>` strategy is mandatory in order to be sure **nothing will be lost**.

Being able to do backups is yet another advantage over paper (you can't backup paper !).

Is the data encrypted ?
-----------------------

The MySQL database can be installed on a encrypted drive. And backup can be encrypted, too. But this has to be done by your system admininstrator.

Also, as you need an account to access the data, no external individual can see your data !

Finally, eLabFTW works fully in HTTPS for added security.

Is eLabFTW still maintained ?
-----------------------------

As of |today| I'm still actively working on it. Improvements are coming in a steady flow. There are good chances that I will continue to do so for a few years. In the unlikely event I'm not able to work on it anymore, anyone can continue the work, as the source code is available and well commented.

Will I be able to import my plasmids/antibodies/whatever in the database from a Excel file ?
--------------------------------------------------------------------------------------------

Yes, in the admin panel, click on the Import CSV link and follow the instructions.

Can I try it before I install it ?
----------------------------------

Sure, there is a demo online here : `eLabFTW live DEMO <https://demo.elabftw.net>`_

But what about the others ELN out there ?
-----------------------------------------

First I'll only speak about the others free and open source lab notebook. Because there is no point in comparing free and privative software, as it's not the same philosophy at all !

* Labtrove : labtrove is a glorified Wordpress plugin. Unfortunately, I didn't manage to install it so I can't really talk about it... Also, it wasn't updated since 2011 so it probably won't work with latest versions of Wordpress.

* Indigo : It's for chemists only and it's in Java :/

* Electronic laboratory notebook on sourceforge : Looks like it was made in 1999, poorly written, only client (no server), java.

* MediaWiki : although it's very nice to have a wiki in your team, this is not designed to be an electronic lab notebook. So you should definitely have a wiki, but don't use it to store your experiments !

* Mylabbook : based on Drupal. So you need to have all the Drupal stuff around. I used Drupal in the past, it's way too complicated !

* Labbook : A perl based software, still stuck in 1999 and virtually impossible to install/use.

* Cynote : I cannot manage to download it !

What about compliance to standards ?
------------------------------------
eLabFTW tries to comply to the following standards :

* `Code of Federal Regulations Title 21, paragraph 11 <http://www.accessdata.fda.gov/scripts/cdrh/cfdocs/cfcfr/CFRSearch.cfm?CFRPart=11>`_
* `FERPA <http://www2.ed.gov/policy/gen/guid/fpco/ferpa/index.html>`_
* `HIPAA <http://www.hhs.gov/ocr/privacy/>`_
* `FISMA <https://en.wikipedia.org/wiki/Federal_Information_Security_Management_Act_of_2002#Compliance_framework_defined_by_FISMA_and_supporting_standards>`_

What are the technical specifications ?
----------------------------------------

eLabFTW is a server software that should be installed on a server.

**Requirements for the server :**

* PHP version > 5.5
* MySQL version > 5.5
* HTTPS enabled
* PHP extensions openssl, gd, hash and zip activated

The operating system of the server can be any, but GNU/Linux or FreeBSD is recommended for obvious reasons.
The server software can be any (nginx, apache, cherokee, lighttpd, …).

**Requirements for the client :**
- Any operating system with any browser (latest version). Internet Explorer/Edge is not recommended for obvious reasons.
