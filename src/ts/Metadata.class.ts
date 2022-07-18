/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Action, Entity, EntityType, Target, ResponseMsg, PartialEntity } from './interfaces';
import { Ajax } from './Ajax.class';
import i18next from 'i18next';


export function ResourceNotFoundException(message: string): void {
  this.message = message;
  this.name = 'ResourceNotFoundException';
}

export class Metadata {
  entity: Entity;
  model: EntityType;
  sender: Ajax;
  metadataDiv: Element;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = entity.type,
    this.sender = new Ajax();
    // this is the div that will hold all the generated fields from metadata json
    this.metadataDiv = document.getElementById('metadataDiv');
  }

  /**
   * Get the json from the metadata column
   */
  read(): Promise<Record<string, unknown>> {
    const payload: Payload = {
      method: Method.GET,
      action: Action.Read,
      model: this.model,
      entity: this.entity,
      target: Target.Metadata,
    };
    return this.sender.send(payload).then(json => {
      // if there are no metadata.json file available, return an empty object
      const fulljson = (json.value as PartialEntity);
      if (!fulljson.metadata) {
        return {};
      }
      return JSON.parse(fulljson.metadata);
    });
  }

  /**
   * Only save a single field value after a change
   */
  handleEvent(event): Promise<ResponseMsg> {
    let value = event.target.value;
    // special case for checkboxes
    if (event.target.type === 'checkbox') {
      value = event.target.checked ? 'on': 'off';
    }
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      entity: this.entity,
      target: Target.MetadataField,
      content: value,
      extraParams: {
        jsonField: event.target.dataset.field,
      },
      notif: true,
    };
    return this.sender.send(payload);
  }

  /**
   * Save the whole json at once, coming from json editor save button
   */
  update(metadata): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      entity: this.entity,
      target: Target.Metadata,
      content: metadata,
      notif: true,
    };
    return this.sender.send(payload);
  }


  /**
   * For radio we need a special build workflow
   */
  buildRadio(name: string, description: Record<string, any>): Element {
    // a div to hold the different elements so we can return a single Element
    const element = document.createElement('div');
    element.dataset.purpose = 'radio-holder';

    const radioInputs = [];
    const radiosName = this.getRandomId();
    for (const option of description.options) {
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
    return Math.random().toString(36).substr(2, 12);
  }

  /**
   * Generate a non editable view of the extra fields
   */
  generateElement(name: string, description: Record<string, any>): Element {
    const element = document.createElement('div');
    const valueEl = document.createElement('span');
    valueEl.innerText = description.value;
    // the link is generated with javascript so we can still use innerText and
    // not innerHTML with manual "<a href...>" which implicates security considerations
    if (description.type === 'url') {
      valueEl.dataset.genLink = 'true';
    }
    element.innerText = name + ': ';
    element.append(valueEl);
    return element;
  }

  /**
   * Take the json description of the field and build an input element to be injected
   */
  generateInput(name: string, description: Record<string, any>): Element {
    // we don't know yet which kind of element it will be
    let element;
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
      // add options to select element
      for (const option of description.options) {
        const optionEl = document.createElement('option');
        optionEl.text = option;
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
      break;
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
        element.checked = description.value === 'on' ? true : false;
      }
      element.value = description.value;
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
   * Create the "Extra fields" header
   */
  getHeaderDiv(): Element {
    const headerDiv = document.createElement('div');
    headerDiv.classList.add('row');
    const header = document.createElement('h4');
    header.innerText = i18next.t('extra-fields');
    headerDiv.append(header);
    return headerDiv;
  }

  /**
   * Main public function to call to display the metadata in view or edit mode
   */
  display(mode: string): Promise<void> {
    let displayFunction = this.view;
    if (mode === 'edit') {
      displayFunction = this.edit;
    }

    return displayFunction.call(this).catch(e => {
      if (e instanceof ResourceNotFoundException) {
        // no metadata is associated but it's okay, it's not an error
        return;
      }
      // if there was an issue fetching metadata, log the error
      console.error(e);
      return;
    });
  }

  /**
   * In view mode, display the extra fields
   */
  view(): Promise<void> {
    return this.read().then(json => {
      // do nothing more if there is no extra_fields in our json
      if (!Object.prototype.hasOwnProperty.call(json, 'extra_fields')) {
        return;
      }
      this.metadataDiv.append(this.getHeaderDiv());
      // the input elements that will be created from the extra fields
      const elements = [];
      for (const [name, description] of Object.entries(json.extra_fields)) {
        elements.push({
          name: name,
          element: this.generateElement(name, description),
          position: parseInt(description.position, 10) || 99999,
        });
      }
      // now display the names/values from extra_fields
      for (const element of elements.sort((a, b) => a.position - b.position)) {
        const rowDiv = document.createElement('div');
        rowDiv.classList.add('row');
        this.metadataDiv.append(rowDiv);
        rowDiv.append(element.element);
      }
    });
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
      this.metadataDiv.append(this.getHeaderDiv());
      // the input elements that will be created from the extra fields
      const elements = [];
      for (const [name, description] of Object.entries(json.extra_fields)) {
        elements.push({
          name: name,
          element: this.generateInput(name, description),
          position: parseInt(description.position, 10) || 99999,
        });
      }
      // now display the inputs from extra_fields
      for (const element of elements.sort((a, b) => a.position - b.position)) {
        const rowDiv = document.createElement('div');
        rowDiv.classList.add('row');
        this.metadataDiv.append(rowDiv);
        const label = document.createElement('label');
        label.htmlFor = element.element.id;
        label.innerText = element.name as string;
        if (element.element.type === 'checkbox') {
          label.classList.add('form-check-label');
        }
        // for checkboxes the label comes second
        if (element.element.type === 'checkbox') {
          const wrapperDiv = document.createElement('div');
          wrapperDiv.classList.add('form-check');
          rowDiv.append(wrapperDiv);
          wrapperDiv.append(element.element);
          // add some spacing between the checkbox and the label
          label.classList.add('ml-1');
          wrapperDiv.append(label);
        } else {
          rowDiv.append(label);
          rowDiv.append(element.element);
        }
      }
    });
  }
}
