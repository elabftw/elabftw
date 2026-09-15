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
      .find('.scheduler-recurrence-enabled')
      .should('have.length', 1);
    cy.htmlvalidate();
  });

  it('Configures recurring bookings from the booking form', () => {
    cy.visit('/scheduler.php');
    cy.get('#itemPickerSelectModal')
      .invoke('addClass', 'show')
      .invoke('css', 'display', 'block');
    cy.get('#itemPickerSelectModal').within(() => {
      cy.get('.scheduler-recurrence-options').should('not.be.visible');
      cy.get('.scheduler-recurrence-enabled').check({ force: true });
      cy.get('.scheduler-recurrence-options').should('be.visible');

      cy.get('.scheduler-recurrence-interval-mode').select('custom');
      cy.get('.scheduler-recurrence-interval').should('be.visible').clear().type('3');
      cy.get('.scheduler-recurrence-frequency').select('weekly');
      cy.get('.scheduler-recurrence-weekly').should('be.visible');

      cy.get('.scheduler-recurrence-weekday-mode').select('custom');
      cy.get('.scheduler-recurrence-weekdays').should('be.visible');
      cy.get('.scheduler-recurrence-weekday[value="2"]').check();
      cy.get('.scheduler-recurrence-weekday[value="4"]').check();

      cy.get('.scheduler-recurrence-end-mode').select('date');
      cy.get('.scheduler-recurrence-count-wrapper').should('not.be.visible');
      cy.get('.scheduler-recurrence-until-wrapper').should('be.visible');
    });
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
          expect(first.recurrence_frequency).to.equal('daily');
          expect(Number(first.recurrence_interval)).to.equal(1);
          expect(first.recurrence_rule).to.deep.equal({
            frequency: 'daily',
            interval: 1,
            count: 3,
          });

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
