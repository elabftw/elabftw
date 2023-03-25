/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Action, Entity, EntityType } from './interfaces';
import { Api } from './Apiv2.class';

type ExtraFieldProperty =
  | string
  | boolean
  | number
  | Array<string>;

export function ResourceNotFoundException(message: string): void {
  this.message = message;
  this.name = 'ResourceNotFoundException';
}

export class Metadata {
  entity: Entity;
  model: EntityType;
  api: Api;
  metadataDiv: Element;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = entity.type;
    this.api = new Api();
    // this is the div that will hold all the generated fields from metadata json
    this.metadataDiv = document.getElementById('metadataDiv');
  }

  /**
   * Get the json from the metadata column
   */
  read(): Promise<Record<string, unknown>> {
    return this.api.getJson(`${this.entity.type}/${this.entity.id}`).then(json => {
      // if there are no metadata.json file available, return an empty object
      if (typeof json.metadata === 'undefined' || !json.metadata) {
        return {};
      }
      return JSON.parse(json.metadata);
    });
  }

  /**
   * Only save a single field value after a change
   */
  handleEvent(event): Promise<Response> {
    // by default the value is simply the value of the input, which is the event target
    let value = event.target.value;
    // special case for checkboxes
    if (event.target.type === 'checkbox') {
      value = event.target.checked ? 'on': 'off';
    }
    // special case for multiselect
    if (event.target.hasAttribute('multiple')) {
      // collect all the selected options, and the value will be an array
      value = [...event.target.selectedOptions].map(option => option.value);
    }
    const params = {};
    params['action'] = Action.UpdateMetadataField;
    params[event.target.dataset.field] = value;
    return this.api.patch(`${this.entity.type}/${this.entity.id}`, params);
  }

  /**
   * Save the whole json at once, coming from json editor save button
   */
  update(metadata): Promise<void> {
    return this.api.patch(`${this.entity.type}/${this.entity.id}`, {'metadata': JSON.stringify(metadata)}).then(() => this.display('edit'));
  }

  /**
   * For radio we need a special build workflow
   */
  buildRadio(name: string, description: Record<string, ExtraFieldProperty>): Element { // eslint-disable-line
    // a div to hold the different elements so we can return a single Element
    const element = document.createElement('div');
    element.dataset.purpose = 'radio-holder';

    const radioInputs = [];
    const radiosName = this.getRandomId();
    for (const option of (description.options as Array<string>)) {
      const radioInput = document.createElement('input');
      radioInput.classList.add('form-check-input');
      radioInput.type = 'radio';
      radioInput.checked = description.value === option ? true : false;
      radioInput.value = option;
      // they all need to have the same name to work together
      radioInput.name = radiosName;
      radioInput.id = this.getRandomId();
      // add a data-field attribute so we know what to update on change
      radioInput.dataset.field = name;
      radioInputs.push(radioInput);
    }

    for (const input of radioInputs) {
      const wrapperDiv = document.createElement('div');
      wrapperDiv.classList.add('form-check', 'form-check-inline');
      element.appendChild(wrapperDiv);
      wrapperDiv.appendChild(input);
      const label = document.createElement('label');
      label.htmlFor = input.id;
      label.innerText = input.value;
      label.classList.add('form-check-label');
      wrapperDiv.appendChild(label);
    }
    element.addEventListener('change', this, false);
    return element;
  }

  getRandomId(): string {
    return Math.random().toString(36).substring(2, 12);
  }

  /**
   * Generate a non editable view of the extra fields
   */
  generateElement(name: string, properties: Record<string, ExtraFieldProperty>): Element {
    const wrapperDiv = document.createElement('div');
    wrapperDiv.classList.add('d-flex');
    // name + description
    const nameWrapper = document.createElement('div');
    nameWrapper.classList.add('flex-column');

    const nameEl = document.createElement('p');
    nameEl.innerText = name;
    nameWrapper.append(nameEl);
    nameWrapper.append(this.getDescription(properties));

    let valueEl: HTMLElement;
    // checkbox is special case
    if (properties.type === 'checkbox') {
      valueEl = document.createElement('input');
      valueEl.setAttribute('type', 'checkbox');
      valueEl.classList.add('d-block');
      (valueEl as HTMLInputElement).disabled = true;
      if (properties.value === 'on') {
        (valueEl as HTMLInputElement).checked = true;
      }
    } else {
      valueEl = document.createElement('div');
      valueEl.innerText = properties.value as string;
      // the link is generated with javascript so we can still use innerText and
      // not innerHTML with manual "<a href...>" which implicates security considerations
      if (properties.type === 'url') {
        valueEl.dataset.genLink = 'true';
      }
    }
    const valueWrapper = document.createElement('div');
    // set the value on the right
    valueWrapper.classList.add('ml-auto', 'pl-5');
    valueWrapper.append(valueEl);
    wrapperDiv.append(nameWrapper);
    wrapperDiv.append(valueWrapper);
    return wrapperDiv;
  }

  /**
   * Take the json description of the field and build an input element to be injected
   */
  generateInput(name: string, description: Record<string, ExtraFieldProperty>): Element {
    // we don't know yet which kind of element it will be
    let element: HTMLInputElement|HTMLSelectElement;
    // generate a unique id for the element so we can associate the label properly
    const uniqid = this.getRandomId();

    // read the type of element
    switch (description.type) {
    case 'number':
      element = document.createElement('input');
      element.type = 'number';
      break;
    case 'select':
      element = document.createElement('select');
      if (description.allow_multi_values === true) {
        element.toggleAttribute('multiple');
      }
      // add options to select element
      for (const option of description.options as Array<string>) {
        const optionEl = document.createElement('option');
        optionEl.text = option;
        if (description.allow_multi_values === true && (description.value as Array<string>).includes(option)) {
          optionEl.setAttribute('selected', '');
        }
        element.add(optionEl);
      }
      break;
    case 'date':
      element = document.createElement('input');
      element.type = 'date';
      break;
    case 'checkbox':
      element = document.createElement('input');
      element.type = 'checkbox';
      break;
    case 'radio':
      return this.buildRadio(name, description);
    case 'url':
      element = document.createElement('input');
      element.type = 'url';
      break;
    default:
      element = document.createElement('input');
      element.type = 'text';
    }

    // add the unique id to the element
    element.id = uniqid;

    if (Object.prototype.hasOwnProperty.call(description, 'value')) {
      if (element.type === 'checkbox') {
        (element as HTMLInputElement).checked = description.value === 'on' ? true : false;
      }
      if (description.allow_multi_values !== true) {
        element.value = description.value as string;
      }
    }

    if (Object.prototype.hasOwnProperty.call(description, 'required')) {
      element.required = true;
    }

    // by default all inputs get this bootstrap class
    let cssClass = 'form-control';
    // but checkboxes/radios need a different one
    if (description.type === 'checkbox') {
      cssClass = 'form-check-input';
    }
    element.classList.add(cssClass);


    // add a data-field attribute so we know what to update on change
    element.dataset.field = name;
    // add an onChange listener to the element
    // so the json can be updated without having to click save
    // set the callback to the whole class so handleEvent is called and 'this' refers to the class
    // not the event in the function called
    element.addEventListener('change', this, false);
    return element;
  }

  /**
   * Main public function to call to display the metadata in view or edit mode
   */
  display(mode: string): Promise<void> {
    let displayFunction = this.view;
    if (mode === 'edit') {
      displayFunction = this.edit;
    }

    // clear previous content
    this.metadataDiv.textContent = '';
    return displayFunction.call(this).catch(e => {
      if (e instanceof ResourceNotFoundException) {
        // no metadata is associated but it's okay, it's not an error
        return;
      }
      // if there was an issue fetching metadata, log the error
      console.error(e);
    });
  }

  /**
   * In view mode, display the extra fields, currently only used with toggle body function
   */
  view(): Promise<void> {
    return this.read().then(json => {
      // do nothing more if there is no extra_fields in our json
      if (!Object.prototype.hasOwnProperty.call(json, 'extra_fields')) {
        return;
      }
      // the input elements that will be created from the extra fields
      const elements = [];
      for (const [name, properties] of Object.entries(json.extra_fields)) {
        elements.push({
          name: name,
          description: properties.description,
          element: this.generateElement(name, properties),
          position: parseInt(properties.position, 10) || 99999,
        });
      }
      // now display the names/values from extra_fields
      for (const element of elements.sort((a, b) => a.position - b.position)) {
        this.metadataDiv.append(element.element);
        this.metadataDiv.append(document.createElement('hr'));
      }
    });
  }

  // build a description element
  getDescription(properties: Record<string, ExtraFieldProperty>): HTMLSpanElement {
    const descriptionWrapper = document.createElement('div');
    if (properties.description) {
      const descriptionEl = document.createElement('p');
      descriptionEl.classList.add('smallgray');
      descriptionEl.innerText = properties.description as string;
      descriptionWrapper.append(descriptionEl);
    }
    return descriptionWrapper;
  }

  /**
   * Get the metadata json and add input elements to DOM
   */
  edit(): Promise<void> {
    return this.read().then(json => {
      // do nothing more if there is no extra_fields in our json
      if (!Object.prototype.hasOwnProperty.call(json, 'extra_fields')) {
        return;
      }
      // the input elements that will be created from the extra fields
      const elements = [];
      for (const [name, properties] of Object.entries(json.extra_fields)) {
        elements.push({
          name: name,
          description: properties.description,
          element: this.generateInput(name, properties),
          position: parseInt(properties.position, 10) || 99999,
        });
      }
      const wrapperUl = document.createElement('ul');
      wrapperUl.classList.add('list-group');

      // now display the inputs from extra_fields
      for (const element of elements.sort((a, b) => a.position - b.position)) {
        const listItem = document.createElement('li');
        listItem.classList.add('list-group-item');
        const label = document.createElement('label');
        label.htmlFor = element.element.id;
        label.innerText = element.name as string;

        // for checkboxes the label comes second
        if (element.element.type === 'checkbox') {
          label.classList.add('form-check-label');
          const wrapperDiv = document.createElement('div');
          wrapperDiv.classList.add('form-check');
          listItem.append(wrapperDiv);
          wrapperDiv.append(element.element);
          wrapperDiv.append(label);
          wrapperDiv.append(this.getDescription(element));
        } else {
          listItem.append(label);
          listItem.append(this.getDescription(element));
          listItem.append(element.element);
        }

        wrapperUl.append(listItem);
      }

      this.metadataDiv.append(wrapperUl);
    });
  }
}
