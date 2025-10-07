/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Todolist from './Todolist.class';
import { ApiC } from './api';
import { EntityType } from './interfaces';
import FavTag from './FavTag.class';
import { assignKey } from './keymaster';

export class KeyboardShortcuts {

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
    this.page = document.location.pathname;
  }

  init() {
    // CREATE EXPERIMENT or DATABASE item with shortcut
    assignKey(this.create, () => {
      // add current tags in there too
      const urlParams = new URLSearchParams(document.location.search);
      const tags = urlParams.getAll('tags[]');
      // use default template
      const params = {category_id: 0, tags: tags};
      let entityType = EntityType.Experiment;
      if (document.location.pathname === '/database.php') {
        entityType = EntityType.Item;
      }
      ApiC.post2location(entityType, params).then(id => {
        window.location.href = `?mode=edit&id=${id}`;
      });
    });

    // EDIT SHORTCUT
    assignKey(this.edit, () => {
      const urlParams = new URLSearchParams(document.location.search);
      const id = parseInt(urlParams.get('id'), 10);
      if (isNaN(id)) {
        return;
      }
      // if already in edit mode, do nothing
      if ((urlParams.get('mode') || '').toLowerCase() === 'edit') return;
      window.location.href = `?mode=edit&id=${id}`;
    });

    // TODOLIST TOGGLE
    assignKey(this.todo, () => (new Todolist()).toggle());

    // FAVORITE TAGS TOGGLE
    assignKey(this.favorite, () => (new FavTag()).toggle());

    // SEARCH BAR FOCUS
    assignKey(this.search, (event: Event) => {
      // search input might not be visible on some pages
      const qs = document.getElementById('extendedArea');
      if (qs) {
        // add this or the shortcut key gets written in the input
        event.preventDefault();
        qs.scrollIntoView({ behavior: 'smooth' });
        qs.focus();
      }
    });
  }
}
