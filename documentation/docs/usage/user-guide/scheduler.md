---
sidebar_position: 4
title: Scheduler
---

# Scheduler

It is possible to use the scheduler (calendar) to book Resources.

Before proceeding, make sure you have a **bookable resource**. See [Make a resource bookable](./resources.md#making-a-resource-bookable) section.

Select an item by typing its name in the search bar.
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

### Recurring events

Enable **Repeat** when creating a booking, then choose whether it repeats every period, every other period, or at a custom interval. Bookings can repeat daily, weekly, or monthly. Weekly bookings can use the booking day, two days in the week, weekdays, or a custom selection of weekdays. A recurring booking can end after a chosen number of occurrences or on a chosen date, with up to 100 reservations over a period of up to 10 years.

:::warning

A monthly recurrence is rejected if its day does not exist in one of the requested months (for example, January 31 followed by February).
:::

Every occurrence is a normal reservation. Before creating or changing a recurrence, eLabFTW checks every occurrence for resource permissions, booking limits, and conflicts. If one occurrence is invalid, none of its occurrences are changed.

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

:::tip

When editing or deleting a recurring reservation, you can choose whether the action applies:
- only to that reservation,
- to that reservation and all future reservations,
- or to the complete recurrence.

**Dragging or resizing a reservation in the calendar changes only that occurrence, not the complete recurrence.**
:::

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

## Booking requests

Some bookable resources require approval before a reservation is confirmed. Approval is configured separately for each resource by selecting individual approvers in its [booking parameters](./resources.md#requiring-approval-for-bookings).

### Submitting a booking request

When you book a resource that requires approval, your reservation is created with the **Pending approval** status instead of being approved immediately. The pending event remains visible in the calendar and blocks the selected time slot.

<figure>
  <img src="/img/booking-pending-calendar.webp" alt="Pending booking in calendar" />
  <figcaption>Reserved time slot in the calendar.</figcaption>
</figure>

### Tracking or cancelling your requests

The `My booking requests` tab lists your own requests and their approval status. You can cancel a request while it is still pending. Once an approver has made a decision, its status changes to **Approved** or **Rejected**.

<figure>
  <img src="/img/booking-my-requests.webp" alt="My booking requests" />
  <figcaption>My booking requests.</figcaption>
</figure>

Pending requests whose start time has passed are marked as overdue.

<figure>
  <img src="/img/booking-overdue.webp" alt="Booking overdue" />
  <figcaption>The status of my bookings.</figcaption>
</figure>

If you are one of the resource's designated approvers, your own booking is approved immediately and does not require another approver's decision.

:::tip

For bookings subject to approval, you cannot directly change the reserved resource or the start and end times. Cancel the booking and create another request if you need a different resource or time slot.
:::

### Reviewing booking requests

Designated approvers can review requests from the `Pending approvals` tab on the Scheduler page:

1. Open the Scheduler and select `Pending approvals`.
2. Find the request for the relevant resource and requester.
3. Choose `Approve` to confirm the reservation, or `Reject` to refuse it. You may add a comment, for example to explain a rejection.

<figure>
  <img src="/img/pending-approvals.webp" alt="Pending approvals" />
  <figcaption>Pending approvals.</figcaption>
</figure>

:::info

A decision on a **recurring booking** applies to all occurrences of that request that are still pending.
:::

### Notifications and audit trail

When a request is submitted, approvers receive a notification that a booking is waiting for review, and the requester is informed that the reservation is pending. After an approval or rejection, the requester and approvers receive a status update. In-app notifications and email delivery follow each user's notification preferences and the instance's email configuration.

Approval decisions record the approver, the date and the decision, and are also tracked in the audit log. A rejection can include an explanatory comment.

:::note

Disabling approval for a resource does not retroactively change requests that are already pending. Booking reports include approved reservations and reservations that never required approval, not pending or rejected requests.
:::

Notification preferences can be customized from the User Control Panel, where users can enable or disable in-app and email notifications for booking requests and status updates.

## Adjusting permissions

When a Resource is bookable, a new permission appears: "Can book":

<figure>
  <img src="/img/can-book-setting.webp" width='400' alt="can book settings" />
  <figcaption>Modify booking permissions.</figcaption>
</figure>

By default, it will match the `Visibility` permission of the entry, but it can be adjusted to fine-tune who exactly has access to this Resource for booking it.

## Archiving and deleting resources

Resources can be archived or deleted just like experiments. The behavior is the exact same. See [Archival](./experiments.md#archival) section.
