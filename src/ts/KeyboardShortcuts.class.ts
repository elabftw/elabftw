/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Todolist from './Todolist.class';
import { Api } from './Apiv2.class';
import { EntityType } from './interfaces';
import FavTag from './FavTag.class';
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
    import(/* webpackChunkName: "keymaster" */ '../js/vendor/keymaster.js')
      .then(({ default: key }) => {
        console.log(key.filter);
        // CREATE EXPERIMENT or DATABASE item with shortcut
        key(this.create, () => {
          // add current tags in there too
          const urlParams = new URLSearchParams(document.location.search);
          const tags = urlParams.getAll('tags[]');
          // use default template
          const params = {category_id: 0, tags: tags};
          this.api.post(EntityType.Experiment, params).then(resp => {
            const newId = getNewIdFromPostRequest(resp);
            window.location.href = `experiments.php?mode=edit&id=${newId}`;
          });
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
        key(this.todo, () => (new Todolist()).toggle());

        // FAVORITE TAGS TOGGLE
        key(this.favorite, () => (new FavTag()).toggle());

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
      });
  }
}
