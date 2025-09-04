describe('Metadata Extra fields', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Create and edit metadata in an experiment', () => {
    cy.visit('/experiments.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_experiments').should('be.visible').should('contain', 'No category').contains('No category').click();
    cy.get('#askTitleModalTitleInput').should('be.visible').wait(500).type('Cypress metadata experiment').click();
    cy.get('#askTitleButton').click();
    createTextExtraField();
    deleteExtrafield();
    createUserExtraField();
  });

  const createTextExtraField = () => {
    createNewField('Text type field', 'text');
  };

  const deleteExtrafield = () => {
    cy.get('#metadataDiv').should('be.visible');
    cy.on('window:confirm', () => { return true; });
    cy.get('[data-action="metadata-rm-field"]').click();
  };

  const createUserExtraField = () => {
    const fieldName = 'User type field';
    createNewField(fieldName, 'users');
    cy.get(`[data-field="${fieldName}"][data-target="users"]`).wait(500).type('Titi{enter}');
    cy.get('ul.ui-autocomplete')
      .should('be.visible')
      .contains('div.ui-menu-item-wrapper', 'Titi')
      .click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
  };

  const createNewField = (fieldName: string, type: string) => {
    cy.get('[data-cy="addMetadataField"]').first().click();
    cy.get('#fieldBuilderModal').should('be.visible');
    cy.get('#newFieldTypeSelect').select(type);
    cy.get('#newFieldKeyInput').wait(500).type(fieldName);
    cy.get('[data-action="save-new-field"]').click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    cy.get('#metadataDiv').should('be.visible').should('contain', fieldName).contains(fieldName);
  };
});
