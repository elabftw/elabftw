describe('Metadata Extra fields', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Create and edit metadata in an experiment', () => {
    cy.createEntity();
    cy.addTextMetadataField('Raw data URL');
    cy.removeMetadataField();
    cy.addUserMetadataField('Owner', 'Titi');
  });

  it('Keeps multi-value labels paired after deleting a row', () => {
    const fieldName = 'Checks';
    const metadata = JSON.stringify({
      extra_fields: {
        [fieldName]: {
          type: 'text',
          allow_multi_values: true,
          value: ['A', 'B'],
          value_labels: ['labelA', 'labelB'],
        },
      },
    });

    cy.request({
      method: 'POST',
      url: '/api/v2/experiments',
      body: { title: `Cypress labeled values ${Date.now()}` },
    }).then(createResponse => {
      expect(createResponse.status).to.eq(201);
      cy.extractIdFromLocation(createResponse).then(experimentId => {
        cy.request({
          method: 'PATCH',
          url: `/api/v2/experiments/${experimentId}`,
          body: { metadata },
        }).its('status').should('eq', 200);

        cy.visit(`/experiments.php?mode=edit&id=${experimentId}`);
        const holder = `[data-purpose="multi-value-holder"][data-field="${fieldName}"]`;

        cy.get(`${holder} [data-purpose="multi-value-row"]`).should('have.length', 2);
        cy.intercept('GET', `**/api/v2/experiments/${experimentId}`).as('readMetadata');
        cy.intercept('PATCH', `**/api/v2/experiments/${experimentId}`).as('saveMetadata');
        cy.get(`${holder} [data-purpose="multi-value-row"]`).first().find('button').click();
        cy.wait('@saveMetadata').then(interception => {
          expect(interception.response?.statusCode).to.eq(200);
          expect(interception.request.body.action).to.eq('updatemetadatafield');
          expect(interception.request.body[fieldName]).to.deep.eq({
            value: ['B'],
            value_labels: ['labelB'],
          });
        });
        cy.wait('@readMetadata').its('response.statusCode').should('eq', 200);

        cy.reload();
        cy.get(`${holder} [data-purpose="multi-value-row"]`).should('have.length', 1)
          .first().within(() => {
            cy.get('textarea').should('have.value', 'B');
            cy.get('[data-purpose="value-label"]').should('have.text', 'labelB');
          });
      });
    });
  });
});
