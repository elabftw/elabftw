describe('Items Types', () => {
  beforeEach(() => {
    cy.login()
  })
  const newname = 'New test item type'

  it('Create and delete an item type', () => {
    cy.visit('/admin.php?tab=5')
    cy.intercept('POST', '/api/v2/items_types', req => {
      req.on('before:response', res => {
          expect(res.statusCode).to.equal(201)
          expect(res.headers.location).to.include(`templateid=`)
      })
    })
    cy.window().then(win => {
      cy.stub(win, 'prompt').returns(newname)
      cy.get('[data-action="itemstypes-create"]').click()
    })
    cy.get('#itemsTypesName').should('have.value', newname)
    cy.get('[data-action="itemstypes-destroy"]').click()
    cy.url().should('contains', 'tab=5')
    cy.contains(newname).should('not.exist')
  });
});
