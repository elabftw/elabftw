describe('Items Types', () => {
  beforeEach(() => {
    cy.login()
  })

  it('Create and edit an item type', () => {
    const newname = 'New test item type'
    cy.visit('/admin.php?tab=5')
    cy.get('#itemsTypesName').type(newname)
    cy.get('#itemsTypesCreate').click()
    cy.get('input[id^=itemsTypesName_]').last().should('have.value', newname).then(($el) => {
      // get the id of the newly created element so we can destroy it
      const newid = $el.get(0).id.split('_').pop()
      cy.get('button[data-id="' + newid + '"]').contains('Delete').click()
    })
  });
});
