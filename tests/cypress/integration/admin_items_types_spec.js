describe('Items Types', () => {
  beforeEach(() => {
    cy.login()
  })

  it('Create and delete an item type', () => {
    const newname = 'New test item type'
    cy.visit('/admin.php?tab=5')
    cy.window().then(win => {
      cy.stub(win, 'prompt').returns(newname)
      cy.get('[data-action="itemstypes-create"]').click()
    })
    cy.url().should('contains', 'templateid')
    cy.get('#itemsTypesName').should('have.value', newname)
    cy.get('[data-action="itemstypes-destroy"]').click()
    cy.url().should('contains', 'tab=5')
  });
});
