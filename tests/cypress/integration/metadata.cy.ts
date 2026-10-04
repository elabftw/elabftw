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

  it('Preserves apostrophes in custom field names', () => {
    cy.createEntity();
    cy.addTextMetadataField('l\'appartement');
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
            cy.get('[data-purpose="value-label"]').should('have.value', 'labelB');
          });
      });
    });
  });

  it('Creates, edits and clears value labels without replacing other fields', () => {
    cy.request('POST', '/api/v2/experiments', { title: `Cypress editable labels ${Date.now()}` }).then(response => {
      cy.extractIdFromLocation(response).then(id => {
        const url = `/api/v2/experiments/${id}`;
        cy.request('PATCH', url, { metadata: JSON.stringify({ extra_fields: {
          Numbers: { type: 'number', allow_multi_values: true, value: ['1', '2'], unit: 'mg' },
          Untouched: { value: 'before' },
          Readonly: { type: 'text', allow_multi_values: true, value: ['R'], value_labels: ['locked'], readonly: true },
          Checkboxes: { type: 'checkbox', allow_multi_values: true, value: ['on', 'off'] },
        } }) });
        cy.visit(`/experiments.php?mode=edit&id=${id}`);
        const labels = '[data-purpose="multi-value-holder"][data-field="Numbers"] input[data-purpose="value-label"]';
        cy.get(labels).should('have.length', 2);
        cy.get('[data-purpose="multi-value-holder"][data-field="Readonly"]').within(() => {
          cy.get('input[data-purpose="value-label"], button').should('not.exist');
          cy.get('[data-purpose="value-label"]').should('have.text', 'locked');
          cy.get('textarea').should('have.attr', 'readonly');
        });
        cy.get('[data-purpose="multi-value-holder"][data-field="Checkboxes"] [data-purpose="multi-value-row"]').then($rows => {
          const checkbox = $rows[0].querySelector('input[type="checkbox"]').getBoundingClientRect();
          const nextLabel = $rows[1].querySelector('[data-purpose="value-label"]').getBoundingClientRect();
          expect(checkbox.bottom).to.be.at.most(nextLabel.top);
        });
        // Simulate a different field being saved after this page was loaded.
        cy.request('PATCH', url, { action: 'updatemetadatafield', Untouched: 'after' });
        cy.intercept('PATCH', `**${url}`, request => {
          if (request.body.action === 'updatemetadatafield' && request.body.Numbers) {
            request.alias = 'saveLabel';
          }
        });
        const checkSaved = (expected: string[]|null) => {
          cy.wait('@saveLabel').then(interception => {
            expect(interception.response?.statusCode).to.eq(200);
            expect(interception.request.body).to.deep.eq({
              action: 'updatemetadatafield', Numbers: { value: ['1', '2'], value_labels: expected },
            });
          });
          cy.request(url).then(saved => {
            const fields = JSON.parse(saved.body.metadata).extra_fields;
            expect(fields.Numbers.value_labels ?? null).to.deep.eq(expected);
            expect(fields.Numbers.unit).to.eq('mg');
            expect(fields.Untouched.value).to.eq('after');
          });
        };
        cy.get(labels).first().type('initial').blur();
        checkSaved(['initial', '']);
        cy.reload();
        cy.get(labels).first().should('have.value', 'initial').clear().type('<img src=x onerror=alert(1)>').blur();
        checkSaved(['<img src=x onerror=alert(1)>', '']);
        cy.reload();
        cy.get(labels).first().should('have.value', '<img src=x onerror=alert(1)>');
        cy.get('#metadataDiv img').should('not.exist');
        cy.get(labels).first().clear().blur();
        checkSaved(null);
        cy.reload();
        cy.get(labels).first().should('have.value', '');
      });
    });
  });
});
