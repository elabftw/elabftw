import Autocomplete from '../../../src/ts/Autocomplete.class';
import type { AutocompleteOptions, AutocompleteResult } from '../../../src/ts/Autocomplete.class';

describe('Autocomplete', () => {
  let instance: Autocomplete;
  let fixture: HTMLFormElement;

  const setup = (options: AutocompleteOptions) => cy.document().then(doc => {
    fixture = doc.createElement('form');
    fixture.innerHTML = '<label for="autocompleteInput">Search</label><input id="autocompleteInput" autocomplete="on"><input id="nextInput"><button type="submit">Submit</button>';
    fixture.style.padding = '2rem';
    doc.body.append(fixture);
    fixture.addEventListener('submit', event => event.preventDefault());
    instance = Autocomplete.attach(fixture.querySelector('input'), options);
  });

  beforeEach(() => {
    cy.visit('/login.php');
  });

  afterEach(() => {
    instance?.destroy();
    fixture?.remove();
  });

  it('Debounces typing and counts graphemes for the minimum length', () => {
    cy.clock();
    const source = cy.stub().as('source').returns(['Result']);
    setup({ source, minLength: 2 });
    cy.get('#autocompleteInput').invoke('attr', 'aria-controls').then(id => {
      cy.get(`#${id}`).should('have.attr', 'role', 'listbox').should('not.be.visible');
    });
    cy.get('#autocompleteInput').type('👩‍🔬', { delay: 0 });
    cy.tick(300);
    cy.get('@source').should('not.have.been.called');
    cy.get('#autocompleteInput').type('ab', { delay: 0 });
    cy.tick(299);
    cy.get('@source').should('not.have.been.called');
    cy.tick(1);
    cy.get('@source').should('have.been.calledOnceWithExactly', '👩‍🔬ab');
    cy.get('.elab-autocomplete:visible').should('contain.text', 'Result');
  });

  it('Skips disabled messages and selects with Enter without submitting', () => {
    const onSelect = cy.stub().as('select');
    setup({ source: () => [{ label: 'Keep typing', disabled: true }, { label: 'A result', value: '42' }], onSelect, delay: 0 });
    cy.then(() => fixture.addEventListener('submit', cy.stub().as('submit')));
    cy.get('#autocompleteInput').type('a');
    cy.get('.elab-autocomplete [aria-disabled="true"]').click({ force: true });
    cy.get('#autocompleteInput').should('have.value', 'a');
    cy.get('#autocompleteInput').type('{downarrow}').should('have.attr', 'aria-activedescendant');
    cy.get('.elab-autocomplete [aria-selected="true"]').should('have.text', 'A result');
    cy.get('#autocompleteInput').type('{enter}').should('have.value', '42').should('have.attr', 'aria-expanded', 'false');
    cy.get('@select').should('have.been.calledOnce');
    cy.get('@submit').should('not.have.been.called');
  });

  it('Renders labels as text and emits native events while keeping input focus', () => {
    const label = '<img src=x onerror=alert(1)>';
    const events: string[] = [];
    setup({ source: () => [label], delay: 0 });
    cy.get('#autocompleteInput').type('a');
    cy.get('.elab-autocomplete:visible').should('have.text', label).find('img').should('not.exist');
    cy.then(() => {
      const input = fixture.querySelector('input');
      for (const type of ['input', 'change', 'blur']) input.addEventListener(type, () => events.push(type));
    });
    cy.get('.elab-autocomplete [role="option"]').click();
    cy.get('#autocompleteInput').should('have.value', label).should('have.focus');
    cy.then(() => expect(events).to.deep.eq(['input', 'change']));
    cy.get('#nextInput').focus();
    cy.then(() => expect(events).to.deep.eq(['input', 'change', 'blur']));
  });

  it('Ignores old responses, including after the input loses focus', () => {
    const pending = new Map<string, (results: AutocompleteResult[]) => void>();
    setup({ source: term => new Promise(resolve => pending.set(term, resolve)), delay: 0 });
    cy.get('#autocompleteInput').type('a');
    cy.wrap(pending).should('have.property', 'size', 1);
    cy.get('#autocompleteInput').type('b');
    cy.wrap(pending).should('have.property', 'size', 2);
    cy.then(() => pending.get('ab')(['New result']));
    cy.get('.elab-autocomplete:visible').should('contain.text', 'New result');
    cy.then(() => pending.get('a')(['Old result']));
    cy.get('.elab-autocomplete:visible').should('not.contain.text', 'Old result');
    cy.get('#autocompleteInput').type('c');
    cy.wrap(pending).should('have.property', 'size', 3);
    cy.get('#nextInput').focus();
    cy.then(() => pending.get('abc')(['Too late']));
    cy.get('.elab-autocomplete:visible').should('not.exist');
  });

  it('Invalidates pending results when a filter changes', () => {
    let resolve: (results: AutocompleteResult[]) => void;
    const pendingSource = cy.stub().callsFake(() => new Promise<AutocompleteResult[]>(done => resolve = done));
    setup({ source: pendingSource, delay: 0 });
    cy.then(() => {
      const input = fixture.querySelector('input');
      const filter = fixture.querySelector<HTMLInputElement>('#nextInput');
      instance.destroy();
      instance = Autocomplete.attach(input, { source: pendingSource, dependencies: [filter], delay: 0 });
    });
    cy.get('#autocompleteInput').type('a');
    cy.wrap(pendingSource).should('have.been.calledOnce');
    cy.get('#nextInput').trigger('change');
    cy.then(() => resolve(['Old filter result']));
    cy.get('#autocompleteInput').should('have.attr', 'aria-expanded', 'false');
  });

  it('Waits for IME composition to finish and lets Escape dismiss suggestions', () => {
    cy.clock();
    const source = cy.stub().as('source').returns(['日本']);
    setup({ source });
    cy.get('#autocompleteInput').focus().trigger('compositionstart').invoke('val', '日')
      .trigger('input').trigger('keydown', { key: 'Enter', isComposing: true });
    cy.tick(300);
    cy.get('@source').should('not.have.been.called');
    cy.get('#autocompleteInput').trigger('compositionend');
    cy.tick(300);
    cy.get('@source').should('have.been.calledOnceWithExactly', '日');
    cy.get('.elab-autocomplete:visible').should('contain.text', '日本');
    cy.get('#autocompleteInput').type('{downarrow}{esc}').should('have.value', '日')
      .should('not.have.attr', 'aria-activedescendant').should('have.attr', 'aria-expanded', 'false');
  });

  it('Reuses initialization and cleans up after fragment replacement', () => {
    const source = cy.stub().as('source').returns(['Result']);
    setup({ source, delay: 0 });
    cy.then(() => {
      const input = fixture.querySelector('input');
      expect(Autocomplete.attach(input, { source })).to.eq(instance);
    });
    cy.get('#autocompleteInput').type('a');
    cy.get('@source').should('have.been.calledOnce');
    cy.get('.elab-autocomplete').should('have.length', 1);
    cy.then(() => fixture.querySelector('input').remove());
    cy.get('.elab-autocomplete').should('not.exist');
  });

  it('Closes on rejected searches and restores attributes when destroyed', () => {
    const source = cy.stub().as('source').callsFake(() => Promise.reject(new Error('Search failed')));
    setup({ source, delay: 0 });
    cy.get('#autocompleteInput').type('a');
    cy.get('@source').should('have.been.calledOnce');
    cy.get('#autocompleteInput').should('have.attr', 'aria-busy', 'false');
    cy.get('.elab-autocomplete:visible').should('not.exist');
    cy.then(() => instance.destroy());
    cy.get('#autocompleteInput').should('have.attr', 'autocomplete', 'on')
      .should('not.have.attr', 'role').should('not.have.attr', 'aria-controls');
  });
});

describe('Autocomplete initialization', () => {
  beforeEach(() => {
    cy.login();
    cy.intercept({ method: 'GET', pathname: '/api/v2/users/', query: { q: 'Cypress' } }, {
      body: [{ userid: 42, fullname: 'Cypress Collaborator', email: 'collaborator@example.com' }],
    }).as('searchUsers');
    cy.request('POST', '/api/v2/experiments', { title: `Cypress autocomplete ${Date.now()}` }).then(response => {
      cy.extractIdFromLocation(response).then(id => cy.visit(`/experiments.php?mode=edit&id=${id}`));
    });
  });

  const openRequestAction = () => {
    cy.get('[data-action="toggle-modal"][data-target="requestActionModal"]').click();
    cy.focused().should('have.id', 'requestActionUserSelect');
  };

  const selectCollaborator = () => {
    // Keep the modal's autofocus: clicking this input would hide the regression.
    cy.focused().should('have.id', 'requestActionUserSelect').clear().type('Cypress');
    cy.wait('@searchUsers').its('request.query.q').should('eq', 'Cypress');
    cy.get('#requestActionModal .elab-autocomplete:visible').contains('[role="option"]', 'Cypress Collaborator').click();
    cy.get('#requestActionUserSelect').should('have.value', '42 - Cypress Collaborator (collaborator@example.com)')
      .should('have.focus').should('have.attr', 'aria-expanded', 'false');
    cy.get('#autocompleteAnchorDiv_requestActionUserSelect .elab-autocomplete').should('have.length', 1);
  };

  it('Works on the first modal autofocus and after reopening it without clicking the field', () => {
    cy.get('#requestActionUserSelect').should('have.attr', 'role', 'combobox').then($input => {
      $input.on('click', cy.stub().as('collaboratorClick'));
    });
    openRequestAction();
    selectCollaborator();
    cy.get('#requestActionModal [data-dismiss="modal"]').last().click();
    cy.get('#requestActionModal').should('not.be.visible');
    openRequestAction();
    selectCollaborator();
    cy.get('@collaboratorClick').should('not.have.been.called');
    cy.get('@searchUsers.all').should('have.length', 2);
  });

  it('Initializes a replacement input when it is focused without a click', () => {
    openRequestAction();
    cy.get('#requestActionUserSelect').then($input => {
      const original = $input[0];
      const replacement = original.ownerDocument.createElement('input');
      replacement.type = 'text';
      replacement.id = original.id;
      replacement.className = original.className;
      replacement.dataset.action = 'autocomplete';
      replacement.dataset.target = 'users';
      replacement.dataset.identifier = original.dataset.identifier;
      original.replaceWith(replacement);
      replacement.focus();
    });
    selectCollaborator();
    cy.get('@searchUsers.all').should('have.length', 1);
  });
});
