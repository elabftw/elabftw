type User = {
  email: string;
  userid: number;
};

type Permissions = {
  teams: number[];
  teamgroups: number[];
  users: number[];
};

const findUserId = (email: string) => cy.request({
  method: 'GET',
  url: '/api/v2/users/',
  qs: { q: email },
}).then(response => {
  expect(response.status).to.eq(200);
  const user = (response.body as User[]).find(candidate => candidate.email === email);
  if (!user) {
    throw new Error(`Could not find test user ${email}`);
  }
  return user.userid;
});

const createExperiment = (
  title: string,
  canread: Permissions,
  canreadBase: number,
  canwrite: Permissions,
  canwriteBase: number,
) => cy.request({
  method: 'POST',
  url: '/api/v2/experiments',
  body: {
    title,
    canread: JSON.stringify(canread),
    canread_base: canreadBase,
    canwrite: JSON.stringify(canwrite),
    canwrite_base: canwriteBase,
  },
}).then(response => {
  expect(response.status).to.eq(201);
  return cy.extractIdFromLocation(response);
});

const selectUser = (identifier: string, name: string) => {
  cy.get(`#${identifier}-users-select-input`)
    .clear()
    .type(name);
  cy.get(`#permModal-${identifier} .ts-dropdown .option`)
    .contains(name)
    .should('be.visible')
    .click();
};

const parsePermissions = (value: string): Permissions => JSON.parse(value) as Permissions;

const expectPermissionIds = (actual: string, expected: Permissions) => {
  const parsed = parsePermissions(actual);
  expect(parsed.teams).to.have.members(expected.teams);
  expect(parsed.teamgroups).to.have.members(expected.teamgroups);
  expect(parsed.users).to.have.members(expected.users);
};

describe('Bulk entity permissions', () => {
  let entityIds: number[] = [];

  beforeEach(() => {
    entityIds = [];
    cy.login();
  });

  afterEach(() => {
    entityIds.forEach(id => {
      cy.request({
        method: 'DELETE',
        url: `/api/v2/experiments/${id}`,
        failOnStatusCode: false,
      });
    });
  });

  it('adds multiple users while preserving each entity permissions and bases', () => {
    const titlePrefix = `Cypress bulk permissions ${Date.now()}`;

    return findUserId('toto@yopmail.com').then(existingUserId =>
      findUserId('titi@yopmail.com').then(firstAddedUserId =>
        findUserId('tutu@yopmail.com').then(secondAddedUserId => {
          const firstRead: Permissions = { teams: [1], teamgroups: [], users: [existingUserId] };
          const secondRead: Permissions = { teams: [], teamgroups: [], users: [] };
          const firstWrite: Permissions = { teams: [], teamgroups: [], users: [existingUserId] };
          const secondWrite: Permissions = { teams: [1], teamgroups: [], users: [] };

          return createExperiment(`${titlePrefix} first`, firstRead, 30, firstWrite, 20)
            .then(firstId => {
              entityIds.push(firstId);
              return createExperiment(`${titlePrefix} second`, secondRead, 20, secondWrite, 30);
            })
            .then(secondId => {
              entityIds.push(secondId);

              cy.visit(`/experiments.php?q=${encodeURIComponent(titlePrefix)}`);
              cy.get(`[data-entity-id="${entityIds[0]}"]`).should('exist');
              cy.get(`[data-entity-id="${entityIds[1]}"]`).should('exist');
              entityIds.forEach(id => {
                cy.get(`[data-entity-id="${id}"] input[data-action="checkbox-entity"]`).check();
              });

              cy.get('[data-cy="change-selected-read-permissions"]').click();
              cy.get('#permModal-canreadBatch').should('be.visible');
              selectUser('canreadBatch', 'Titi');
              selectUser('canreadBatch', 'Tutu');

              cy.intercept('PATCH', '**/api/v2/experiments/*').as('readPermissionPatch');
              cy.on('window:confirm', () => true);
              cy.get('#permModal-canreadBatch [data-cy="save-batch-permissions"]').click();
              cy.wait('@readPermissionPatch').then(({request}) => {
                expect(request.body).to.include({ action: 'addcanread' });
                expect(request.body).to.have.property('can');
                expect(request.body).not.to.have.property('canread');
                expect(request.body).not.to.have.property('canread_base');
              });
              cy.wait('@readPermissionPatch').then(({request}) => {
                expect(request.body).to.include({ action: 'addcanread' });
                expect(request.body).to.have.property('can');
                expect(request.body).not.to.have.property('canread');
                expect(request.body).not.to.have.property('canread_base');
              });

              cy.request(`/api/v2/experiments/${entityIds[0]}`).then(response => {
                expect(response.status).to.eq(200);
                expectPermissionIds(response.body.canread, {
                  teams: [1],
                  teamgroups: [],
                  users: [existingUserId, firstAddedUserId, secondAddedUserId],
                });
                expect(response.body.canread_base).to.eq(30);
              });
              cy.request(`/api/v2/experiments/${entityIds[1]}`).then(response => {
                expect(response.status).to.eq(200);
                expectPermissionIds(response.body.canread, {
                  teams: [],
                  teamgroups: [],
                  users: [firstAddedUserId, secondAddedUserId],
                });
                expect(response.body.canread_base).to.eq(20);
              });

              cy.get('[data-cy="change-selected-write-permissions"]').click();
              cy.get('#permModal-canwriteBatch').should('be.visible');
              selectUser('canwriteBatch', 'Titi');
              selectUser('canwriteBatch', 'Tutu');

              cy.intercept('PATCH', '**/api/v2/experiments/*').as('writePermissionPatch');
              cy.get('#permModal-canwriteBatch [data-cy="save-batch-permissions"]').click();
              cy.wait('@writePermissionPatch').then(({request}) => {
                expect(request.body).to.include({ action: 'addcanwrite' });
                expect(request.body).to.have.property('can');
                expect(request.body).not.to.have.property('canwrite');
                expect(request.body).not.to.have.property('canwrite_base');
              });
              cy.wait('@writePermissionPatch').then(({request}) => {
                expect(request.body).to.include({ action: 'addcanwrite' });
                expect(request.body).to.have.property('can');
                expect(request.body).not.to.have.property('canwrite');
                expect(request.body).not.to.have.property('canwrite_base');
              });

              cy.request(`/api/v2/experiments/${entityIds[0]}`).then(response => {
                expect(response.status).to.eq(200);
                expectPermissionIds(response.body.canwrite, {
                  teams: [],
                  teamgroups: [],
                  users: [existingUserId, firstAddedUserId, secondAddedUserId],
                });
                expect(response.body.canwrite_base).to.eq(20);
              });
              cy.request(`/api/v2/experiments/${entityIds[1]}`).then(response => {
                expect(response.status).to.eq(200);
                expectPermissionIds(response.body.canwrite, {
                  teams: [1],
                  teamgroups: [],
                  users: [firstAddedUserId, secondAddedUserId],
                });
                expect(response.body.canwrite_base).to.eq(30);
              });
            });
        }),
      ),
    );
  });

  it('preserves concurrent additions to the same permission list', () => {
    const title = 'Cypress concurrent bulk permissions ' + Date.now();

    return findUserId('titi@yopmail.com').then(firstAddedUserId =>
      findUserId('tutu@yopmail.com').then(secondAddedUserId =>
        createExperiment(
          title,
          { teams: [], teamgroups: [], users: [] },
          20,
          { teams: [], teamgroups: [], users: [] },
          20,
        ).then(entityId => {
          entityIds.push(entityId);
          cy.visit('/experiments.php?q=' + encodeURIComponent(title));
          return cy.get('meta[name="csrf-token"]').invoke('attr', 'content').then(csrf => {
            const permissionsFor = (userid: number) => JSON.stringify({
              teams: [],
              teamgroups: [],
              users: [userid],
            });
            return cy.window().then(win => {
              const patch = (userid: number) => win.fetch(
                '/api/v2/experiments/' + entityId,
                {
                  method: 'PATCH',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': String(csrf),
                    'X-Requested-With': 'XMLHttpRequest',
                  },
                  body: JSON.stringify({
                    action: 'addcanread',
                    can: permissionsFor(userid),
                  }),
                },
              ).then(response => {
                expect(response.status).to.eq(200);
              });

              return Cypress.Promise.all([patch(firstAddedUserId), patch(secondAddedUserId)]);
            });
          }).then(() => cy.request('/api/v2/experiments/' + entityId).then(response => {
            expect(response.status).to.eq(200);
            expectPermissionIds(response.body.canread, {
              teams: [],
              teamgroups: [],
              users: [firstAddedUserId, secondAddedUserId],
            });
          }));
        }),
      ),
    );
  });
});
