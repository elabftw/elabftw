/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

export interface ValidMetadata {
  elabftw?: MetadataElabftw,
  extra_fields: ExtraField,
}

export interface ExtraField {
  [key: string]: ExtraFieldProperties,
}

export interface SubField {
  [key: string]: SubFieldProperties,
}

export enum ExtraFieldInputType {
  Checkbox = 'checkbox',
  Compounds = 'compounds',
  Date = 'date',
  DateTime = 'datetime-local',
  Email = 'email',
  Experiments = 'experiments',
  Multifield = 'multifield',
  Number = 'number',
  Radio = 'radio',
  Items = 'items',
  Select = 'select',
  Text = 'text',
  Time = 'time',
  Uploads = 'uploads',
  Users = 'users',
  Url = 'url',
}

export interface SubFieldProperties {
  type?: ExtraFieldInputType;
  position?: number;
  options?: string[];
  description?: string;
  default?: string|number;
}

export interface ExtraFieldProperties {
  type?: ExtraFieldInputType;
  value: string|number|Array<string|number>|Array<Record<string, string|number>>;
  group_id?: number;
  position?: number;
  options?: string[];
  value_labels?: string[];
  description?: string;
  allow_multi_values?: boolean;
  required?: boolean;
  unit?: string;
  units?: string[];
  readonly?: boolean;
  element?: HTMLElement;
  sub_fields?: SubField;
}

export interface MetadataElabftw {
  extra_fields_groups?: Array<ExtraFieldsGroup>,
}

export interface ExtraFieldsGroup {
  id: number,
  name: string,
}
