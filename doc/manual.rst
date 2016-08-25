.. _manual:

Manual
======

This guide assumes you already have a working installation of eLabFTW on a server.

General overview
----------------

The principles
~~~~~~~~~~~~~~
One eLabFTW installation can host several teams. So ideally it is installed at an Institution/Company level. It can also be used by only one team, or only one user.

By default, experiments and database items are restricted to a team. But users can choose to extend this to all registered users.

Experiments showed on the Experiments tab (the main tab) are yours only. To see experiments from other people in the team use the Search page or enable it from your User Control Panel.

Database items are common to the team and can be edited by anyone from the team.

A lot of flexibility in the use of the software allows for different usage by a wide variety of researchers.

Creating an account
~~~~~~~~~~~~~~~~~~~
New users need to register an account on the register page, accessible from the login page. They need to choose a team from the list.

By default, newly created accounts are disabled. The admin of the team needs to validate them by going into the admin panel and activate new users.

The Sysadmin
~~~~~~~~~~~~
* Is the first registered user
* Has access to the Sysconfig Panel with general settings impacting every team hosted on the server
* Can create/edit/delete teams
* Can send a mass email to all users
* Can set the default language
* Can configure timestamping (if another TSA is wanted)
* Can change security settings (number of login attempts, manual validation of new users)
* Can see error logs
* It is possible to have multiple 'Sysadmin' accounts

The Admin
~~~~~~~~~
* Is the first registered user in a team
* Has access to the Admin Panel with settings impacting only his team
* Can change the rightmost link in the main menu (default is Documentation)
* Can override general Timestamping settings
* Can edit users of his team
* Can edit available Status for experiments of his team
* Can edit available Items Types for the database of his team
* Can edit the default text of a new experiment
* Can import data from a CSV file in the database
* Can import elabftw.zip archives (experiments or database items)
* Can manage groups of users amongst the team (see below)
* Can promote another user to Admin or give locking powers

User groups
```````````
The Admin can create User Groups from the Admin Panel. Users can then choose to set the visibility of an experiment to this group. Only members of this group will be able to see this experiment.

Experiments
-----------
Once logged in, you can create an experiment by pressing 'c' or clicking the «Create new» button. You will be presented with an «edition» page (you can see 'mode=edit' in the URL; the two other modes being 'view' and 'show').

Templates
~~~~~~~~~
Each user can have his own Experiments templates. Go to your user panel to create/edit your templates. You can export them as .elabftw.tpl files, and import them, too! They will be accessible from the «Create new» dropdown menu.

Edit mode
~~~~~~~~~

Tags
````
The first field is the tags, that you can use to group experiments (think folders or projects). All experiments with the same tag will be accessible by clicking this tag or searching for it. To validate a tag, press Enter. It is saved immediatly. The number of tags is not limited. Click on a tag to remove it (in edit mode). Tags are common to a team. Autocompletion favors the reuse of existing tags.

Date
````
The date is today's date by default, in the format YYYYMMDD. You can edit it as you wish. The real creation date/time is stored in the database in another column.

Visibility
``````````
By default, all experiments can be viewed by other team members. If you wish to restrict viewing of a particular experiment, set this to 'Only me'. An admin can also create groups of users, and users can set the visibility of experiments to this group only.

Status
``````
This useful feature lets you set the 'status' of an experiment. By default you can have :

- Running (selected upon creation)
- Need to be redone
- Success
- Fail

These status can be modified completely by the admin in the admin panel.

Title
`````
The title of your experiment. A duplicated experiment will have a «I» character appended to the title upon creation.

Experiment (body)
`````````````````
This is where you describe your experiment and write your results. It is a rich text editor where you can have formatting, tables, colors, images, links, etc… To insert an image in this field, first upload it by dragging it in the 'Attach files' block. Then you will see a new block appear just below, with a thumbnail of the file, its name and size. Right click on the image and select «Copy link location». Next, click on the «Insert/edit image» button in the toolbar of the rich text editor (third button before the last).
Paste the link location. Press OK. That's it, you have an image inside your main text.

Make sure to right click on the thumbnail and not on the name!

Linked items
````````````
This field allows you to link an item from the database. Just begin to type the name of what you want to link and you will see an autocompletion list appear. Select the one you want and press Enter. The number of links is not limited.

This feature can also be used to link an experiment to a particular Project. If you have a «Project» Item Type and have a Project item in your database, you will then be able to see all experiments linked to this project by clicking the Link icon.

Attach a file
`````````````
You can click this region to open a file browser, or drag-and-drop a file inside. The file size limit depends on the server configuration, and there is no limit on file type. If you upload an image, a thumbnail will be created. There is no limit on the number of files you can attach to an experiment.

When you are done, click the «Save and go back» button.

You are now in view mode.

View mode of experiment
~~~~~~~~~~~~~~~~~~~~~~~
In the view mode, several actions are accessible under the date.

Edit
````
Go back to edit mode. You can also click the main text.

Duplicate
`````````
Duplicating an experiment allows you to create a new item with the same Title, tags, body and links, but with today's date and a running status. Uploaded files are not duplicated. A «I» character will be added to the title to denote that it is a replicate.

Make a pdf
``````````
Clicking this will create a pdf from your experiment. The generated pdf will contain all the information related to the experiment.

Make a zip archive
``````````````````
A zip archive will contain the generated pdf of the experiment + any attached files present.

Lock
````
Once locked, an experiment cannot be modified anymore. Unless you unlock it. If it is locked by someone with locking powers (the PI), you will not be able to unlock it.

Timestamp
`````````
An experiment can be timestamped. Once timestamped it cannot be edited anymore.

What happens when you timestamp an experiment :

- a pdf is generated
- a sha256 sum of this pdf is generated
- this data is sent to the Time Stamping Authority (TSA)
- they timestamp it
- we get a token back

More info here : https://en.wikipedia.org/wiki/Trusted_timestamping

eLabFTW uses :rfc:`3161` for timestamping. So any TSA providing a :rfc:`3161` compatible way of timestamping will work.

By default, eLabFTW is configured to use the timestamping server of `pki.dfn.de <https://www.pki.dfn.de/zeitstempeldienst/>`_. It allows you to timestamp your experiments for free if you are doing research.

You can also use a different timestamping provider. For instance `SafeCreative <https://tsa.safecreative.org/>`_ is known to work. Download their `certificate <https://tsa.safecreative.org/certificate>`_ in the elabftw folder and configure your timestamping settings to use that file. The URL is `https://tsa.safecreative.org <https://tsa.safecreative.org>`_. You are limited to 5 timestamps by day and IP address.

elabid
``````
In the bottom right part of the experiment, you can see something like : «Unique elabid : 20150526-e72646c3ecf59b4f72147a52707629150bca0f91». This number is unique to each experiment. You can use it to reference an experiment with an external database.

Comments
````````
People can leave comments on experiments. They cannot edit your experiment, but they can leave a comment. The owner of the experiment will receive an email if someone comment their experiment.

Database
--------
Same as experiments for a lot of things, except there is no status, but a rating system (little stars). You can store any type of items inside, the admin can edit the available types of items.

In view mode, click the link icon to show all experiments linked with this item.

Examples of database items types:

* antibodies
* microscopes
* plasmids
* drugs
* chemicals
* equipment
* projects

Team
----
This page presents the members and some statistics about the team. You'll also find here a molecule drawer. Note: this molecule drawer can be displayed when you create an experiment. Go to your user control panel to adjust this setting.

Scheduler
~~~~~~~~~
Since version 1.3.0, a scheduler is available to book equipment. First you need to set some item types as bookable from the Admin Panel. After you select an item from the Scheduler page, and use the calendar to book it.

Miscellaneous
-------------

You can export experiments in .zip. If the experiment was timestamped you will find in the archive the timestamped pdf and the corresponding .asn1 token.

You can export and import items from the database (it can be several items).

Press 't' to have a TODO list.

In the editor, press Ctrl+shift+d to get today's date inserted at cursor position.
