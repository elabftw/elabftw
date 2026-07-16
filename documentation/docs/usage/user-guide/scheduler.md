---
sidebar_position: 4
title: Scheduler
---

# Scheduler

It is possible to use the scheduler (calendar) to book Resources.

Before proceeding, make sure you have a bookable resource. See [Make a resource bookable](./resources#making-a-resource-bookable) section.

Select an item by typing its name in the searchbar.
<figure>
  <img src="/img/scheduler-select-item.webp" width='500' alt="select an item" />
  <figcaption>Select an item to create an event.</figcaption>
</figure>

You can now drag-and-drop a slot, confirm the "Title" of the event and click the `Create event(s)` button. You can select multiple resources to create many events at the same time.
<figure>
  <img src="/img/scheduler-create-events.webp" width='500' alt="scheduler create events modal" />
  <figcaption>Modal to confirm creation of events.</figcaption>
</figure>

Once created, you will be able to see a list of your scheduled bookings in the main page (dashboard).
<figure>
  <img src="/img/scheduled-bookings.webp" width='500' alt="scheduled bookings in dashboard" />
  <figcaption>Scheduled bookings in the dashboard.</figcaption>
</figure>

## Events

On the scheduler page, the items listed are called Events. Clicking an existing slot displays a modal window with different information:
<figure>
  <img src="/img/scheduler-event.webp" width="300" alt="scheduler events" />
  <figcaption>Events in the scheduler.</figcaption>
</figure>

<figure>
  <img src="/img/scheduler-event-view.webp" width="700" alt="modal view of an event" />
  <figcaption>Viewing an event.</figcaption>
</figure>

The first line indicates the Title (or Comment) of the event. It is defined by the user when creating or editing the event.

Below are the date of the event, the time slot, and the duration in minutes.

### Bindings

You can bind the slot to an experiment or a resource. Start typing on the "Search" textbox and select the entry you wish to relate to this event.
<figure>
  <img src="/img/scheduler-bind-experiment.webp" width="700" alt="Bind an experiment to an event." />
  <figcaption>Binding an experiment to the event.</figcaption>
</figure>

You can then view or unbind the entry.
<figure>
  <img src="/img/scheduler-binded.webp" width="700" alt="View or unbind" />
  <figcaption>View or unbind an experiment.</figcaption>
</figure>

### Edit an event

Click the `Edit` button to edit an event. You can modify its title and starting/ending hours.

<figure>
  <img src="/img/scheduler-edit-event.webp" width="700" alt="Edit an event" />
  <figcaption>Edit an event.</figcaption>
</figure>

In the scheduler page, you can update an event with the following actions:

- Drag-and-drop: Move the event to the desired start time.
- Drag (by the end): Move only the ending hour of the event.

<figure>
  <img src="/img/scheduler-event-actions.gif" alt="Edit an event" />
  <figcaption>Different actions on an event.</figcaption>
</figure>

### Cancel an event

Click the `Cancel` button to cancel an event.

<figure>
  <img src="/img/scheduler-delete-event.webp" width="700" alt="Delete an event" />
  <figcaption>Delete an event.</figcaption>
</figure>

You can add a custom message to inform the team members who are connected to this event. You can either send to **Members of the team** or to a list of users who booked this resource in a specific time range.

### Browse events

You can use the filters to reduce clutter on the scheduler view and look for specific events.

<figure>
  <img src="/img/scheduler-filters-category.webp" alt="filtering by category" />
  <figcaption>Filtering by category.</figcaption>
</figure>

<figure>
  <img src="/img/scheduler-filters-owner.webp" alt="filtering by owner" />
  <figcaption>Filtering by owner.</figcaption>
</figure>

## Adjusting permissions

When a Resource is bookable, a new permission appears: "Can book":

<figure>
  <img src="/img/can-book-setting.webp" width='400' alt="can book settings" />
  <figcaption>Modify booking permissions.</figcaption>
</figure>

By default, it will match the `Visibility` permission of the entry, but it can be adjusted to fine-tune who exactly has access to this Resource for booking it.

## Archiving and deleting resources

Resources can be archived or deleted just like experiments. The behavior is the exact same. See [Archival](./experiments#archival) section.
