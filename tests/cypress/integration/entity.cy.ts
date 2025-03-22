describe('Experiments', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  const entityEdit = (endpoint: string) => {
    cy.url().should('include', 'mode=edit');

    // update date
    cy.intercept('PATCH', `/api/v2/${endpoint}/**`).as('apiPATCH');
    cy.get('#date_input').type('2021-05-01').blur();
    cy.wait('@apiPATCH');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');

    // create Tag
    cy.intercept('POST', `/api/v2/${endpoint}/**`).as('apiPOST');
    cy.get('#createTagInput').type('some tag').blur();
    cy.wait('@apiPOST');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    cy.get('div.tags').contains('some tag').should('exist');

    // delete tag
    cy.on('window:confirm', () => { return true; });
    cy.contains('some tag').click();
    cy.wait('@apiPATCH');
    cy.get('div.tags').contains('some tag').should('not.exist');

    // create step
    cy.get('.stepinput').type('some step');
    cy.get('[data-action="create-step"').click();
    cy.wait('@apiPOST');
    cy.get('.step-static').should('contain', 'some step');

    // complete step
    cy.get('.stepbox').click();
    cy.wait('@apiPATCH');
    cy.get('.text-muted').should('contain', 'completed');

    //cy.htmlvalidate();

    // delete step
    cy.intercept('DELETE', `/api/v2/${endpoint}/**`).as('apiDELETE');
    cy.get('[data-action="destroy-step"]').click();
    cy.wait('@apiDELETE');
    cy.contains('some step').should('not.exist');
  };

  const entityComment = () => {
    // go in view mode
    cy.get('[title="View mode"]').click();
    cy.url().should('include', 'mode=view');

    cy.get('#commentsCreateArea').type('This is a very nice experiment');
    cy.get('[data-action="create-comment"]').click();
    cy.wait('@apiPOST');
    cy.get('#commentsDiv').contains('Toto Le sysadmin commented').should('be.visible');
    cy.get('[data-action="destroy-comment"]').click();
    cy.wait('@apiDELETE');
    cy.get('#commentsDiv').contains('Toto Le sysadmin commented').should('not.exist');
    cy.htmlvalidate(
      // {
      //   rules: {
      //     'prefer-native-element': 'off',
      //   },
      // },
    );
  };

  const entityDuplicate = () => {
    // keep the original entity url in memory
    cy.url().then(url => {
      cy.log(url);
      cy.get('[data-target="duplicateModal"]').click()
        .get('[data-action="duplicate-entity"]').click();
      cy.wait('@apiGET');
      cy.wait('@apiGET');
      cy.wait('@apiPOST');
      cy.get('#documentTitle').should('be.visible').should('contain', 'Untitled I');
      // destroy the duplicated entity now
      entityDestroy();
      // go back to the original entity
      cy.visit(url);
    });
  };

  const entityDestroy = () => {
    cy.get('button[title="More options"]').click()
      .get('button[data-action="destroy"]').click();
    cy.wait('@apiDELETE');
  };

  it('Create and edit an experiment', () => {
    const endpoint = 'experiments';
    cy.visit('/experiments.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.intercept('GET', `/api/v2/${endpoint}/**`).as('apiGET');
    cy.get('#createModal_experiments').should('be.visible').should('contain', 'Default template').contains('Default template').click();
    cy.wait('@apiGET');
    cy.wait('@apiGET');
    entityEdit(endpoint);
    // change status
    cy.get('#status_select').select('Success').blur();
    cy.wait('@apiPATCH');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    entityComment();
    entityDuplicate();
    entityDestroy();
  });

  it('Create and edit an item', () => {
    const endpoint = 'items';
    cy.visit('/database.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.intercept('GET', `/api/v2/${endpoint}/**`).as('apiGET');
    cy.get('#createModal_database').should('be.visible').should('contain', 'Microscope').contains('Microscope').click();
    cy.wait('@apiGET');
    cy.wait('@apiGET');
    entityEdit(endpoint);
    cy.get('#category_select').select('Plasmid').blur();
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    entityComment();
    entityDuplicate();
    entityDestroy();
  });
});
