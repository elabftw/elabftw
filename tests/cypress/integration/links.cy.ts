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

  it('can import from experiments and resources', () => {
    // create 4 exp via API
    ['A', 'B', 'C', 'D'].forEach(title => {
      postRequest('experiments', {
        title: `Links test-${title}`,
      }).then(res => expect(res.status).to.equal(201));
    });

    // create 4 res via API
    ['a', 'b', 'c', 'd'].forEach(title => {
      postRequest('items', {
        title: `Links test-${title}`,
      }).then(res => expect(res.status).to.equal(201));
    });

    // link exp A to exp B
    cy.visit('/experiments.php');
    const targetA = 'Links test-A';
    cy.get('#itemList').contains(targetA).click();
    cy.intercept('GET', /\/api\/v2\/experiments\/\?.+$/).as('getExpQueryApi');
    cy.get('#topToolbar').get('[title="Edit"]').click();

    const targetB = 'Links test-B';
    cy.intercept('POST', '/api/v2/experiments/*/experiments_links/*').as('postExpLinkExpApi');
    cy.get('#addLinkExpInput').type(targetB, {delay: 0});
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetB}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('GET', '/experiments.php?mode=edit*').as('getExpPage');
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkExpApi');
      cy.wait('@getExpPage');
      cy.get('#linksExpDiv').should('contain', targetB);
    });

    // link exp A to res b
    const targetb = 'Links test-b';
    cy.intercept('GET', /api\/v2\/items\/\?.+$/).as('getResQueryApi');
    cy.intercept('POST', '/api/v2/experiments/*/items_links/*').as('postExpLinkResApi');
    cy.get('#addLinkItemsInput').type(targetb, {delay: 0});
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetb}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkResApi');
      cy.wait('@getExpPage');
      cy.get('#linksDiv').should('contain', targetb);
    });

    // link res a to exp D
    const targetD = 'Links test-D';
    cy.visit('/database.php');
    const targeta = 'Links test-a';
    cy.get('#itemList').contains(targeta).click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.intercept('GET', '/database.php?mode=edit*').as('getResPage');
    cy.intercept('POST', '/api/v2/items/*/experiments_links/*').as('postResLinkExpApi');
    cy.get('#addLinkExpInput').type(targetD, {delay: 0});
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetD}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkExpApi');
      cy.wait('@getResPage');
      cy.get('#linksExpDiv').should('contain', targetD);
    });

    // link res a to res d
    const targetd = 'Links test-d';
    cy.intercept('POST', '/api/v2/items/*/items_links/*').as('postResLinkResApi');
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

    // link exp C to exp A
    cy.visit('/experiments.php');
    cy.get('#itemList').contains('Links test-C').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.get('#addLinkExpInput').type(targetA);
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetA}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkExpApi');
      cy.wait('@getExpPage');
      cy.get('#linksExpDiv').should('contain', targetA);
    });

    // link exp C to res a
    cy.get('#addLinkItemsInput').type(targeta);
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targeta}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkResApi');
      cy.wait('@getExpPage');
      cy.get('#linksDiv').should('contain', targeta);
    });

    // import links from exp A
    cy.get('#linksExpDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postExpLinkExpApi', '@postExpLinkResApi', '@getExpPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', targetB);
        cy.get('#linksDiv').should('contain', targetb);
      });
    });

    // import links from res a
    cy.get('#linksDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postExpLinkExpApi', '@postExpLinkResApi', '@getExpPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', targetD);
        cy.get('#linksDiv').should('contain', targetd);
      });
    });

    // link res c to exp A
    cy.visit('/database.php');
    cy.get('#itemList').contains('Links test-c').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.get('#addLinkExpInput').type(targetA);
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targetA}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkExpApi');
      cy.wait('@getResPage');
      cy.get('#linksExpDiv').should('contain', targetA);
    });

    // link res c to res a
    cy.get('#addLinkItemsInput').type(targeta);
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${targeta}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkResApi');
      cy.wait('@getResPage');
      cy.get('#linksDiv').should('contain', targeta);
    });

    // import links from exp A
    cy.get('#linksExpDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postResLinkExpApi', '@postResLinkResApi', '@getResPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', targetB);
        cy.get('#linksDiv').should('contain', targetb);
      });
    });

    // import links from res a
    cy.get('#linksDiv > :first-child [data-action="import-links"]').click().then(() => {
      cy.wait(['@postResLinkExpApi', '@postResLinkResApi', '@getResPage'], {timeout: 60000}).then(() => {
        cy.get('#linksExpDiv').should('contain', targetD);
        cy.get('#linksDiv').should('contain', targetd);
      });
    });
  });
});
