describe('Items Types', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });
  const newname = 'Cypress :: New item type name';

  it('Create and delete an item type', () => {
    cy.visit('/admin.php?tab=4');
    cy.intercept('POST', '/api/v2/items_types', req => {
      req.on('before:response', res => {
        expect(res.statusCode).to.equal(201);
      });
    }).as('create');
    cy.intercept('DELETE', '/api/v2/items_types/*').as('delete');
    cy.window().then(win => {
      // create
      cy.stub(win, 'prompt').returns(newname);
      cy.get('[data-action="itemstypes-create"]').click();
      cy.wait('@create').then(() => {
        cy.url().should('include', 'templateid=');
        cy.get('#itemsTypesName').should('have.value', newname);
      });

      // delete
      cy.stub(win, 'confirm').returns(true);
      cy.get('[data-action="itemstypes-destroy"]').click();
      cy.wait('@delete').then(() => {
        cy.contains(newname).should('not.exist');
        cy.url().should('not.include', 'templateid=');
      });
    });
  });
});
