import { DateTime } from 'luxon';

describe('Scheduler', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Displays Scheduler page', () => {
    cy.visit('/scheduler.php');
    cy.get('h1#pageTitle').should('have.text', 'Scheduler');
    cy.get('#loading-spinner').should('not.exist');
    cy.get('#itemPickerSelectModal')
      .find('.scheduler-recurrence-frequency')
      .should('have.length', 1);
    cy.htmlvalidate();
  });

  it('Display Scheduler with selected item', () => {
    // TODO: itemId currently has no real active use
    cy.request('PATCH', '/api/v2/users/me', { scope_items: 2 });
    cy.createBooking().then(itemId => {
      cy.visit(`/scheduler.php?items[]=${itemId}`);
      cy.get('#loading-spinner').should('not.exist');
      cy.get('#scopeBtn').should('not.be.visible');
      cy.get('#scopeItemsLocked').should('be.visible')
        .find('button').should('be.disabled')
        .find('i').should('have.class', 'fa-globe');
      cy.get('[data-cy="selectedItemsDisplay"]').should('contain', 'Cypress booked resource');
    });
  });

  it('Creates and cancels a finite recurring series', () => {
    cy.createResource().then(response => {
      cy.extractIdFromLocation(response).then(itemId => {
        cy.editResource({ itemId, body: { is_bookable: 1 } });
        // start at a later time just in case
        const start = DateTime.local()
          .plus({ days: 2 })
          .set({ hour: 10, minute: 0, second: 0, millisecond: 0 });

        cy.request('POST', `/api/v2/events/${itemId}`, {
          title: 'Recurring Cypress booking',
          start: start.toFormat('yyyy-MM-dd HH:mm:ss'),
          end: start.plus({ hours: 1 }).toFormat('yyyy-MM-dd HH:mm:ss'),
          // repeat once a day for three occurrences
          recurrence: {
            frequency: 'daily',
            interval: 1,
            count: 3,
          },
        }).its('status').should('eq', 201);

        cy.request('GET', `/api/v2/events/${itemId}`).then(eventsResponse => {
          expect(eventsResponse.body).to.have.length(3);
          const [first, second, third] = eventsResponse.body;
          expect(first.recurrence_series_id).to.equal(third.recurrence_series_id);

          // delete only one
          cy.request('DELETE', `/api/v2/event/${first.id}`)
            .its('status')
            .should('eq', 204);

          // delete all remaining occurrences in one shot
          cy.request('DELETE', `/api/v2/event/${second.id}?scope=series`)
            .its('status')
            .should('eq', 204);
        });

        cy.request('GET', `/api/v2/events/${itemId}`)
          .its('body')
          .should('have.length', 0);
      });
    });
  });
});
