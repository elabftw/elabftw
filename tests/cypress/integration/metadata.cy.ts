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
});
