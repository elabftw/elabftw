/**
 * Assert that the pill background is the user color at 40% opacity.
 * color-mix() is serialized differently depending on the browser: chrome and
 * electron report "color(srgb 0.9 0.38 0.3 / 0.4)" while others still report
 * "rgba(230, 97, 76, 0.4)". Compare the channel values instead of the string,
 * with a tolerance of one unit for the float rounding of the srgb form.
 */
function assertTint(r: number, g: number, b: number) {
  return ($el: JQuery<HTMLElement>) => {
    const bg = $el.css('background-color');
    const parts = bg.match(/[\d.]+/g)?.map(Number) ?? [];
    expect(parts, `could not parse background-color "${bg}"`).to.have.length(4);
    const isSrgb = bg.startsWith('color(');
    const scale = isSrgb ? 255 : 1;
    [r, g, b].forEach((expected, i) => {
      expect(parts[i] * scale, `channel ${i} of "${bg}"`).to.be.closeTo(expected, 1);
    });
    expect(parts[3], `alpha of "${bg}"`).to.be.closeTo(0.4, 0.01);
  };
}

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

  it('Render a colored label on an extra field in view mode', () => {
    const metadata = {
      extra_fields: {
        Plain: { type: 'text', value: 'no label here', position: 1 },
        Labelled: {
          type: 'number',
          value: '10.0',
          unit: 'rpm',
          position: 2,
          label: { text: 'Unverified', color: 'e6614c', title: 'written by the analysis pipeline' },
        },
        BadColor: {
          type: 'text',
          value: 'fallback',
          position: 3,
          label: { text: 'Neutral', color: 'nope' },
        },
        Multi: {
          type: 'text',
          allow_multi_values: true,
          value: ['one', 'two'],
          position: 4,
          label: { text: 'Draft' },
        },
      },
    };
    cy.request({ method: 'POST', url: '/api/v2/experiments', body: {} })
      .then(res => cy.extractIdFromLocation(res))
      .then(id => {
        cy.request({
          method: 'PATCH',
          url: `/api/v2/experiments/${id}`,
          body: { metadata: JSON.stringify(metadata) },
        });
        cy.visit(`/experiments.php?mode=view&id=${id}`);

        // a field without a label renders as before
        cy.contains('li', 'no label here').find('.extra-field-label').should('not.exist');

        // the label is rendered once, with its text, color and hover title
        cy.contains('li', 'Labelled').within(() => {
          cy.get('.extra-field-label').should('have.length', 1).and('contain', 'Unverified');
          cy.get('.extra-field-label').should('have.attr', 'title', 'written by the analysis pipeline');
          // the pill carries the user color, tinted to stay readable
          cy.get('.extra-field-label').should(assertTint(230, 97, 76));
        });

        // an invalid color degrades to the neutral grey instead of breaking the view
        cy.contains('li', 'BadColor').within(() => {
          cy.get('.extra-field-label').should('contain', 'Neutral');
          cy.get('.extra-field-label').should(assertTint(189, 189, 189));
        });

        // a multi-value field gets one label for the field, not one per value
        cy.contains('li', 'Multi').find('.extra-field-label').should('have.length', 1);
      });
  });

  it('Create, edit and remove a field label from the UI', () => {
    cy.createEntity();
    cy.addMetadataField('Rotation', 'text');

    // saving the modal is asynchronous, and cy.request() does not retry, so
    // wait for the PATCH to land before reading the metadata back. the alias is
    // registered after addMetadataField, whose own PATCH would otherwise be the
    // one matched, letting the read run before the label is stored
    cy.intercept('PATCH', '/api/v2/experiments/*').as('saveField');

    // add a label to the existing field. the prefill reads the entity through
    // MetadataC.read(), and
    // fillFieldLabelInputs() clears the label input when it resolves, so typing
    // before that loses characters. no input value can gate this: the key input
    // still holds the name addMetadataField() typed, and text is the type select's
    // own default, so both are already set before the callback runs. wait for the
    // request itself instead
    cy.intercept('GET', '/api/v2/experiments/*').as('readForEdit');
    cy.get('[data-action="metadata-edit-field"]').first().click();
    cy.get('#fieldBuilderModal').should('be.visible');
    cy.wait('@readForEdit');
    cy.get('#newFieldLabelTextInput').should('have.value', '');
    cy.get('#newFieldLabelTextInput').type('Unverified');
    cy.get('#newFieldLabelTextInput').should('have.value', 'Unverified');
    cy.get('#newFieldLabelColorInput').invoke('val', '#e6614c').trigger('input');
    cy.get('[data-action="edit-extra-field"]').click();
    cy.wait('@saveField');
    cy.getMetadataOf('Rotation').its('label.text').should('eq', 'Unverified');
    cy.getMetadataOf('Rotation').its('label.color').should('eq', 'e6614c');

    // the modal is prefilled with the stored label when reopened
    cy.get('[data-action="metadata-edit-field"]').first().click();
    cy.get('#newFieldLabelTextInput').should('have.value', 'Unverified');
    cy.get('#newFieldLabelColorInput').should('have.value', '#e6614c');

    // the trash button clears the inputs, saving then removes the label
    cy.get('[data-action="clear-field-label"]').click();
    cy.get('#newFieldLabelTextInput').should('have.value', '');
    cy.get('[data-action="edit-extra-field"]').click();
    cy.wait('@saveField');
    cy.getMetadataOf('Rotation').should('not.have.property', 'label');
  });

  it('Does not recolor a label whose stored color is unusable', () => {
    const metadata = {
      extra_fields: {
        BadColor: {
          type: 'text',
          value: 'fallback',
          position: 1,
          label: { text: 'Neutral', color: 'nope' },
        },
      },
    };
    cy.createEntity();
    cy.url().then(url => {
      const id = new URL(url).searchParams.get('id');
      cy.request({
        method: 'PATCH',
        url: `/api/v2/experiments/${id}`,
        body: { metadata: JSON.stringify(metadata) },
      });
      cy.intercept('PATCH', '/api/v2/experiments/*').as('saveField');
      cy.intercept('GET', '/api/v2/experiments/*').as('readForEdit');
      cy.visit(`/experiments.php?mode=edit&id=${id}`);

      // wait for the field to be rendered before opening its modal. the test above
      // gets this for free from addMetadataField(); clicking into a page that is
      // still rendering toggles the modal twice, and the close resets the footer
      // buttons through the hidden.bs.modal handler
      cy.get('#metadataDiv').should('be.visible').should('contain', 'BadColor');

      // the picker cannot hold "nope", and it always hands a value back to
      // collectFieldLabel(), so it has to be prefilled with the grey the field is
      // actually rendered with rather than the teal default for a new label
      cy.get('[data-action="metadata-edit-field"]').first().click();
      cy.get('#fieldBuilderModal').should('be.visible');
      cy.get('[data-action="edit-extra-field"]').should('be.visible');
      cy.wait('@readForEdit');
      cy.get('#newFieldLabelTextInput').should('have.value', 'Neutral');
      cy.get('#newFieldLabelColorInput').should('have.value', '#bdbdbd');

      // editing an unrelated property must not repaint the label
      cy.get('#newFieldDescriptionInput').clear().type('touched something else');
      cy.get('[data-action="edit-extra-field"]').click();
      cy.wait('@saveField');
      cy.getMetadataOf('BadColor').its('label.text').should('eq', 'Neutral');
      cy.getMetadataOf('BadColor').its('label.color').should('eq', 'bdbdbd');
    });
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
        let awaitingLabelRefresh = false;
        cy.intercept('PATCH', `**${url}`, request => {
          if (request.body.action === 'updatemetadatafield' && request.body.Numbers) {
            request.alias = 'saveLabel';
            awaitingLabelRefresh = true;
          }
        });
        cy.intercept('GET', `**${url}`, request => {
          if (awaitingLabelRefresh) {
            request.alias = 'readLabelMetadata';
            awaitingLabelRefresh = false;
          }
        });
        const checkSaved = (expected: string[]|null) => {
          cy.wait('@saveLabel').then(interception => {
            expect(interception.response?.statusCode).to.eq(200);
            expect(interception.request.body).to.deep.eq({
              action: 'updatemetadatafield', Numbers: { value: ['1', '2'], value_labels: expected },
            });
          });
          // The save also refreshes the JSON editor; finish that read before reloading.
          cy.wait('@readLabelMetadata').its('response.statusCode').should('eq', 200);
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
