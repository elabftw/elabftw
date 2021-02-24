/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import i18next from 'i18next';

export default class Tag extends Crud {
  type: string;

  constructor(type: string) {
    super('app/controllers/Ajax.php');
    this.type = type;
  }

  // REFERENCE A TAG
  save(tag: string, itemId: number): void {
    // POST request
    this.send({
      action: 'create',
      what: 'tag',
      type: this.type,
      params: {
        tag: tag,
        itemId: itemId,
      },
    }).then(() => {
      $('#tags_div_' + itemId).load(window.location.href + ' #tags_div_' + itemId);
    });
  }

  // REMOVE THE TAG FROM AN ENTITY
  unreference(tagId: number, itemId: number): void {
    if (confirm(i18next.t('tag-delete-warning'))) {
      this.send({
        action: 'unreference',
        what: 'tag',
        type: this.type,
        params: {
          id: tagId,
          itemId: itemId,
        },
      }).then(() => {
        $('#tags_div_' + itemId).load(window.location.href + ' #tags_div_' + itemId);
      });
    }
  }

  // DEDUPLICATE
  deduplicate(): void {
    this.send({
      action: 'deduplicate',
      what: 'tag',
    }).then(() => {
      $('#tag_manager').load(window.location.href + ' #tag_manager > *');
    });
  }

  // REMOVE A TAG COMPLETELY (from admin panel/tag manager)
  destroy(tagId: number): void {
    if (confirm(i18next.t('tag-delete-warning'))) {
      this.send({
        action: 'destroy',
        what: 'tag',
        params: {
          id: tagId,
        },
      }).then(() => {
        $('#tag_manager').load(window.location.href + ' #tag_manager > *');
      });
    }
  }
}
