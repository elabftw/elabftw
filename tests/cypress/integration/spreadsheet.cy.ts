/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
describe('Spreadsheet editor', () => {
  beforeEach(() => {
    cy.login();
    // grouped grid layout shows upload cards, same as uploads.cy.ts
    cy.request({
      method: 'PATCH',
      url: '/api/v2/users/me',
      body: {uploads_layout: 1},
    });
  });

  // https://github.com/elabftw/elabftw/issues/7786
  // a saved workbook used to lose formulas, cell styles and merges because only
  // the worksheet names and data grids were serialized on save
  it('preserves formulas and formatting through a save-load round-trip', () => {
    const downloadedFile = 'tests/cypress/downloads/roundtrip.xlsx';
    cy.request({
      method: 'POST',
      url: '/api/v2/experiments',
      body: {title: `Cypress spreadsheet ${Date.now()}`},
    }).then(response => {
      expect(response.status).to.eq(201);
      return cy.extractIdFromLocation(response);
    }).then(entityId => {
      cy.visit(`/experiments.php?mode=edit&id=${entityId}`);
      // upload a workbook carrying a formula (C1 =A1+B1), a styled cell, an embedded
      // merge and a plain xlsx merge on the second sheet
      // selectFile would read the xlsx as utf8 text and corrupt the binary: pass raw bytes
      // (and no cy.intercept on the upload: Cypress re-serializes FormData bodies)
      cy.readFile('tests/_data/spreadsheet-state.xlsx', null).then(contents => {
        cy.get('input.dz-hidden-input').selectFile({
          contents,
          fileName: 'spreadsheet-state.xlsx',
        }, {force: true});
      });
      cy.get('#uploadsDiv').contains('a', 'spreadsheet-state.xlsx');
      // open it in the spreadsheet editor
      cy.get('#uploadsDiv').contains('a', 'spreadsheet-state.xlsx')
        .closest('[id^="uploadDiv_"]')
        .find('span[data-action="xls-load-file"]')
        .click();
      // the replace button enables once the workbook reached the iframe editor;
      // let the grid mount so the live worksheet state is read back on save
      cy.get('#spreadsheetReplaceAttachment').should('not.be.disabled');
      cy.wait(3000);
      // save it back as a new attachment
      cy.window().then(win => cy.stub(win, 'prompt').returns('roundtrip.xlsx'));
      cy.get('#spreadsheetSaveAsAttachment').click();
      cy.get('#uploadsDiv').contains('a', 'roundtrip.xlsx');
      // fetch the saved attachment through the regular download endpoint
      cy.get('#uploadsDiv').contains('a', 'roundtrip.xlsx')
        .invoke('attr', 'href')
        .then(href => {
          cy.request({url: href as string, encoding: 'binary'}).then(response => {
            cy.writeFile(downloadedFile, response.body, 'binary');
          });
        });
      // the formula survived as a real formula with its last computed value
      cy.task('readZipEntry', {path: downloadedFile, entry: 'xl/worksheets/sheet1.xml'})
        .then(xml => {
          expect(xml).to.contain('<f>A1+B1</f>');
          expect(xml).to.contain('<v>3</v>');
        });
      // jspreadsheet state (cell styles, merges, comments) rides along in a custom workbook property
      cy.task('readZipEntry', {path: downloadedFile, entry: 'docProps/custom.xml'})
        .then(xml => {
          expect(xml).to.contain('elabftw_spreadsheet_state');
          expect(xml).to.contain('background-color');
          expect(xml).to.contain('mergeCells');
          expect(xml).to.contain('A4');
          expect(xml).to.contain('note here');
        });
      // the second sheet's merge, expressed with plain xlsx markup, is preserved natively
      cy.task('readZipEntry', {path: downloadedFile, entry: 'xl/worksheets/sheet2.xml'})
        .should('contain', 'A2:B2');
    });
  });
});
