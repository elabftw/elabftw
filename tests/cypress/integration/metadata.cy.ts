describe('Metadata Extra fields', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Create and edit metadata in an experiment', () => {
    cy.createEntity();
    cy.addTextMetadataField('Raw data URL');
    cy.removeMetadataField();
    cy.addUserMetadataField('Owner', 'Titi');
  });
});
