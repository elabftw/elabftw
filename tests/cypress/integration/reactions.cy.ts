import type { Ketcher } from 'ketcher-core';

describe('Structured reactions', () => {
  let entityId: number;

  beforeEach(() => {
    cy.login();
    cy.request('POST', '/api/v2/experiments', { title: 'Reaction foundation test' })
      .then(response => cy.extractIdFromLocation(response))
      .then(id => { entityId = id; cy.visit(`/experiments.php?mode=edit&id=${id}`); });
    cy.get('[data-cy="chem-create-reaction"]', { timeout: 60000 }).should('be.enabled');
    cy.get('#chemEditorDiv').then($editor => {
      if ($editor[0].hidden) cy.get('#chem-editor [data-action="toggle-next"]').click();
    });
  });

  it('creates a reaction from the drawing, calculates a yield and preserves it on reload', () => {
    cy.window().its('ketcher').then((ketcher: Ketcher) => ketcher.setMolecule('CCO>>CC=O'));
    cy.get('[data-cy="chem-create-reaction"]').click();
    cy.get('#reactionModal').should('be.visible');
    cy.get('#reactionTitle').type('Ethanol oxidation');
    cy.get('#reactionComponents > tr').should('have.length', 2);
    cy.get('#reactionComponents > tr').eq(0).within(() => {
      cy.get('[data-field="actual_unit"]').select('mol');
      cy.get('[data-field="actual_value"]').type('0.01');
    });
    cy.get('#reactionComponents > tr').eq(1).within(() => {
      cy.get('[data-field="actual_unit"]').select('mol');
      cy.get('[data-field="actual_value"]').type('0.008');
      cy.get('[data-result="actual"]').should('contain.text', '80 %');
    });
    cy.intercept('POST', `**/api/v2/experiments/${entityId}/reactions`).as('saveReaction');
    cy.get('[data-cy="reaction-save"]').click();
    cy.wait('@saveReaction').its('response.statusCode').should('eq', 201);
    cy.get('#reactionModal').should('not.be.visible');
    cy.contains('#reactionsList article', 'Ethanol oxidation').should('contain.text', '80');
    cy.reload();
    cy.get('[data-cy="chem-create-reaction"]', { timeout: 60000 }).should('be.enabled');
    cy.contains('#reactionsList article', 'Ethanol oxidation').find('[data-reaction-action="edit"]').click();
    cy.get('#reactionComponents > tr').eq(0).find('[data-field="actual_value"]').should('have.value', '0.010000000000');
    cy.request(`/api/v2/experiments/${entityId}/reactions`).then(response => {
      const reaction = response.body[0];
      expect(reaction.rxn).to.match(/^\$RXN V3000/);
      expect(reaction.scheme.root.nodes).to.have.length.greaterThan(0);
      expect(reaction.components[1].actual.yield_percent).to.eq(80);
    });
  });

  it('keeps the form after a failed save and records history after an edit', () => {
    cy.window().its('ketcher').then((ketcher: Ketcher) => ketcher.setMolecule('CCO>>CC=O'));
    cy.get('[data-cy="chem-create-reaction"]').click();
    cy.get('#reactionTitle').type('Original reaction');
    cy.intercept({ method: 'POST', url: `**/api/v2/experiments/${entityId}/reactions`, times: 1 }, {
      statusCode: 503, body: { description: 'Temporarily unavailable' },
    });
    cy.get('[data-cy="reaction-save"]').click();
    cy.get('#reactionError').should('contain.text', 'Temporarily unavailable');
    cy.get('#reactionTitle').should('have.value', 'Original reaction');
    cy.get('[data-cy="reaction-save"]').should('be.enabled').click();
    cy.get('#reactionModal').should('not.be.visible');
    cy.contains('#reactionsList article', 'Original reaction').find('[data-reaction-action="edit"]').click();
    cy.get('#reactionTitle').clear().type('Updated reaction');
    cy.get('[data-cy="reaction-save"]').click();
    cy.get('#reactionModal').should('not.be.visible');
    cy.request(`/api/v2/experiments/${entityId}/reactions`).then(response => {
      const id = response.body[0].id;
      cy.request(`/api/v2/experiments/${entityId}/reactions?history_of=${id}`).then(history => {
        expect(history.body).to.have.length(2);
        expect(history.body[0].snapshot.title).to.eq('Original reaction');
        expect(history.body[1].snapshot.title).to.eq('Updated reaction');
      });
    });
  });
});
