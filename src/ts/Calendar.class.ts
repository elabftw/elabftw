/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { Api } from './Apiv2.class';
import { getNewIdFromPostRequest, relativeMoment, reloadElements, TomSelect } from './misc';
import i18next from 'i18next';

export default class Calendar {

  private page: string;
  private ApiC: Api;

  constructor() {
    this.page = window.location.pathname;
    this.ApiC = new Api();

    this.addResetModalEventListener();
    this.initializeTomSelect();
  }

  public async create() {
    // gather data from inputs
    const title = (document.getElementById('calendarTitle') as HTMLInputElement).value;
    const allTeamEvents = (document.getElementById('calendarTeam') as HTMLInputElement).checked;
    let todo = false;
    let UnfinishedStepsScope = 'none';
    if (this.page === '/profile.php') {
      todo = (document.getElementById('calendarTodo') as HTMLInputElement).checked;
      UnfinishedStepsScope = (document.querySelector('input[name="calendarUnfinishedSteps"]:checked') as HTMLInputElement).value;
    }
    let categories: number[] = [];
    let items: number[] = [];

    const getSelectedOptions = (id: string): number[] =>
      Array.from((document.getElementById(id) as HTMLSelectElement).options)
        .filter(option => option.selected)
        .map(option => parseInt(option.value, 10));
    if (!allTeamEvents) {
      categories = getSelectedOptions('calendarSelectCat');
      items = getSelectedOptions('calendarSelectItem');
    }

    // check if there is any content
    if (!allTeamEvents
        && categories.length === 0
        && items.length === 0
        && (this.page === '/team.php'
            || (this.page === '/profile.php'
                && todo
                && UnfinishedStepsScope === 'none'
            )
        )
    ) {
      alert(i18next.t('nothing-selected'));
      return;
    }

    // prepare post request params
    const postParams = {
      title,
      'all_events': allTeamEvents,
      categories,
      items,
      todo,
      'unfinished_steps_scope': UnfinishedStepsScope,
    };

    const postResult = await this.ApiC.post('calendars', postParams);
    const getResponse = await this.ApiC.get(`calendars/${getNewIdFromPostRequest(postResult)}`);
    const calendarData = await getResponse.json();
    (document.getElementById('calendarFeedUrl') as HTMLInputElement).value = `${window.location.origin}/calendar.php?token=${calendarData.token}`;
    document.getElementById('calendarUrlDisplay').hidden = false;
    if (this.page === '/profile.php') {
      await reloadElements(['calendarTable']);
      relativeMoment();
    }
  }

  private addResetModalEventListener(): void {
    $('#calendarModal').on('hidden.bs.modal', () => {
      (document.getElementById('calendarTitle') as HTMLInputElement).value = '';
      (document.getElementById('calendarTeam') as HTMLInputElement).checked = false;
      (document.getElementById('calendarSelectCat') as HTMLSelectElement).selectedIndex = -1;
      // todo: figure out how to use tomselect type
      // @ts-expect-error tomselect
      document.getElementById('calendarSelectCat').tomselect.clear();
      (document.getElementById('calendarSelectItem') as HTMLSelectElement).selectedIndex = -1;
      // todo: figure out how to use tomselect type
      // @ts-expect-error tomselect
      document.getElementById('calendarSelectItem').tomselect.clear();

      if (this.page === '/profile.php') {
        (document.getElementById('calendarTodo') as HTMLInputElement).checked = false;
        (document.getElementById('calendarUnfinishedSteps') as HTMLInputElement).checked = true;
      }

      (document.getElementById('calendarFeedUrl') as HTMLInputElement).value = '';
      document.getElementById('calendarUrlDisplay').hidden = true;

    });
  }

  private initializeTomSelect(): void {
    ['calendarSelectCat', 'calendarSelectItem'].forEach(id => {
      new TomSelect(`#${id}`, {
        plugins: [
          'dropdown_input',
          'remove_button',
          'checkbox_options',
          'clear_button',
          'no_active_items',
        ],
      });
    });
  }
}
