/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

export interface ValidMetadata {
  elabftw: MetadataElabftw,
  extra_fields: ExtraField,
}

export interface ExtraField {
  [key: string]: ExtraFieldProperties,
}

export enum ExtraFieldInputType {
  Checkbox = 'checkbox',
  Date = 'date',
  Number = 'number',
  Radio = 'radio',
  Select = 'select',
  Text = 'text',
  Time = 'time',
  Url = 'url',
}

export interface ExtraFieldProperties {
  type?: ExtraFieldInputType;
  value: string|string[];
  group_id?: number;
  position?: number;
  options?: string[];
  description?: string;
  allow_multi_values?: boolean;
  required?: boolean;
}

export interface MetadataElabftw {
  extra_fields_groups?: Array<ExtraFieldsGroup>,
  display_main_text?: boolean,
}

export interface ExtraFieldsGroup {
  id: number,
  name: string,
}
