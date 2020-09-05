/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import i18next from 'i18next';

export default class Tag {
  controller: string;
  type: string;

  constructor(type: string) {
    this.type = type;
    this.controller = 'app/controllers/TagsController.php';
  }

  // REFERENCE A TAG
  save(tag: string, itemId: number): void {
    // POST request
    $.post(this.controller, {
      createTag: true,
      tag: tag,
      itemId: itemId,
      type: this.type
    }).done(function(json) {
      notif(json);
      $('#tags_div_' + itemId).load(window.location.href + ' #tags_div_' + itemId);
    });
  }

  // REMOVE THE TAG FROM AN ENTITY
  unreference(tagId: number, itemId: number): void {
    if (confirm(i18next.t('tag-delete-warning'))) {
      $.post(this.controller, {
        unreferenceTag: true,
        type: this.type,
        itemId: itemId,
        tagId: tagId
      }).done(function() {
        $('#tags_div_' + itemId).load(window.location.href + ' #tags_div_' + itemId);
      });
    }
  }

  // DEDUPLICATE
  deduplicate(): void {
    $.post(this.controller, {
      deduplicate: true,
    }).done(function(json) {
      notif(json);
      $('#tag_manager').load(window.location.href + ' #tag_manager > *');
    });
  }

  // REMOVE A TAG COMPLETELY (from admin panel/tag manager)
  destroy(tagId: number): void {
    if (confirm(i18next.t('tag-delete-warning'))) {
      $.post(this.controller, {
        destroyTag: true,
        tagId: tagId
      }).done(function(json) {
        notif(json);
        $('#tag_manager').load(window.location.href + ' #tag_manager > *');
      });
    }
  }
}
