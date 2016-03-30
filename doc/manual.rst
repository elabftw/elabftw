.. _manual:

Manual
======

This guide assumes you already have a working installation of eLabFTW on a server.

The first person to register an account will be 'Sysadmin' and will have access to the 'sysconfig' page, from there you can change settings impacting every team hosted on the server.

The first person to register an account in a Team will be 'Admin' of this team and have access to the 'Admin panel' page.
An admin can deal with all aspects related to team management, change the defaults, add status, types of items, default template, etc…

1. Creating an account
----------------------

New users need to register an account on the register page, accessible from the login page. They need to choose a team from the list. By default, newly created accounts are disabled. The admin of the team needs to validate them by going into the admin panel and activate new users. Only the Sysadmin can add teams or edit team names.

2. Creating an experiment
-------------------------

Once logged in, you can create an experiment by pressing 'c' or clicking the «Create experiment» button. You will be presented with an «edition» page (you can see 'mode=edit' in the URL; the two other modes being 'view' and 'show'). You can also load a template. Go to your user panel to edit your templates. You can export them as .elabftw.tpl files, and import them, too!

2.1 Tags
~~~~~~~~

The first field is the tags, that you can use to group experiments (think folders). All experiments with the same tag will be accessible by clicking this tag. To validate a tag, press Enter. It is saved automatically. The number of tags is not limited. Click on a tag to remove it (in edit mode).

2.2 Date
~~~~~~~~

The date is today's date by default, in the format YYYYMMDD. You can edit it as you wish. The real creation date/time is stored in the database in another column.

2.3 Visibility
~~~~~~~~~~~~~~

By default, all experiments can be viewed by other team members. If you wish to restrict viewing of a particular experiment, set this to 'Only me'. An admin can also create groups of users, and users can set the visibility of experiments to this group only.

2.4 Status
~~~~~~~~~~

This useful feature lets you set the 'status' of an experiment. By default you can have :

- Running (selected upon creation)
- Need to be redone
- Success
- Fail

These status can be modified completely by the admin in the admin panel.

2.5 Title
~~~~~~~~~

The title of your experiment. A duplicated experiment will have a «I» character appended to the title upon creation.

2.6 Experiment (body)
~~~~~~~~~~~~~~~~~~~~~

This is where you describe your experiment and write your results. It is a rich text editor where you can have formatting, tables, colors, images, links, etc… To insert an image in this field, first upload it by dragging it in the 'Attach files' block. Then you will see a new block appear just below, with a thumbnail of the file, its name and size. Right click on the image and select «Copy link location». Next, click on the «Insert/edit image» button in the toolbar of the rich text editor (third button before the last).
Paste it. Press OK. That's it, you have an image inside your main text.

2.7 Linked items
~~~~~~~~~~~~~~~~

This field allows you to link an item from the database. Just begin to type the name of what you want to link and you will see an autocompletion list appear. Select the one you want and press Enter. The number of links is not limited.

2.8 Attach a file
~~~~~~~~~~~~~~~~~

You can click this region to open a file browser, or drag-and-drop a file inside. The file size limit depends on the server configuration, and there is no limit on file type. If you upload an image, a thumbnail will be created. There is no limit on the number of files you can attach to an experiment.

When you are done, click the «Save and go back» button.

You are now in view mode.

3. View mode of experiment
--------------------------

In the view mode, several actions are accessible under the date.

3.1 Edit
~~~~~~~~

Go back to edit mode. You can also click the main text.

3.2 Duplicate
~~~~~~~~~~~~~

Duplicating an experiment allows you to create a new item with the same Title, tags, body and links, but with today's date and a running status. Uploaded files are not duplicated. A «I» character will be added to the title to denote that it is a replicate.

3.3 Make a pdf
~~~~~~~~~~~~~~

Clicking this will create a pdf from your experiment. The generated pdf will contain all the information related to the experiment.

3.4 Make a zip archive
~~~~~~~~~~~~~~~~~~~~~~

A zip archive will contain the generated pdf of the experiment + any attached files present.

3.5 Lock
~~~~~~~~

Once locked, an experiment cannot be modified anymore. Unless you unlock it. If it is locked by someone with locking powers (the PI), you will not be able to unlock it. Once locked, a new action appears :

3.6 Timestamp
~~~~~~~~~~~~~

A locked experiment can be timestamped. Once timestamped it cannot be edited anymore.

What happens when you timestamp an experiment :

- a pdf is generated
- a sha256 sum of this pdf is generated
- this data is sent to the Time Stamping Authority (TSA)
- they timestamp it
- we get a token back

More info here : https://en.wikipedia.org/wiki/Trusted_timestamping

eLabFTW uses :rfc:`3161` for timestamping. So any TSA providing a :rfc:`3161` compatible way of timestamping will work.

3.8 elabid
~~~~~~~~~~

In the bottom right part of the experiment, you can see something like : «Unique elabid : 20150526-e72646c3ecf59b4f72147a52707629150bca0f91». This number is unique to each experiment. You can use it to reference an experiment with an external database.

3.8 Comments
~~~~~~~~~~~~

People can leave comments on experiments. They cannot edit your experiment, but they can leave a comment. The owner of the experiment will receive an email if someone comment their experiment.

4. Database
-----------

Same as experiments for a lot of things, except there is no status, but a rating system (little stars). You can store any type of items inside, the admin can edit the available types of items.

In view mode, click the link icon to show all experiments linked with this item.

5. Admin Panel
--------------

The admin panel allows the admin of a team to edit :

- status
- type of items
- template for experiment (what is in the body upon experiment creation)
- users
- the link in the menu
- timestamping credentials (if you don't/can't use the global ones provided by the sysadmin)
- user groups

From there, you can also import a .csv or .elabftw.zip file.

6. Sysconfig
------------

The sysconfig page is only available to the sysadmin user(s). From there, you can configure :

- the teams (add or edit)
- the default language
- activate or not the debug mode
- proxy settings
- some security configs
- email config

To configure emails, I would recommend to setup an account with Mailgun.com, they provide free SMTP access and it works very well.

7. Miscellaneous
----------------

You can export experiments in .zip. If the experiment was timestamped you will find in the archive the timestamped pdf and the corresponding .asn1 token.

You can export and import items from the database (it can be several items).

Press 't' to have a TODO list.

In the editor, press Ctrl+shift+d to get today's date inserted at cursor position.
