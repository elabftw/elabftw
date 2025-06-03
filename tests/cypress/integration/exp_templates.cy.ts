describe('Experiments templates', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Create and edit an experiment template', () => {
    cy.visit('/templates.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_templates').should('be.visible').should('contain', 'Create a new template');
    cy.get('input[name=title]').type('Template');
    cy.get('button[data-action="create-entity"][data-type="experiments_templates"]').click();
    templateEdit();
    templateComment();
    templateDuplicate();
    templateDestroy();
  });


  const templateEdit = () => {
    cy.url().should('include', 'mode=edit');

    // change category and status
    cy.get('#category_select').select('Cell biology').blur();
    cy.get('#status_select').select('Success').blur();
    // edit tags, steps and permissions

    createAndDeleteTag();
    createCompleteAndDeleteStep();

    cy.get('#canread_is_immutable').check({force: true});
    cy.get('#canwrite_is_immutable').check({force: true});
    cy.get('#canread_is_immutable').should('be.checked');
    cy.get('#canwrite_is_immutable').should('be.checked');
    // permissions has to be improved since there's multiple modals so they
    // setTemplatePermissions(endpoint);
  };

  const templateComment = () => {
    // go in view mode
    cy.get('[title="View mode"]').click();
    cy.url().should('include', 'mode=view');

    cy.get('#commentsCreateArea').type('This is a very nice template');
    cy.get('[data-action="create-comment"]').click();
    cy.get('#commentsDiv').contains('Toto Le sysadmin commented').should('be.visible');
    cy.get('[data-action="destroy-comment"]').click();
    cy.get('#commentsDiv').contains('Toto Le sysadmin commented').should('not.exist');
    cy.htmlvalidate();
  };

  const templateDuplicate = () => {
    // keep the original template url in memory
    cy.url().then(url => {
      cy.log(url);
      cy.get('[data-target="duplicateModal"]').click()
        .get('[data-action="duplicate-entity"]').click();
      // cy.wait('@apiGET');
      // cy.wait('@apiGET');
      // cy.wait('@apiPOST');
      cy.get('#documentTitle').should('be.visible').should('contain', 'Template I');
      // destroy the duplicated entity now
      templateDestroy();
      // go back to the original entity
      cy.visit(url);
    });
  };

  const createAndDeleteTag = () => {
    // create Tag
    cy.get('#createTagInput').type('some tag').blur();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    cy.get('div.tags').contains('some tag').should('exist');

    // delete tag
    cy.on('window:confirm', () => { return true; });
    cy.contains('some tag').click();
    cy.get('div.tags').contains('some tag').should('not.exist');
  };

  const createCompleteAndDeleteStep = () => {
    // create step
    cy.get('.stepinput').type('some step');
    cy.get('[data-action="create-step"').click();
    cy.get('.step-static').should('contain', 'some step');

    // complete step
    cy.get('.stepbox').click();
    cy.get('.text-muted').should('contain', 'completed');

    // delete step
    cy.get('[data-action="destroy-step"]').click();
    cy.contains('some step').should('not.exist');
  };

  // TODO when fixed the multiple modal issues
  // const setTemplatePermissions = (endpoint: string) => {
  //   // cy.intercept('PATCH', `/api/v2/${endpoint}/**`).as('apiPATCH');
  //   // cy.intercept('GET', `/api/v2/${endpoint}/**`).as('apiGET');
  //   // read permissions
  //   cy.get('button[data-action="toggle-modal"][data-target="permModal-canread"]').click();
  //   cy.get('#permModal-canread').should('be.visible');
  //   // click only the save button in this modal (because there's many on the web page) // .within() still not working
  //   cy.get('button[data-action="toggle-modal"][data-target="permModal-canread"]').should('be.visible').click();
  //   cy.get('.overlay').first().should('contain', 'Saved');
  //   cy.get('#permModal-canread').should('not.be.visible');
  //
  //   // write permissions
  // };


  const templateDestroy = () => {
    cy.get('button[title="More options"]').click()
      .get('button[data-action="destroy"]').click();
  };
});
