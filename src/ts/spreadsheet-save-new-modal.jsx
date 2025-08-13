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

import React, { useState, useRef } from 'react';
import i18next from './i18n';
import { SpreadsheetEditorHelper } from './SpreadsheetEditorHelper.class';
import { FILE_EXPORT_OPTIONS } from './spreadsheet-formats';

export function SaveAsAttachmentModal({columnDefs, rowData, entity, onSaved}) {
  const SpreadsheetHelperC = useRef(new SpreadsheetEditorHelper()).current;
  const [fileName, setFileName] = useState('');
  const [format, setFormat] = useState(FILE_EXPORT_OPTIONS[0].type);

  const handleSave = async () => {
    if (!fileName.trim()) return;
    await SpreadsheetHelperC.saveAsAttachment(format, columnDefs, rowData, entity.type, entity.id, fileName);
    onSaved?.();
    $('#saveNewSpreadsheetModal').modal('hide');
  };

  return (
    <div className='modal fade' id='saveNewSpreadsheetModal' tabIndex='-1' role='dialog' aria-labelledby='saveNewSpreadsheetModalLabel'>
      <div className='modal-dialog' role='document'>
        <div className='modal-content'>
          <div className='modal-header'>
            <h5 className='modal-title' id='saveNewSpreadsheetModalLabel'>{i18next.t('save-attachment')}</h5>
            <button type='button' className='close' data-dismiss='modal' aria-label={i18next.t('close')}>
              <span aria-hidden='true'>&times;</span>
            </button>
          </div>
          <div className='modal-body'>
            <div className='form-group'>
              <label>{i18next.t('file-name')}</label>
              <input type='text' className='form-control' value={fileName} onChange={(e) => setFileName(e.target.value)} />
            </div>
            <div className='form-group'>
              <label>{i18next.t('format')}</label>
              <select className='form-control' value={format} onChange={(e) => setFormat(e.target.value)}>
                {FILE_EXPORT_OPTIONS.map(({ type, labelKey }) => (
                  <option key={type} value={type}>
                    {i18next.t(labelKey)}
                  </option>
                ))}
              </select>
            </div>
          </div>
          <div className='modal-footer'>
            <button type='button' className='btn btn-ghost' data-dismiss='modal'>
              {i18next.t('Cancel')}
            </button>
            <button type='button' className='btn btn-primary' onClick={handleSave}>
              {i18next.t('Save')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
