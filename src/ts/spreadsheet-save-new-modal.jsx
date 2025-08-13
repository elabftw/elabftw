/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Modal designed for saving a new spreadsheet to the entity's attachments.
 */

import React, {  useRef } from 'react';
import i18next from './i18n';
import { SpreadsheetEditorHelper } from './SpreadsheetEditorHelper.class';
import { FILE_EXPORT_OPTIONS } from './spreadsheet-formats';

export function SaveAsAttachmentModal({isDisabled, columnDefs, rowData, entity, onSaved}) {
  const SpreadsheetHelperC = useRef(new SpreadsheetEditorHelper()).current;

  return (
    <div className='modal fade' id='saveNewSpreadsheetModal' tabIndex='-1' role='dialog' aria-labelledby='saveNewSpreadsheetModalLabel'>
      <div className='modal-dialog' role='document'>
        <div className='modal-content'>
          <div className='modal-header'>
            <h5 className='modal-title' id='saveNewSpreadsheetModalLabel'>{i18next.t('save-new')}</h5>
            <button type='button' className='close' data-dismiss='modal' aria-label={i18next.t('close')}>
              <span aria-hidden='true'>&times;</span>
            </button>
          </div>
          <div className='modal-body'>
            <div className='dropdown'>
              <button id='saveAsAttachment' disabled={isDisabled} className='btn hl-hover-gray d-inline p-2 mr-2' title={i18next.t('save-attachment')} data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' type='button'>
                <i className='fas fa-save fa-fw'></i>
              </button>
              <div className='dropdown-menu' data-action='spreadsheet-save-attachment-menu'>
                {FILE_EXPORT_OPTIONS.map(({ type, icon, labelKey }) => (
                  <button
                    key={type}
                    className='dropdown-item'
                    onClick={() => SpreadsheetHelperC.saveAsAttachment(type, columnDefs, rowData, entity.type, entity.id).then(() => onSaved?.())}>
                    <i className={`fas ${icon} fa-fw`}></i>{i18next.t(labelKey)}
                  </button>
                ))}
              </div>
            </div>
          </div>
          <div className='modal-footer'>
            <button type='button' className='btn btn-ghost' data-dismiss='modal'>
              {i18next.t('Cancel')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
