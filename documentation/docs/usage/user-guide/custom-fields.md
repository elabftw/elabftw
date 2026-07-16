---
sidebar_position: 4
title: Custom fields
---

# Custom fields (metadata column)

This page describes the usage of the custom fields attached to experiments or resources (and their templates).

## Description

Custom fields are a way to add inputs (text, date, dropdown menus, etc...) to your entries. Internally, it corresponds to the `metadata` column in the database and is stored in JSON.

Having these key/value elements allow you to then search for a particular value. For example, you can have a custom field: Temperature, which only allows a value of type Number, and can then do a search such as: "search all experiments where temperature is equal to 12".

## Getting started

Let's try it on an Experiment. Create a new experiment, and scroll down (in edit mode) to the "Custom fields" part.

Click "Add field", a modal window will appear.

<figure>
  <img src="/img/custom-field-builder.webp" alt="custom-field-builder" />
  <figcaption>Custom field builder.</figcaption>
</figure>

If you want your new inputs to appear in groups, you can click "Manage field groups" and the "Add group" button to add a new group of inputs. Or select an existing group from the dropdown menu.

Then you can select which type of input you want for your custom field. You are free to add as many as you want, of different types. It is most useful to define them in the Templates, so when creating an entry, all the required inputs are already present.


### Example with dropdown menu

Let's select "Dropdown menu" for our example. Enter a name for this input, optionally a description and at least 2 entries to select from.

<figure>
  <img src="/img/extra-fields-dropdown.png" alt="custom-fields-dropdown" />
  <figcaption>Custom field dropdown.</figcaption>
</figure>

Then click save and the new input (custom field) will appear right under the "Save" and "Save and go back" buttons:

<figure>
  <img src="/img/extra-fields-view.png" alt="custom-fields-view" />
  <figcaption>Custom field view.</figcaption>
</figure>

### Example with number and units

In our second example, we will add an input of type `number` with several choices for the units. This is how it looks like:


<figure>
  <img src="/img/extra-fields-number.png" alt="custom-fields-number" />
  <figcaption>Custom field number.</figcaption>
</figure>

Then click save and the generated input will accept only numbers, and a dropdown menu with a list of available units will be appended to the input:

<figure>
  <img src="/img/extra-fields-number-view.png" alt="custom-fields-number-view" />
  <figcaption>Custom field number view.</figcaption>
</figure>


## Advanced use

Keep in mind that what the builder menu will do for you is simply create some JSON code and store it in the `metadata` attribute of the entry. You are free to edit this JSON code from the editor.

<figure>
  <img src="/img/json-editor-mode.png" alt="json-editor-mode" />
  <figcaption>JSON editor.</figcaption>
</figure>

## Positions
In order to assign a particular position to the inputs, you can drag them around.

If you are using the API, use the `position` key, with a number as value. The inputs will then be ordered based on this value. Lowest value being on top. Groups are shown in the position they are defined.

## Removing an input
If you wish to remove an input, click the trash icon present in edit mode on the right side of the input block.

## Example code

This will allow you to copy/paste easily the following code block into the editor (once the editor's mode is set to "Code"):

~~~javascript
{
  "extra_fields": {
    "End date": {
      "type": "date",
      "value": "2021-06-09",
      "position": 1
    },
    "Magnification": {
      "type": "select",
      "value": "20X",
      "options": [
        "10X",
        "20X",
        "40X"
      ],
      "position": 2
    },
    "Pressure (Pa)": {
      "type": "number",
      "value": "12",
      "position": 3,
      "blank_value_on_duplicate": true
    },
    "Wavelength (nm)": {
      "type": "radio",
      "position": 4,
      "value": "405",
      "options": [
        "488",
        "405",
        "647"
      ]
    }
  }
}
~~~

Now click Save and scroll up a bit. Above the Steps you should now see four new inputs under the "Custom fields" header. When they are modified, the change is saved immediately.


<figure>
  <img src="/img/extra-fields.png" alt="custom-fields" />
  <figcaption>Custom fields.</figcaption>
</figure>


And if you're looking for all entries that have the status "Need reorder" you can do so from the search page!

## Schema description

In order to be processed by eLabFTW, the JSON contained in `metadata` must be in a particular format. It looks like this:

~~~bash
{
 "extra_fields": {
   "Some custom field name": {
     "type": "date",
     "value": "2023-06-23",
     ...
   }
 },
 "elabftw" {
   "display_main_text": false,
   "extra_fields_groups": [
     { "id": 1, "name": "Some group" },
     { "id": 2, "name": "Another group" },
   ]
 }
}
~~~

In the `extra_fields` key object, you'll find all the extra fields. The name is the object key, and then it contains properties (described below).

The `elabftw` key is a special key to hold the groups or if we want to display the main text.

The rest will be ignored by eLabFTW, so you can have other things in there, too.

## Extra fields objects

Here is a list and description of the properties that `extra_fields` objects can have:

### value (required)
The field that will hold the selected/input value. You can set a default value here or leave it empty. It is the only required attribute for an `extra_field`.

### type (optional)

#### checkbox
A box to check. A Step might be a better option here.

#### date
A date input.

#### datetime-local
A date and time input.

#### email
An email input: only a valid email address will be accepted.

#### number
A text input that only accepts a number as value.

#### radio
A radio input similar to select but all options are immediately visible.

#### select
A dropdown element with options to choose from.

#### text
The default value if omitted. Use it for a short text.

#### time
A time input.

#### url
A text input that only accepts a valid URL. In view mode, the link will be clickable. By default, the link will open in a new tab. Add `"open_in_current_tab" : true` to make it open in the current tab.

#### options (for type = select)
An array of string (`[]`) with different options for the dropdown element.

### allow_multi_values (for type = select)
A `boolean` attribute for allowing the selection of multiple values from the dropdown menu (which then becomes a multi select input).

### required
A `boolean` attribute to indicate that filling this field is required. Please note that this won't prevent a user from leaving the page even if the value is empty. It will indicate visually that a value is required but won't block the workflow.

### description
A `string` attribute that will be displayed under the name of the field.

### units (for type = number)
An array (`[]`) with different units for the units dropdown element. Requires a `unit` attribute to store the selected unit.

### unit
An attribute used to store the selected unit, will be updated with a change from the `units` generated dropdown menu.

### position
Add a number as a value to correctly order the custom fields how you want them.

### blank_value_on_duplicate
Set to `true` for the value to be blanked when the entity is duplicated.

### group_id
A number corresponding to the `id` of a group defined in the `elabftw.extra_fields_groups` object. Groups are defined as an array of objects with `id` and `name` properties.

## elabftw object

Another object, with key `elabftw` is used to define some parameters.

### extra_fields_groups
An array of objects that have an `id` and `name` and corresponds to groups.
