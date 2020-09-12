/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import i18next from 'i18next';
import tinymce from 'tinymce/tinymce';

export default class Template {
  controller: string;
  type: string;

  constructor() {
    this.type = 'experiments_templates';
    this.controller = 'app/controllers/EntityAjaxController.php';
  }

  create(name: string, body = ''): void {
    $.post(this.controller, {
      create: true,
      name: name,
      body: body,
      type: this.type,
    }).done(function(json) {
      notif(json);
      if (json.res) {
        window.location.replace(`ucp.php?tab=3&templateid=${json.msg}`);
      }
    });
  }

  saveToFile(id, name): void {
    // we have the name of the template used for filename
    // and we have the id of the editor to get the content from
    // we don't use activeEditor because it requires a click inside the editing area
    const content = tinymce.get(id).getContent();
    const blob = new Blob([content], {type: 'text/plain;charset=utf-8'});
    saveAs(blob, name + '.elabftw.tpl');
  }

  destroy(id): void {
    if (confirm(i18next.t('generic-delete-warning'))) {
      $.post(this.controller, {
        destroy: true,
        id: id,
        type: this.type,
      }).done(function(json) {
        notif(json);
        if (json.res) {
          window.location.replace('ucp.php?tab=3');
        }
      });
    }
  }
}
