describe('Experiments', () => {
  beforeEach(() => {
    cy.login();
  });

  const entityEdit = (endpoint: string) => {
    cy.url().should('include', 'mode=edit');

    // update date
    cy.get('#date_input').type('2021-05-01').blur();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');

    // create Tag
    cy.get('#createTagInput').type('some tag').blur();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    cy.get('div.tags').contains('some tag').should('exist');

    // delete tag
    cy.on('window:confirm', () => { return true; });
    cy.contains('some tag').click();
    cy.get('div.tags').contains('some tag').should('not.exist');

    // create step
    cy.get('.stepinput').type('some step');
    cy.get('[data-action="create-step"').click();
    cy.get('.step-static').should('contain', 'some step');

    // complete step
    cy.get('.stepbox').click();
    cy.get('.text-muted').should('contain', 'completed');

    //cy.htmlvalidate();

    // delete step
    cy.get('[data-action="destroy-step"]').click();
    cy.contains('some step').should('not.exist');
  };

  const entityComment = () => {
    // go in view mode
    cy.get('[title="View mode"]').click();
    cy.url().should('include', 'mode=view');

    cy.get('#commentsCreateArea').type('This is a very nice experiment');
    cy.get('[data-action="create-comment"]').click();
    cy.get('#commentsDiv').contains('Toto Le sysadmin commented').should('be.visible');
    cy.get('[data-action="destroy-comment"]').click();
    cy.get('#commentsDiv').contains('Toto Le sysadmin commented').should('not.exist');
    cy.htmlvalidate();
  };

  const entityDuplicate = () => {
    // keep the original entity url in memory
    cy.url().then(url => {
      cy.log(url);
      cy.get('[data-target="duplicateModal"]').click()
        .get('[data-action="duplicate-entity"]').click();
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
  };

  const entityCatStat = (category: string, categoryTarget: string, statusTarget: string) => {
    // change category
    cy.get('.malleableCategory').click();
    cy.get(`select:has(option:selected:contains("${category}"))`).first().select(`${categoryTarget}`);
    cy.get('.form-inline > .btn-primary').click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    // change status
    cy.get('.malleableStatus').click();
    cy.get('select:has(option:selected:contains("Not set"))').first().select(`${statusTarget}`);
    cy.get('.form-inline > .btn-primary').click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
  };

  it('Create and edit an experiment', () => {
    const endpoint = 'experiments';
    cy.visit('/experiments.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_experiments').should('be.visible').should('contain', 'Default template').contains('Default template').click();
    entityCatStat('Not set', 'Cell biology', 'Success');
    entityEdit(endpoint);
    entityComment();
    entityDuplicate();
    entityDestroy();
    entityRestore(endpoint, 'experiments.php');
  });

  it('Create and edit an item', () => {
    const endpoint = 'items';
    cy.visit('/database.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_database').should('be.visible').should('contain', 'Microscope').contains('Microscope').click();
    entityCatStat('Microscope', 'Plasmid', 'In stock');
    entityEdit(endpoint);
    entityComment();
    entityDuplicate();
    entityDestroy();
    entityRestore(endpoint, 'database.php');
  });

  const entityRestore = (endpoint: string, publicUrl: string) => {
    cy.visit(`/${publicUrl}`);
    cy.htmlvalidate();
    cy.get('button[title="Show more filters"]').click();
    // filter deleted items
    cy.get('select[name="state"]').select('3');
    // restore
    cy.get('[data-action="restore-entity"]').first().click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
  };
});
