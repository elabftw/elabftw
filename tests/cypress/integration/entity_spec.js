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
    cy.get('.text-muted').contains('completed')
    cy.get('.stepDestroy').click()
    cy.contains('some step').should('not.exist')
  }

  const entityComment = () => {
    // go in view mode
    cy.contains('View mode').click()
    cy.get('#commentsCreateArea').type('This is a very nice experiment')
    cy.get('#commentsCreateButton').click()
    cy.contains('Toto Le sysadmin commented').should('be.visible')
    cy.get('[data-action="destroy-comment"]').click()
    cy.contains('Toto Le sysadmin commented').should('not.exist')
    // go back in edit mode for destroy action
    cy.get('.action-topmenu > [data-action="edit"]').click()
  }

  const entityDuplicate = () => {
    // keep the original entity url in memory
    cy.url().then(url => {
      cy.log(url)
      // go in view mode
      cy.contains('View mode').click()
      cy.get('[data-action="duplicate-entity"]').click()
      cy.contains('Title').should('be.visible')
      // destroy the duplicated entity now
      cy.get('a[title="More options"]').click().get('a[data-action="destroy"]').click()
      // go back to the original entity
      cy.visit(url)
    })
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
    entityComment()
    entityDuplicate()
    entityDestroy()
  });

  it('Create and edit an item', () => {
    cy.visit('/database.php')
    cy.contains('Create').click()
    cy.contains('Edit me').click()
    entityEdit()
    cy.get('#category_select').select('Microscope').blur()
    cy.contains('Saved').should('be.visible')
    entityComment()
    entityDuplicate()
    entityDestroy()
  });
});
