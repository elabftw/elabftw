describe('Editors section', () => {
  beforeEach(() => {
    cy.login();
    cy.request('POST', '/api/v2/experiments', { title: 'Editors tabs test' })
      .then(response => cy.extractIdFromLocation(response))
      .then(id => cy.visit(`/experiments.php?mode=edit&id=${id}`));
  });

  it('groups the spreadsheet, image and json editors under remembered tabs', () => {
    // collapsed by default, like the separate sections were
    cy.get('[data-cy="editors-section-toggle"]').should('have.attr', 'aria-expanded', 'false');
    cy.get('#editorsDiv').should('not.be.visible');
    cy.get('[data-cy="editors-section-toggle"]').click();
    cy.get('#spreadsheetPane').should('be.visible');
    cy.get('#imagePane').should('not.be.visible');
    cy.get('[data-cy="editors-tab-json"]').click();
    cy.get('#jsonEditorContainer').should('be.visible');
    cy.get('#spreadsheetPane').should('not.be.visible');
    cy.get('[data-cy="editors-tab-json"]').type('{leftarrow}');
    cy.get('[data-cy="editors-tab-image"]').should('have.attr', 'aria-selected', 'true').and('have.focus');
    cy.get('#doodleCanvas').should('be.visible');
    cy.reload();
    cy.get('[data-cy="editors-section-toggle"]').should('have.attr', 'aria-expanded', 'true');
    cy.get('#doodleCanvas').should('be.visible');
    // the main section keeps its own tab
    cy.get('[data-cy="body-tab-main"]').should('have.attr', 'aria-selected', 'true');
  });

  it('uploads an image and loads it in the image editor, then loads it again from the attachments', () => {
    // 432x131 png: the editor adds a margin of a tenth of its largest side (43px) above and below it.
    // Select a file from disk: a Buffer built in the spec was uploaded as UTF-8 text, so the stored png could not be decoded.
    cy.get('[data-cy="editors-section-toggle"]').click();
    cy.get('[data-cy="editors-tab-image"]').click();
    cy.intercept('POST', '**/api/v2/experiments/*/uploads').as('upload');
    cy.get('[data-cy="doodle-upload-input"]').selectFile('tests/_data/example.png', { force: true });
    cy.wait('@upload').its('response.statusCode').should('eq', 201);
    cy.get('#doodleCanvas').should('have.attr', 'height', '217');
    cy.get('#uploadsDiv').should('contain', 'example.png');

    // back to the default size, then load the attachment we just uploaded
    cy.get('#clearCanvas').click();
    cy.get('#doodleCanvas').invoke('attr', 'height', '600');
    cy.get('[data-cy="doodle-load-attachment"]').click();
    cy.get('[data-cy="load-attachment-select"]').should('contain', 'example.png');
    cy.get('[data-cy="load-attachment-submit"]').should('be.enabled').click();
    cy.get('#loadAttachmentModal').should('not.be.visible');
    cy.get('#doodleCanvas').should('have.attr', 'height', '217');
  });

  it('makes the image canvas as wide as the editor', () => {
    cy.get('[data-cy="editors-section-toggle"]').click();
    cy.get('[data-cy="editors-tab-image"]').click();
    cy.get('#doodleCanvas').should($canvas => {
      const canvas = $canvas[0] as HTMLCanvasElement;
      expect(canvas.width).to.eq(canvas.parentElement.clientWidth);
    });
  });

  it('resizes the image canvas with the handle', () => {
    cy.get('[data-cy="editors-section-toggle"]').click();
    cy.get('[data-cy="editors-tab-image"]').click();
    cy.get('#doodleCanvas').should('have.attr', 'height', '600');
    cy.get('[data-cy="doodle-resize-handle"]').focus().type('{downarrow}');
    cy.get('#doodleCanvas').should('have.attr', 'height', '610');
    cy.get('#doodleCanvasSize').should('contain', '× 610 px');
    // drag 100px left and 50px down
    cy.get('#doodleCanvas').invoke('attr', 'width').then(width => {
      cy.get('[data-cy="doodle-resize-handle"]')
        .trigger('pointerdown', { clientX: 500, clientY: 500 });
      cy.document().trigger('pointermove', { clientX: 400, clientY: 550 });
      cy.document().trigger('pointerup');
      cy.get('#doodleCanvas').should('have.attr', 'width', String(Number(width) - 100)).and('have.attr', 'height', '660');
    });
  });

  it('remembers the selected body tab when opening another entry', () => {
    // the chemical editor loads when its tab is shown, but this does not need it
    cy.get('[data-cy="body-tab-chem"]').click();
    cy.get('#chemPane').should('be.visible');
    cy.get('#mainTextPane').should('not.be.visible');
    cy.get('#mainTextEllipsis').should('not.be.visible');
    cy.request('POST', '/api/v2/experiments', { title: 'Body tab test' })
      .then(response => cy.extractIdFromLocation(response))
      .then(id => cy.visit(`/experiments.php?mode=edit&id=${id}`));
    cy.get('[data-cy="body-tab-chem"]').should('have.attr', 'aria-selected', 'true');
    cy.get('#chemPane').should('be.visible');
    cy.get('[data-cy="body-tab-main"]').click();
    cy.get('#mainTextPane').should('be.visible');
    cy.get('#chemPane').should('not.be.visible');
    cy.reload();
    cy.get('[data-cy="body-tab-main"]').should('have.attr', 'aria-selected', 'true');
    cy.get('#chemPane').should('not.be.visible');
  });

  it('collapses the sections, with the keyboard too, and remembers it', () => {
    // the main section
    cy.get('[data-cy="body-section-toggle"]').click();
    cy.get('[data-cy="body-section-toggle"]').should('have.attr', 'aria-expanded', 'false');
    cy.get('#entityBodyDiv').should('not.be.visible');
    cy.reload();
    cy.get('#entityBodyDiv').should('not.be.visible');
    cy.get('[data-cy="body-section-toggle"]').focus().type('{enter}');
    cy.get('#entityBodyDiv').should('be.visible');
    cy.get('#mainTextPane').should('be.visible');

    // the editors section
    cy.get('[data-cy="editors-section-toggle"]').focus().type('{enter}');
    cy.get('#editorsDiv').should('be.visible');
    // the page may still be initializing the chem tab (Ketcher, compounds table) and move focus: wait until it is back
    cy.focused().should('have.attr', 'data-cy', 'editors-section-toggle');
    cy.get('[data-cy="editors-section-toggle"]').type(' ');
    cy.get('#editorsDiv').should('not.be.visible');
  });
});
