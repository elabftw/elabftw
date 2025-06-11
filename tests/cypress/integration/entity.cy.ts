describe('Experiments', () => {
  beforeEach(() => {
    cy.login();
  });

  const entityEdit = () => {
    cy.url().should('include', 'mode=edit');

    // update date only on Experiment & Resources pages
    cy.location('pathname').then((path) => {
      if (!path.includes('/templates.php')) {
        cy.get('#date_input').type('2021-05-01').blur();
        cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
      }
    });

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

    // TODO-cypress: (wip) fix is coming on next PR as there's many anchors with no label, and other invalid html
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
    // cy.htmlvalidate();
  };

  const entityDuplicate = () => {
    // keep the original entity url in memory
    cy.url().then(url => {
      cy.log(url);
      cy.get('[data-target="duplicateModal"]').click()
        .get('[data-action="duplicate-entity"]').click();
      cy.location('pathname').then((path) => {
        if (!path.includes('/templates.php')) {
          cy.get('#documentTitle').should('be.visible').should('contain', 'Untitled I');
        } else {
          cy.get('#documentTitle').should('be.visible').should('contain', 'Template I');
        }
      });
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

  it('Create, edit, duplicate and delete an experiment', () => {
    cy.visit('/experiments.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_experiments').should('be.visible').should('contain', 'Default template').contains('Default template').click();
    entityEdit();
    // change status
    cy.get('#status_select').select('Success').blur();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    entityComment();
    entityDuplicate();
    entityDestroy();
  });

  it('Create, edit, duplicate and delete an item', () => {
    cy.visit('/database.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_database').should('be.visible').should('contain', 'Microscope').contains('Microscope').click();
    entityEdit();
    cy.get('#category_select').select('Plasmid').blur();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    entityComment();
    entityDuplicate();
    entityDestroy();
  });

  it('Create, edit, duplicate and delete an experiment template', () => {
    cy.visit('/templates.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_templates').should('be.visible').should('contain', 'Create a new template');
    cy.get('input[name=title]').type('Template');
    cy.get('button[data-action="create-entity"][data-type="experiments_templates"]').click();
    // change category and status
    cy.get('#category_select').select('Cell biology').blur();
    cy.get('#status_select').select('Success').blur();
    // actions specific to template: lock permissions for derived entities
    cy.get('#canread_is_immutable').check({ force: true });
    cy.get('#canwrite_is_immutable').check({ force: true });
    cy.get('#canread_is_immutable').should('be.checked');
    cy.get('#canwrite_is_immutable').should('be.checked');
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    // actions for all entities
    entityEdit();
    entityComment();
    entityDuplicate();
    entityDestroy();
  });
});
