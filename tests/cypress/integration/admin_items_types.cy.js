describe('Items Types', () => {
  beforeEach(() => {
    cy.login()
  })
  const newname = 'New test item type'

  it('Create an item type', () => {
    cy.visit('/admin.php?tab=5')
    cy.intercept('POST', '/api/v2/items_types', req => {
      req.on('before:response', res => {
          expect(res.statusCode).to.equal(201)
          expect(res.headers.location).to.include('templateid=10')
      })
    })
    cy.window().then(win => {
      cy.stub(win, 'prompt').returns(newname)
      cy.get('[data-action="itemstypes-create"]').click()
    })
    cy.visit('/admin.php?tab=5&templateid=10')
    cy.get('#itemsTypesName').should('have.value', newname)
  });

  it('Delete an item type', () => {
    cy.visit('/admin.php?tab=5&templateid=10')
    cy.get('[data-action="itemstypes-destroy"]').click()
    cy.url().should('contains', 'tab=5')
    cy.get('[data-table="items_types"]').get('#itemstypes_10').should('not.exist');
  });
});
