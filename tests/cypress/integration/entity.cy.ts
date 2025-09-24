describe('Experiments', () => {
  beforeEach(() => {
    cy.login();
  });
  const entityEdit = () => {
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
    cy.get('#addStepInput').type('some step');
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
        .get('[data-action="duplicate"]').click();
      cy.get('#documentTitle').should('be.visible').should('contain', ' I');
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

  it('Create a resource category', () => {
    const catname = 'Justice';
    cy.visit('/resources-categories.php');
    cy.htmlvalidate();
    cy.get('[data-target="createCatStatModal"]').click();
    // the wait is necessary or it doesn't have the time to type all
    cy.get('#createCatStatName').wait(500).type(catname);
    cy.get('[data-action="create-catstat"]').click();
    //cy.get('#catStatDiv').should('contain', catname);
  });

  it('Create and edit an experiment', () => {
    cy.visit('/experiments.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_experiments').should('be.visible').should('contain', 'No category').contains('No category').click();
    cy.get('#askTitleModalTitleInput').should('be.visible').wait(500).type('Cypress created experiment').click();
    cy.get('#askTitleButton').click();
    entityCatStat('Not set', 'Demo', 'Success');
    entityEdit();
    entityComment();
    entityDuplicate();
    entityDestroy();
    entityRestore('experiments.php');
    entityList('experiments.php');
  });

  it('Create and edit an item', () => {
    cy.visit('/database.php');
    cy.htmlvalidate();
    cy.contains('Create').click();
    cy.get('#createModal_database').should('be.visible').should('contain', 'No category').contains('No category').click();
    cy.get('#askTitleModalTitleInput').should('be.visible').wait(500).type('Cypress created resource').click();
    cy.get('#askTitleButton').click();
    entityCatStat('Not set', 'Justice', 'In stock');
    entityEdit();
    entityComment();
    entityDuplicate();
    entityDestroy();
    entityRestore('database.php');
    entityList('database.php');
  });

  it('Delete a resource category', () => {
    const catname = 'Justice';
    cy.visit('/resources-categories.php');
    cy.htmlvalidate();
    cy.get(`[data-cy=expcatName][value="${catname}"]`)
      .closest('li.list-group-item')
      .find('[data-action="destroy-catstat"]')
      .wait(500)
      .click();
    cy.get('input[data-cy=expcatName][value="Justice"]').should('not.exist');
  });

  const entityRestore = (publicUrl: string) => {
    cy.visit(`/${publicUrl}`);
    cy.htmlvalidate();
    cy.get('button[title="Show more filters"]').click();
    // filter deleted items
    cy.get('select[name="state"]').select('3');
    // restore
    cy.get('[data-action="restore-entity-showmode"]').first().click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
  };

  const entityList = (publicUrl: string) => {
    cy.visit(`/${publicUrl}`);
    cy.get('#itemList').should('be.visible').find('a[href*="mode=view"]').first().click();
    cy.get('a[title="Back to listing"][aria-label="Back to listing"]').should('be.visible').click();
    // ensure there's no wrong listing
    cy.get('body').should('not.contain.html',
      '<div role="status" class="alert alert-danger">',
    );
  };
});
