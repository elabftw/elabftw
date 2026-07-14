---
sidebar_position: 7
title: Creating a synthesis experiment (chemistry)
---

This tutorial will show you how to use eLabFTW for registering chemistry experiments.

:::note
There might be several ways to approach this. We're just showing you one way to do it.
:::

## Creating a template

It is a good idea to start by creating an Experiment Template for Synthesis experiments. Before that, let's create an Experiment Category: "Synthesis".

### Add a category

Go to "Experiments categories" page from the menu:

<figure>
  <img src="/img/tuto-syn-exp-cat-menu.webp" alt="Experiment categories menu" />
  <figcaption>Experiment categories menu</figcaption>
</figure>

Click the "Create" button and add a category: "Synthesis".

<figure>
  <img src="/img/tuto-syn-create-category.webp" alt="Create experiment category" />
  <figcaption>Create experiment category</figcaption>
</figure>

### Add a template

Next, go to "Experiments templates" page and create a new Experiment Template.

<figure>
  <img src="/img/tuto-syn-exp-tpl-menu.webp" alt="Experiment template menu" />
  <figcaption>Experiment template menu</figcaption>
</figure>


Name it "Basic synthesis" or something similar.

<figure>
  <img src="/img/tuto-syn-create-tpl.webp" alt="Create experiment template" />
  <figcaption>Create experiment template</figcaption>
</figure>

Set the Category to "Synthesis" by clicking the first "Not set" above the title.

<figure>
  <img src="/img/tuto-syn-change-category.webp" alt="Set category" />
  <figcaption>Set category</figcaption>
</figure>

Now use the Main Text and the Custom fields to describe what you expect to have in your experiment. You can have a table, you can have links to compounds, ask the user to log various parameters such as pH or temperature.

<figure>
  <img src="/img/tuto-syn-custom-fields.webp" alt="Custom fields" />
  <figcaption>Custom fields</figcaption>
</figure>

## Creating the experiment

Once you're satisfied with your template, create an experiment from that template. Click the "Create" button from the Experiments page, add a title and select the template:

<figure>
  <img src="/img/tuto-syn-create-from-template.webp" alt="Create experiment from template" />
  <figcaption>Create experiment from template</figcaption>
</figure>

Then fill the Custom fields and Main Text with information about the experiment.


## Using the Structure Editor

From the Tools menu, select the "Chemical Structure Editor".

This editor allows you to draw a reaction. You can then save the reaction as RXN file and attach that file to your experiment (we're working on making this step easier without having to download the file).

<figure>
  <img src="/img/tuto-syn-editor.webp" alt="Chemical structure editor" />
  <figcaption>Chemical structure editor</figcaption>
</figure>

You can also export it in PNG so you can have the image in the Main text and display it from the main page by toggling the main content:

<figure>
  <img src="/img/tuto-syn-show.webp" alt="Listing reactions" />
  <figcaption>Listing reactions</figcaption>
</figure>
