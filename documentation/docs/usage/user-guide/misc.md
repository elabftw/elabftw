---
sidebar_position: 7
title: Miscellaneous
---

# Miscellaneous

## How to group Experiments into Projects?

There are several options:

1. Use Categories for Experiments: they are defined by an Admin and are common to the Team.
2. Use Tags/favorite Tags: User or Admin defined, depending on the Team settings (by default Users can create new Tags).
3. Use a Resource of Category "Project" and the link system to link Experiments to that Project.
4. Directly link Experiments together using the link system.


First, try to go beyond the nested, tree-like structure of hierarchical folders.

Imagine you have an Experiment which is:

- about "Protein MR73"
- using "Western blot"
- an external collaboration
- with "HEK cells"

Now if that Experiment was a file, you might want to store it in "Collaborations > Western Blot > MR73" maybe. Or "Project MR73 > Collaborations > HEK"?

But what if you have another one that is also using HEK cells but has nothing in common with the previous one. How would you go about looking for all the Experiments with HEK? And all the Experiments related to MR73 that involve a Western Blot?

In a traditional folder structure, you would need to search for it in almost each sub-folders.

Enter **Tags**.

### Tags

Tags are a way to label your Experiments (and database objects) with defined keywords and you can have as many as you want!

<figure>
  <img src="/img/tags-view.png" alt="tags-view." />
  <figcaption>Tags</figcaption>
</figure>

Now with the Experiments correctly tagged, finding them through different search angles becomes easy! You can search for one Tag or many Tags directly from the main page.

### Favorite Tags

Over time, you will have some Tags that become your favorites, as they are always the ones you look for for a set of Experiments.

Since version 4.2.0 it is possible to define "Favorite Tags" that will appear in the left pane of the page listing entries. It allows quick overview of related entries. You should try this feature, start by clicking the arrow on the left of the screen to toggle the left pane. Click the + button and start typing a Tag to add it to the list of Favorite Tags.

<figure>
  <img src="/img/favtags.png" alt="favtags" />
  <figcaption>Favorite tags.</figcaption>
</figure>

Note that if you use a "Favorite Tag" filter and then create an Experiment, it will be tagged automatically with that Tag.

### Using Projects

There is also another way to group Experiments together, that you can use along with Tags. It's using a Resource of Category: Project.

Go to the Admin Panel and create a Resource Category: "Project". Go to the Resources tab and create a new "Project" entry describing a group of Experiments, a project. Go to the Experiments tab and create an Experiment. In the field "Linked Resources", type the name of the project and click on the autocompletion field appearing, and press enter (or click outside). This Experiment is now linked to the project. So you can easily go to the project description from the Experiment, but more importantly, you can from the Project entry, click the "Show related" icon (chainlink) and display all Experiments linked to this project!

Make sure to create Experiments templates that already link to that Project so the link will always be here when the Experiment is created by a User.

### Using Categories
Among a Team, Users and Admins can define several Experiments Categories. It is a quick and easy way to group Experiments together.


## Sharing with external collaborators
If you want to share your results with an external collaborator, you have a few options:

### Option 1: Export and send
This option is pretty straightforward, you export your entry into a PDF or ZIP archive and send this by email or other means to your collaborator.

If course, this option has its limitations, and is not always the best suited approach, but works 100% of the time.

### Option 2: Allow anonymous access
It is possible to allow Anonymous access to an eLabFTW installation, but this functionality is disabled by default. The Sysadmin must check this parameter from the Sysconfig Panel:

<figure>
  <img src="/img/sysconfig-anonymous.png" alt="sysconfig-anonymous" />
  <figcaption>Registration and authentication configuration (Sysadmin panel).</figcaption>
</figure>

Then, Users will have the possibility to generate a link with an access key in its URL, from the Visibility permissions window of an entry, near the bottom:

<figure>
  <img src="/img/user-anonymous-link.png" alt="user-anonymous-link" />
  <figcaption>Link generation by a user.</figcaption>
</figure>

Sharing this link will give read access to the recipient. If the checkbox is unchecked, previously shared links become obsolete. Using this has the advantage that the recipient can follow the evolution of the results over time.

For this feature to work, the instance must be accessible from an external network.

### Option 3: Share with a user on the instance
You might want to share some entries with a specific user, group, or team present on the Instance. For that, go to the Permissions section of an entry:

<figure>
  <img src="/img/user-permission-view.png" alt="user-permission-view" />
  <figcaption>Permissions for an entry.</figcaption>
</figure>

Click the + sign to display a modal window. From there you can for instance select a Team to add to the base permissions:


<figure>
  <img src="/img/user-permission-view-team.png" alt="user-permission-view-team" />
  <figcaption>Adding a full team.</figcaption>
</figure>

This will add all the members of that Team. If you want to be more precise, you can select a Group, or a single user by typing their name in the section at the bottom:

<figure>
  <img src="/img/user-permission-view-user1.png" alt="user-permission-view-user1" />
  <figcaption>Start typing the name of the collaborator.</figcaption>
</figure>

Select the autocompleted user and click "Add".

<figure>
  <img src="/img/user-permission-view-user2.png" alt="user-permission-view-user2" />
  <figcaption>Collaborator is now selected.</figcaption>
</figure>

Click "Save" and it will look similar to this now:

<figure>
  <img src="/img/user-permission-view-user3.png" alt="user-permission-view-user3" />
  <figcaption>All members of Team "External Collaborators" + Tata Rohan now have access to this entry.</figcaption>
</figure>

You can also do something similar for edition permissions.
