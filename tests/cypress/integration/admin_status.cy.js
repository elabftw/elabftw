describe('Status in admin panel', () => {
  beforeEach(() => {
    cy.login()
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0])
  })

  it('Create, update and delete a status', () => {
    const newname = 'New cypress test status'
    cy.visit('/admin.php?tab=4')
    cy.get('#statusName').type(newname)
    // create
    cy.intercept('/admin.php?tab=4').as('statusCreated')
    cy.get('[data-action="create-status"]').click()
    cy.wait('@statusCreated').then(() => {
      cy.get('ul[data-table="status"]').find('li[data-statusid]').find('input[value="' + newname + '"]').parent().parent().as('newStatus')
      cy.get('@newStatus').find('input').first().should('have.value', newname)
    })
    // update
    cy.get('@newStatus').find('input').first().type('something')
    cy.intercept('/api/v2/teams/1/status/17').as('statusUpdated')
    cy.get('@newStatus').find('[data-action="update-status"]').click()
    cy.wait('@statusUpdated').then(() => {
      cy.get('#overlay').should('be.visible').should('contain', 'Saved')
    })
    // destroy
    cy.get('@newStatus').find('[data-action="destroy-status"]').click()
    cy.wait('@statusUpdated').then(() => {
      cy.get('#overlay').should('be.visible').should('contain', 'Saved')
    })
  });
});
