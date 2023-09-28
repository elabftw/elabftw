describe('Items Types', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });
  const newname = 'Cypress :: New item type name';

  it('Create and delete an item type', () => {
    cy.visit('/admin.php?tab=4');
    cy.window().then(win => {
      // create
      cy.stub(win, 'prompt').returns(newname);
      cy.get('[data-action="itemstypes-create"]').click();
      cy.get('#itemsTypesName').should('have.value', newname);
      // delete
      cy.stub(win, 'confirm').returns(true);
      cy.get('[data-action="itemstypes-destroy"]').click();
      cy.contains(newname).should('not.exist');
    });
  });
});
