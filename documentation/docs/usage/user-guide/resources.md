---
sidebar_position: 3
title: Resources
---

# Resources

Resources are similar to Experiments, but serve a different purpose: listing and organizing *things* that are used in Experiments.

Many of the options on the Resource entry are the same as those for an Experiment entry.
<figure>
  <img src="/img/resources-menu.webp" width="550" alt="Resources menu" />
  <figcaption>Resources dropdown menu.</figcaption>
</figure>

## Resource Categories

Resource Categories can be defined at the team level from the "Resource Categories" page available in the Resources dropdown menu. You can, for example, define categories such as:

* Antibodies
* Microscopes
* Plasmids
* Drugs
* Chemicals
* Equipment
* Projects

Once you have Resource Categories (which are composed of a name and a color), you can create a Resource Template with that category assigned. This allows you to have several Resource Templates for a given category.

For instance, in the Resource Category "Antibodies", you might want a Resource Template for "Primary Antibody" and one for "Secondary Antibody". It's also fine to keep a one-to-one relationship between Resource Templates and Resource Categories.

Resources default permissions allow anyone from the Team to edit them, but you are free to configure them differently from their Template.

## Imports

Look at the [Importing data](../import-export#importing-data) section to learn how to import your Resources from a spreadsheet file or through the API.

## Links
Once you have your Resources present, you can mention them in your Experiments by typing `#` and their title, and selecting the proposed autocompletion, or use directly the Link system to link them to an Experiment.
See [Links](./experiments#linked-resourcesexperiments) section.

Furthermore, Resources can be made bookable, see section below.


## Booking Resources

It is possible to use the scheduler (calendar) present on the Scheduler page to book Resources.
<figure>
  <img src="/img/scheduler.webp" width="550" alt="Scheduler page" />
  <figcaption>Scheduler page.</figcaption>
</figure>

### Making a Resource bookable

Resources are not bookable by default. To enable bookings for a resource, open the resource's page and click the `Modify booking parameters` button in the toolbar.

<figure>
  <img src="/img/resource-activate-booking.webp" width="500" alt="activate booking parameters" />
  <figcaption>Toolbar: Modify booking parameters.</figcaption>
</figure>

Once this parameter has been activated, clicking this same button will redirect you to the scheduler with the resource selected.
If you wish to edit parameters later on, click on the top right menu with three vertical dots:

<figure>
  <img src="/img/modify-booking-menu.webp" width="300" alt="modify booking menu" />
  <figcaption>Modify booking menu.</figcaption>
</figure>

A modal with various settings opens:

<figure>
  <img src="/img/modify-booking-modal.webp" width="600" alt="modify-booking-modal" />
  <figcaption>Modify booking modal.</figcaption>
</figure>

Once all is set, Users can click the `Book item` button in the toolbar, or select it from the Scheduler page, and click the calendar to drag a booking slot.
<figure>
  <img src="/img/book-item-button.webp" width='300' alt="book-item-button" />
  <figcaption>Booking in item button.</figcaption>
</figure>

The scheduler page loads with the selected item:
<figure>
  <img src="/img/scheduler-selected-item.webp" width='300' alt="scheduler selected item" />
  <figcaption>Selected item in the scheduler page.</figcaption>
</figure>

You can now move on to the [Scheduler](./scheduler) section to see how to work with your events.

## Making a Resource procurable

Resources are not procurable by default. To enable procurement for a resource, you must configure its procurement parameters.

Open the resource you want to make procurable and click the Modify procurement parameters button in the toolbar.

<figure>
  <img src="/img/resources-make-procurable.webp" width="650" alt="Make a resource procurable" />
  <figcaption>Make a resource procurable.</figcaption>
</figure>

In the dialog that opens, enable the procurement option and configure the available procurement settings as needed. Once saved, the resource will be available in the procurement system.

Once procurement is enabled, clicking the same button again will open the procurement modal, allowing you to place an order for the item.

<figure>
  <img src="/img/resources-proc-request.webp" width="450" alt="Place an order" />
  <figcaption>Place an order for your item.</figcaption>
</figure>

After an order is submitted, a confirmation message is displayed on the resource page to inform the requester that the procurement request has been recorded.

<figure>
    <img src="/img/resources-proc-made.webp" width="900" alt="Procurement request confirmation message" />
    <figcaption>A confirmation message is displayed after placing a procurement request.</figcaption>
</figure>

Procurement requests can then be managed from the Team page under the **Procurement requests** tab, where team members can review pending requests, track quantities, and update their status.

<figure>
    <img src="/img/resources-proc-team-page.webp" width="900" alt="Procurement requests management page" />
    <figcaption>Manage procurement requests from the Team page.</figcaption>
</figure>

<figure>
    <img src="/img/resources-proc-change-state.webp" width="300" alt="Change state of procurement request" />
    <figcaption>Change state of the procurement request.</figcaption>
</figure>
