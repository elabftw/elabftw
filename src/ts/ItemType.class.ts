/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif, tinyMceInitLight } from './misc';
import i18next from 'i18next';
import tinymce from 'tinymce/tinymce';

export default class ItemType {
  controller: string;

  constructor() {
    this.controller = 'app/controllers/ItemsTypesAjaxController.php';
  }

  create(): void {
    const name = $('#itemsTypesName').val();
    if (name === '') {
      notif({'res': false, 'msg': 'Name cannot be empty'});
      $('#itemsTypesName').css('border-color', 'red');
      return;
    }
    const color = $('#itemsTypesColor').val();
    const checkbox = $('#itemsTypesBookable').is(':checked');
    let bookable = 0;
    if (checkbox) {
      bookable = 1;
    }

    const template = tinymce.get('itemsTypesTemplate').getContent();

    $.post(this.controller, {
      itemsTypesCreate: true,
      name: name,
      color: color,
      bookable: bookable,
      template: template
    }).done(function(json) {
      notif(json);
      if (json.res) {
        window.location.replace('admin.php?tab=5');
      }
    });
  }

  showEditor(id): void {
    $('#itemsTypesTemplate_' + id).addClass('mceditable');
    tinyMceInitLight();
    $('#itemsTypesEditor_' + id).toggle();
  }

  update(id): void {
    const name = $('#itemsTypesName_' + id).val();
    const color = $('#itemsTypesColor_' + id).val();
    const checkbox = $('#itemsTypesBookable_' + id).is(':checked');
    let bookable = 0;
    if (checkbox) {
      bookable = 1;
    }
    // if tinymce is hidden, it'll fail to trigger
    // so we toggle it quickly to grab the content
    if ($('#itemsTypesTemplate_' + id).is(':hidden')) {
      this.showEditor(id);
    }
    const template = tinymce.get('itemsTypesTemplate_' + id).getContent();
    $('#itemsTypesEditor_' + id).toggle();

    $.post(this.controller, {
      itemsTypesUpdate: true,
      id: id,
      name: name,
      color: color,
      bookable: bookable,
      template: template
    }).done(function(json) {
      notif(json);
    });
  }

  destroy(id): void {
    $.post(this.controller, {
      itemsTypesDestroy: true,
      id: id
    }).done(function(json) {
      notif(json);
      if (json.res) {
        $('#itemstypes_' + id).hide();
        $('#itemstypesOrder_' + id).hide();
      }
    });
  }
}
