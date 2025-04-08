describe('Import links', () => {
  beforeEach(() => {
    cy.login().as('csrfToken');
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  const postRequest = (endpoint: string, body: object): Cypress.Chainable => {
    return cy.get('@csrfToken').then(token => {
      return cy.request({
        url: `/api/v2/${endpoint}`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify(body),
        failOnStatusCode: false,
        followRedirect: false,
      });
    });
  };

  // create all necessary entities via API
  before(() => {
    cy.login().as('csrfToken');

    // create 4 exp
    ['A', 'B', 'C', 'D'].forEach(title => {
      postRequest('experiments', {
        title: `Links test-${title}`,
      }).then(res => expect(res.status).to.equal(201));
    });

    // create 4 res
    ['a', 'b', 'c', 'd'].forEach(title => {
      postRequest('items', {
        title: `Links test-${title}`,
      }).then(res => expect(res.status).to.equal(201));
    });

    // create a template
    postRequest('experiments_templates', {
      title: 'Links test-template',
    }).then(res => expect(res.status).to.equal(201));
  });

  it('experiments can have links to experiments and resources', () => {
    cy.visit('/experiments.php');
    cy.get('#itemList').contains('Links test-A').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.intercept('GET', /\/api\/v2\/experiments\/\?.+$/).as('getExpQueryApi');

    // link to exp B
    const targetB = 'Links test-B';
    cy.get('#addLinkExpInput').type(targetB, {delay: 0});
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetB}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/experiments/*/experiments_links/*').as('postExpLinkExpApi');
    cy.intercept('GET', '/experiments.php?mode=edit*').as('getExpPage');

    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkExpApi');
      cy.wait('@getExpPage');
      cy.get('#linksExpDiv').should('contain', targetB);
    });

    cy.intercept('GET', /api\/v2\/items\/\?.+$/).as('getResQueryApi');

    // link to res b
    const targetb = 'Links test-b';
    cy.get('#addLinkItemsInput').type(targetb, {delay: 0});
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetb}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/experiments/*/items_links/*').as('postExpLinkResApi');

    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkResApi');
      cy.wait('@getExpPage');
      cy.get('#linksDiv').should('contain', targetb);
    });
  });

  it('resources can have links to experiments and resources', () => {
    cy.visit('/database.php');
    cy.get('#itemList').contains('Links test-a').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.intercept('GET', /\/api\/v2\/experiments\/\?.+$/).as('getExpQueryApi');

    // link to exp D
    const targetD = 'Links test-D';
    cy.get('#addLinkExpInput').type(targetD, {delay: 0});
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetD}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/items/*/experiments_links/*').as('postResLinkExpApi');
    cy.intercept('GET', '/database.php?mode=edit*').as('getResPage');

    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkExpApi');
      cy.wait('@getResPage');
      cy.get('#linksExpDiv').should('contain', targetD);
    });

    cy.intercept('GET', /api\/v2\/items\/\?.+$/).as('getResQueryApi');
    cy.intercept('POST', '/api/v2/items/*/items_links/*').as('postResLinkResApi');

    // link to res d
    const targetd = 'Links test-d';
    cy.get('#addLinkItemsInput').type(targetd, {delay: 0});
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetd}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });

    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkResApi');
      cy.wait('@getResPage');
      cy.get('#linksDiv').should('contain', targetd);
    });
  });

  it('experiments can import links from experiments and resources', () => {
    cy.visit('/experiments.php');
    cy.get('#itemList').contains('Links test-C').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.intercept('GET', /\/api\/v2\/experiments\/\?.+$/).as('getExpQueryApi');

    // link to exp A
    const targetA = 'Links test-A';
    cy.get('#addLinkExpInput').type(targetA);
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetA}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/experiments/*/experiments_links/*').as('postExpLinkExpApi');
    cy.intercept('GET', '/experiments.php?mode=edit*').as('getExpPage');

    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkExpApi');
      cy.wait('@getExpPage');
      cy.get('#linksExpDiv').should('contain', targetA);
    });

    cy.intercept('GET', /api\/v2\/items\/\?.+$/).as('getResQueryApi');

    // link to res a
    const targeta = 'Links test-a';
    cy.get('#addLinkItemsInput').type(targeta);
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targeta}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/experiments/*/items_links/*').as('postExpLinkResApi');

    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkResApi');
      cy.wait('@getExpPage');
      cy.get('#linksDiv').should('contain', targeta);
    });

    // import links from exp A
    cy.get('#linksExpDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postExpLinkExpApi', '@postExpLinkResApi', '@getExpPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', 'Links test-B');
        cy.get('#linksDiv').should('contain', 'Links test-b');
      });
    });

    // import links from res a
    cy.get('#linksDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postExpLinkExpApi', '@postExpLinkResApi', '@getExpPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', 'Links test-D');
        cy.get('#linksDiv').should('contain', 'Links test-d');
      });
    });
  });

  it('resources can import links from experiments and resources', () => {
    cy.visit('/database.php');
    cy.get('#itemList').contains('Links test-c').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.intercept('GET', /\/api\/v2\/experiments\/\?.+$/).as('getExpQueryApi');

    // link to exp A
    const targetA = 'Links test-A';
    cy.get('#addLinkExpInput').type(targetA);
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetA}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/items/*/experiments_links/*').as('postResLinkExpApi');
    cy.intercept('GET', '/database.php?mode=edit*').as('getResPage');

    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkExpApi');
      cy.wait('@getResPage');
      cy.get('#linksExpDiv').should('contain', targetA);
    });

    cy.intercept('GET', /api\/v2\/items\/\?.+$/).as('getResQueryApi');

    // link to res a
    const targeta = 'Links test-a';
    cy.get('#addLinkItemsInput').type(targeta);
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targeta}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/items/*/items_links/*').as('postResLinkResApi');

    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkResApi');
      cy.wait('@getResPage');
      cy.get('#linksDiv').should('contain', targeta);
    });

    // import links from exp A
    cy.get('#linksExpDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postResLinkExpApi', '@postResLinkResApi', '@getResPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', 'Links test-B');
        cy.get('#linksDiv').should('contain', 'Links test-b');
      });
    });

    // import links from res a
    cy.get('#linksDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postResLinkExpApi', '@postResLinkResApi', '@getResPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', 'Links test-D');
        cy.get('#linksDiv').should('contain', 'Links test-d');
      });
    });
  });

  it('experiment templates can import links from experiments and resources', () => {
    cy.visit('/templates.php');
    cy.get('#itemList').contains('Links test-template').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.intercept('GET', /\/api\/v2\/experiments\/\?.+$/).as('getExpQueryApi');

    // link to exp A
    const targetA = 'Links test-A';
    cy.get('#addLinkExpInput').type(targetA);
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetA}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/experiments_templates/*/experiments_links/*').as('postTempLinkExpApi');
    cy.intercept('GET', '/templates.php?mode=edit*').as('getTempPage');

    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postTempLinkExpApi');
      cy.wait('@getTempPage');
      cy.get('#linksExpDiv').should('contain', targetA);
    });

    cy.intercept('GET', /api\/v2\/items\/\?.+$/).as('getResQueryApi');

    // link to res a
    const targeta = 'Links test-a';
    cy.get('#addLinkItemsInput').type(targeta);
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targeta}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('POST', '/api/v2/experiments_templates/*/items_links/*').as('postTempLinkResApi');

    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postTempLinkResApi');
      cy.wait('@getTempPage');
      cy.get('#linksDiv').should('contain', targeta);
    });

    // import links from exp A
    cy.get('#linksExpDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postTempLinkExpApi', '@postTempLinkResApi', '@getTempPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', 'Links test-B');
        cy.get('#linksDiv').should('contain', 'Links test-b');
      });
    });

    // import links from res a
    cy.get('#linksDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postTempLinkExpApi', '@postTempLinkResApi', '@getTempPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', 'Links test-D');
        cy.get('#linksDiv').should('contain', 'Links test-d');
      });
    });
  });
});
