/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any; // eslint-disable-line @typescript-eslint/no-explicit-any
import Todolist from './Todolist.class';
import { Api } from './Apiv2.class';
import { EntityType } from './interfaces';
import FavTag from './FavTag.class';
import $ from 'jquery';
import { getNewIdFromPostRequest } from './misc';

export class KeyboardShortcuts {

  api: Api;
  create: string;
  edit: string;
  todo: string;
  favorite: string;
  search: string;
  page: string;

  constructor(create: string, edit: string, todo: string, favorite: string, search: string) {
    this.create = create;
    this.edit = edit;
    this.todo = todo;
    this.favorite = favorite;
    this.search = search;
    this.api = new Api();
    this.page = document.location.pathname;
  }

  init() {

    // CREATE EXPERIMENT or DATABASE item with shortcut
    // could work from anywhere but limit it to experiments|database pages
    key(this.create, () => {
      if (this.page === '/database.php') {
        // for database items, show a selection modal
        // modal plugin requires jquery
        $('#createModal').modal('toggle');
        return;
      }

      // EXPERIMENTS
      if (this.page === '/experiments.php') {
        // add current tags in there too
        const urlParams = new URLSearchParams(document.location.search);
        const tags = urlParams.getAll('tags[]');
        // use default template
        const params = {'category_id': 0, 'tags': tags};
        this.api.post(EntityType.Experiment, params).then(resp => {
          const newId = getNewIdFromPostRequest(resp);
          window.location.href = `experiments.php?mode=edit&id=${newId}`;
        });
      }
    });

    // EDIT SHORTCUT
    key(this.edit, () => {
      const urlParams = new URLSearchParams(document.location.search);
      const id = parseInt(urlParams.get('id'), 10);
      if (isNaN(id)) {
        return;
      }
      window.location.href = `?mode=edit&id=${id}`;
    });

    // TODOLIST TOGGLE
    key(this.todo, () => {
      if (!['/experiments.php', '/database.php'].includes(this.page)) {
        return;
      }
      (new Todolist()).toggle();
    });

    // FAVORITE TAGS TOGGLE
    key(this.favorite, () => {
      if (!['/experiments.php', '/database.php'].includes(this.page)) {
        return;
      }
      (new FavTag()).toggle();
    });

    // SEARCH BAR FOCUS
    key(this.search, (event: Event) => {
      // search input might not be visible on some pages
      const qs = document.getElementById('quicksearchInput');
      if (qs) {
        // add this or the shortcut key gets written in the input
        event.preventDefault();
        qs.focus();
      }
    });
  }
}
