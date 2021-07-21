describe('Experiments templates', () => {
  beforeEach(() => {
    cy.login()
  })

  it('Create and edit an experiment template', () => {
    cy.visit('/ucp.php?tab=3')
    // stub the window.prompt() because that's the only way in cypress to input something into a prompt()
    cy.window().then(win => {
      cy.stub(win, 'prompt').returns('New template')
      cy.get('button[data-action="create-template"]').click()
      cy.get('.dropdown > .float-right').click()
      cy.get('.hover-danger').click()
    })
  })
})
