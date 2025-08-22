describe('Test links', () => {
  beforeEach(() => {
    cy.login();
  });

  it('experiments can have links to experiments and resources', () => {
    cy.on('window:confirm', cy.stub().returns(true));

    cy.getExperimentId().then(expid => {
      cy.visit(`/experiments.php?mode=edit&id=${expid}`);
      
      // Wait for the page to load completely
      cy.get('#addLinkExpInput').should('be.visible');
      cy.get('#addLinkItemsInput').should('be.visible');
      
      // Test linking to an experiment
      const expTitle = 'Link target experiment';
      
      // Set up API intercepts before creating the experiment
      cy.intercept('POST', '/api/v2/experiments').as('createExperiment');
      cy.intercept('POST', '/api/v2/experiments/*/experiments_links').as('createExpLink');
      cy.intercept('DELETE', '/api/v2/experiments/*/experiments_links/*').as('deleteExpLink');
      
      // Create an experiment first
      cy.request({ method: 'POST', url: '/api/v2/experiments', body: {title: expTitle} })
        .then((response) => {
          expect(response.status).to.eq(201);
          
          // Wait a moment for the database to be updated
          cy.wait(500);
          
          // Start typing in the experiment link input
          cy.get('#addLinkExpInput').clear().type(expTitle);
          
          // Wait for the autocomplete dropdown to appear and contain our experiment
          cy.get('.ui-menu-item-wrapper').should('be.visible');
          cy.get('.ui-menu-item-wrapper').contains(expTitle).should('be.visible');
          
          // Click on the specific menu item containing our experiment title
          cy.get('.ui-menu-item-wrapper').contains(expTitle).click();
          
          // Click the add button for experiments
          cy.get('button[aria-label="Add link to an experiment"]').click();
          
          // Wait for the link creation API call to complete
          cy.wait('@createExpLink').its('response.statusCode').should('eq', 201);
          
          // Verify the link appears in the experiments links section
          cy.get('#experimentsLinksDiv').should('contain.text', expTitle);
          
          // Now delete the experiment link
          cy.get('#experimentsLinksDiv [data-action="destroy-link"]').first().click();
          
          // Wait for the delete API call to complete
          cy.wait('@deleteExpLink').its('response.statusCode').should('eq', 204);
          
          // Verify the link is removed
          cy.get('#experimentsLinksDiv').should('not.contain.text', expTitle);
        });
      
      // Test linking to a resource/item
      const itemTitle = 'Light sheet resource';
      
      // Set up API intercepts for items
      cy.intercept('POST', '/api/v2/items').as('createItem');
      cy.intercept('POST', '/api/v2/experiments/*/items_links').as('createItemLink');
      cy.intercept('DELETE', '/api/v2/experiments/*/items_links/*').as('deleteItemLink');
      
      // Create a resource first
      cy.request({ method: 'POST', url: '/api/v2/items', body: {title: itemTitle} })
        .then((response) => {
          expect(response.status).to.eq(201);
          
          // Wait a moment for the database to be updated
          cy.wait(500);
          
          // Start typing in the items link input
          cy.get('#addLinkItemsInput').clear().type(itemTitle);
          
          // Wait for the autocomplete dropdown to appear and contain our item
          cy.get('.ui-menu-item-wrapper').should('be.visible');
          cy.get('.ui-menu-item-wrapper').contains(itemTitle).should('be.visible');
          
          // Click on the specific menu item containing our item title
          cy.get('.ui-menu-item-wrapper').contains(itemTitle).click();
          
          // Click the add button for items
          cy.get('button[aria-label="Add item link"]').click();
          
          // Wait for the link creation API call to complete
          cy.wait('@createItemLink').its('response.statusCode').should('eq', 201);
          
          // Verify the link appears in the items links section
          cy.get('#itemsLinksDiv').should('contain.text', itemTitle);
          
          // Now delete the item link
          cy.get('#itemsLinksDiv [data-action="destroy-link"]').first().click();
          
          // Wait for the delete API call to complete
          cy.wait('@deleteItemLink').its('response.statusCode').should('eq', 204);
          
          // Verify the link is removed
          cy.get('#itemsLinksDiv').should('not.contain.text', itemTitle);
        });
    });
  });
});
