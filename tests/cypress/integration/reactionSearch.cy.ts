describe('Reaction search', () => {
  const suffix = Date.now().toString(36);
  const title = `Reaction search target ${suffix}`;
  const otherTitle = `Reaction search other ${suffix}`;
  let entityId: number;

  const createReaction = (id: number, reaction: object) => cy.request('POST', `/api/v2/experiments/${id}/reactions`, reaction);

  before(() => {
    cy.login();
    cy.request('POST', '/api/v2/experiments', { title })
      .then(response => cy.extractIdFromLocation(response))
      .then(id => {
        entityId = id;
        const reactant = '00000000-0000-4000-8000-000000000001';
        createReaction(id, {
          title: `Phenol methylation ${suffix}`,
          reference_component_key: reactant,
          yield_basis_component_key: reactant,
          components: [
            { component_key: reactant, role: 'reactant', name: 'Phenol', smiles: 'Oc1ccccc1', inchi_key: 'ISWSIDIOOBJBQZ-UHFFFAOYSA-N' },
            { role: 'reagent', name: `Methyl iodide ${suffix}`, smiles: 'CI', inchi_key: 'INQOMBQAUSQDDS-UHFFFAOYSA-N' },
            { role: 'product', name: 'Anisole', smiles: 'COc1ccccc1', inchi_key: 'RDOXTESZEPMUJZ-UHFFFAOYSA-N' },
          ],
        });
      });
    cy.request('POST', '/api/v2/experiments', { title: otherTitle })
      .then(response => cy.extractIdFromLocation(response))
      .then(id => createReaction(id, {
        title: `Ester hydrolysis ${suffix}`,
        components: [
          { role: 'reactant', name: 'Ethyl acetate', smiles: 'CCOC(C)=O', inchi_key: 'XEKOWRVHYACXOJ-UHFFFAOYSA-N' },
          { role: 'product', name: 'Ethanol', smiles: 'CCO', inchi_key: 'LFQSCWFLJHTTHZ-UHFFFAOYSA-N' },
        ],
      }));
  });

  beforeEach(() => {
    cy.login();
  });

  it('filters the entries with the reaction search section', () => {
    cy.visit('/experiments.php?mode=show&scope=3');
    cy.get('[data-cy="reactionSearchToggle"]').click();
    cy.get('[data-cy="reactionSearchText"]').type(`Methyl iodide ${suffix}`);
    cy.get('[data-cy="reactionSearchSubmit"]').click();
    cy.location('search').should('contain', 'reaction_q=');
    cy.get('#entityList').should('contain.text', title).and('not.contain.text', otherTitle);

    // a substructure common to both, restricted to products: only anisole contains a benzene ring
    cy.get('[data-cy="reactionSearchText"]').clear();
    cy.get('[data-cy="reactionSearchStructure"]').type('c1ccccc1');
    cy.get('#reactionSearchRole').select('product');
    cy.get('[data-cy="reactionSearchSubmit"]').click();
    cy.get('#entityList').should('contain.text', title).and('not.contain.text', otherTitle);

    cy.get('[data-cy="reactionSearchStructure"]').clear().type('not a structure');
    cy.get('[data-cy="reactionSearchSubmit"]').click();
    cy.get('#reactionSearchError').should('be.visible');

    cy.get('#reactionSearchForm [data-reaction-search-clear="all"]').click();
    cy.location('search').should('not.contain', 'reaction_');
  });

  it('links a reaction to the experiments with the same transformation', () => {
    cy.visit(`/experiments.php?mode=view&id=${entityId}`);
    cy.contains('a', 'Find experiments with the same reaction').click();
    cy.location('search').should('match', /reaction_key=[0-9a-f]{64}/);
    cy.get('#reactionSearch').should('have.attr', 'open');
    cy.get('#entityList').should('contain.text', title).and('not.contain.text', otherTitle);
  });
});
