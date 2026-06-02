// Per-team container units: custom units + hiding built-ins.
// NOTE: scaffold — not executed in the authoring environment. Selectors that depend on
// the Malle inline editor and the admin tab layout may need a small adjustment on first run.
describe('Container units', () => {
  // a unit string that exceeds the 10-character per-unit limit
  const tooLong = 'waytoolongunit';

  beforeEach(() => {
    cy.login();
    // start from a known state: no custom units, nothing hidden
    cy.request('PATCH', '/api/v2/teams/current', { custom_units: '', hidden_units: '' });
  });

  afterEach(() => {
    // leave the team config clean for other specs
    cy.request('PATCH', '/api/v2/teams/current', { custom_units: '', hidden_units: '' });
  });

  it('admin can add custom units and hide a built-in, and they persist', () => {
    cy.visit('/admin.php');

    // add custom units
    cy.get('#custom_units').clear().type('Mcells, OD600').blur();
    // hide the "bar" built-in
    cy.get('.hidden-unit-toggle[value="bar"]').uncheck();

    // reload and assert persistence
    cy.visit('/admin.php');
    cy.get('#custom_units').should('have.value', 'Mcells, OD600');
    cy.get('.hidden-unit-toggle[value="bar"]').should('not.be.checked');
    cy.get('.hidden-unit-toggle[value="mL"]').should('be.checked');
  });

  it('normalizes invalid custom units, reflects the stored value back and warns', () => {
    cy.visit('/admin.php');

    cy.get('#custom_units').clear().type(`mL, mL, ${tooLong}`).blur();

    // a warning naming the dropped unit is shown
    cy.get('.overlay').should('be.visible').and('contain', tooLong);
    // the field is rewritten to exactly what was stored (dedup + over-length dropped)
    cy.get('#custom_units').should('have.value', 'mL');
  });

  it('offers (built-ins - hidden) + custom units in the add-container modal', () => {
    cy.request('PATCH', '/api/v2/teams/current', { custom_units: 'Mcells', hidden_units: 'bar' });

    cy.createResource().then(response => {
      cy.extractIdFromLocation(response).then(itemId => {
        cy.visit(`/database.php?mode=edit&id=${itemId}`);
        cy.get('[data-action="toggle-modal"][data-target="storageModal"]').click();
        cy.get('#storageModal').should('be.visible');

        cy.get('#containerQtyUnitSelect option').then($opts => {
          const values = [...$opts].map(o => (o as HTMLOptionElement).value);
          expect(values).to.include('Mcells'); // custom unit appended
          expect(values).to.include('mL');      // a normal built-in still offered
          expect(values).to.not.include('bar'); // hidden built-in removed
        });
      });
    });
  });

  it('keeps a hidden unit selectable on a container already using it (no silent change)', () => {
    cy.request('PATCH', '/api/v2/teams/current', { hidden_units: 'bar' });

    // create a storage location, then a container stored with the (now hidden) "bar" unit
    cy.request('POST', '/api/v2/storage_units', { name: `Cypress storage ${Date.now()}` })
      .then(storageRes => {
        cy.extractIdFromLocation(storageRes).then(storageId => {
          cy.createResource().then(response => {
            cy.extractIdFromLocation(response).then(itemId => {
              cy.request('POST', `/api/v2/items/${itemId}/containers/${storageId}`, {
                qty_stored: '3',
                qty_unit: 'bar',
              });

              cy.visit(`/database.php?mode=edit&id=${itemId}`);
              // the container displays its stored unit
              cy.get('#containersDiv .malleableQtyUnit').should('contain', 'bar');
              // clicking to edit offers "bar" even though it is hidden team-wide
              cy.get('#containersDiv .malleableQtyUnit').first().click();
              cy.get('#containersDiv select option').then($opts => {
                const values = [...$opts].map(o => (o as HTMLOptionElement).value);
                expect(values).to.include('bar');
              });
            });
          });
        });
      });
  });
});
