describe('Items Types', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });
  const newname = 'New test item type';

  it('Create and delete an item type', () => {
    cy.visit('/admin.php?tab=4');
    cy.intercept('POST', '/api/v2/items_types', req => {
      req.on('before:response', res => {
        expect(res.statusCode).to.equal(201);
        expect(res.headers.location).to.include('templateid=10');
      });
    }).as('create');
    cy.window().then(win => {
      cy.stub(win, 'prompt').returns(newname);
      cy.get('[data-action="itemstypes-create"]').click();
      cy.wait('@create').then(() => {
        cy.get('#itemsTypesName').should('have.value', newname);
      });
    });

    // Delete
    cy.window().then(win => {
      cy.intercept('DELETE', '/api/v2/items_types/*').as('delete');
      cy.stub(win, 'confirm').returns(true);
      cy.get('[data-action="itemstypes-destroy"]').click();
      cy.wait('@delete').then(() => {
        cy.location('search').should('contain', 'tab=4').should('not.contain', 'templateid=10');
        cy.get('[data-table="items_types"]').get('#itemstypes_10').should('not.exist');
      });
    });
  });
});
