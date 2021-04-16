describe('Experiments', () => {
  beforeEach(() => {
    cy.login()
  })

  const entityEdit = () => {
    cy.url().should('include', 'mode=edit')
    cy.get('#date_input').type('2021-05-01').blur()
    cy.contains('Saved').should('be.visible')
    cy.get('#title_input').type('Updated from cypress').blur()
    cy.contains('Saved').should('be.visible')
    cy.get('#createTagInput').type('some tag').blur()
    cy.contains('Saved').should('be.visible')
    cy.get('div.tags').contains('some tag')
    cy.contains('some tag').click()
    cy.get('div.tags').contains('some tag').should('not.exist')
    cy.get('.stepinput').type('some step').blur()
    cy.get('.step-static').contains('some step')
    cy.get('.stepbox').click()
    cy.get('.text-muted').contains('completed in')
    cy.get('.stepDestroy').click()
    cy.contains('some step').should('not.exist')
  }

  const entityDestroy = () => {
    cy.get('a[title="More options"]').click().get('a[data-action="destroy"]').click()
  }

  it('Create and edit an experiment', () => {
    cy.visit('/experiments.php')
    cy.contains('Create').click()
    entityEdit()
    cy.get('#category_select').select('Success').blur()
    cy.contains('Saved').should('be.visible')
    entityDestroy()
  });

  it('Create and edit an item', () => {
    cy.visit('/database.php')
    cy.contains('Create').click()
    cy.contains('Edit me').click()
    entityEdit()
    entityDestroy()
  });
});
