/* eslint-disable @typescript-eslint/no-explicit-any */
describe('Inline spreadsheet', () => {
  beforeEach(() => {
    cy.login();
  });

  it('creates a spreadsheet, embeds a computed snapshot and keeps a single attachment', () => {
    const fileName = `cydata-${Date.now()}.xlsx`;

    cy.getExperimentId().then(expid => {
      cy.visit(`/experiments.php?mode=edit&id=${expid}`);

      // wait for TinyMCE (body editor) and the inline-spreadsheet bundle to be ready
      cy.get('#body_area_ifr', { timeout: 20000 }).should('exist');
      cy.get('button[aria-label="Insert spreadsheet"]').should('exist').click();

      // CREATE NEW
      cy.get('#inlineSpreadsheetModal').should('be.visible');
      cy.get('#inlineSheetNewName').clear().type(fileName);
      cy.get('#inlineSheetCreateBtn').click();

      // the standalone editor opens and an inline snapshot block is embedded in the body
      cy.get('#spreadsheetEditorDiv').should('not.have.attr', 'hidden');
      cy.get('#body_area_ifr').its('0.contentDocument.body').then(cy.wrap)
        .find(`.elabftw-inline-sheet[data-sheet-name="${fileName}"]`, { timeout: 20000 })
        .should('exist');

      // enter values + a formula in the standalone editor, then save
      cy.get('#spreadsheetIframe').its('0.contentDocument', { timeout: 20000 })
        .find('#spreadsheetEditorRoot .jss_container', { timeout: 20000 })
        .should('exist')
        .then($container => {
          const ws = ($container[0] as any).jssWorksheet;
          ws.setValue('A1', '6');
          ws.setValue('A2', '7');
          ws.setValue('A3', '=A1*A2');
        });
      // click the save (floppy) button in the standalone editor toolbar
      cy.get('#spreadsheetIframe').its('0.contentDocument').find('.fa-floppy-disk').click();

      // the inline snapshot auto-updates to the computed result (42), not the formula
      cy.get('#body_area_ifr').its('0.contentDocument.body').then(cy.wrap)
        .find(`.elabftw-inline-sheet[data-sheet-name="${fileName}"]`, { timeout: 20000 })
        .should('contain.text', '42')
        .should('not.contain.text', '=A1*A2');

      // exactly one active attachment for that filename (no duplicate from the replace-on-save)
      cy.request(`/api/v2/experiments/${expid}/uploads`).then(res => {
        const matching = (res.body as any[]).filter(u => u.real_name === fileName);
        expect(matching).to.have.length(1);
      });
    });
  });
});
