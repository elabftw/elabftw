/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-jeditable/src/jquery.jeditable.js';
import '@fancyapps/fancybox/dist/jquery.fancybox.js';
import { Payload, Method, Model, Target, Type, Entity, Action } from './interfaces';
import { notif, displayMolFiles, display3DMolecules } from './misc';
import i18next from 'i18next';
import { Ajax } from './Ajax.class';

$(document).ready(function() {
  const pages = ['edit', 'view'];
  if (!pages.includes($('#info').data('page'))) {
    return;
  }
  displayMolFiles();
  display3DMolecules();

  // REPLACE UPLOAD toggle form
  $(document).on('click', '.replaceUpload', function() {
    $(this).next('.replaceUploadForm').toggle();
  });

  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;
  let entityType: Type;
  if (about.type === 'experiments') {
    entityType = Type.Experiment;
  }
  if (about.type === 'items') {
    entityType = Type.Item;
  }

  // make file comments editable
  $(document).on('mouseenter', '.file-comment', function() {
    ($('.editable') as any).editable(function(value: string) {
      const AjaxC = new Ajax();
      const payload: Payload = {
        method: Method.POST,
        action: Action.Update,
        model: Model.Upload,
        target: Target.Comment,
        entity: {
          type: entityType,
          id: $(this).data('itemid'),
        },
        content: value,
        id : $(this).data('id'),
      };
      AjaxC.send(payload);

      return(value);
    }, {
      tooltip : i18next.t('upload-file-comment'),
      placeholder: i18next.t('upload-file-comment'),
      name : 'fileComment',
      onedit: function() {
        if ($(this).text() === 'Click to add a comment') {
          $(this).text('');
        }
      },
      onblur : 'submit',
      style : 'display:inline',
    });
  });

  // Export mol in png
  $(document).on('click', '.saveAsImage', function() {
    const molCanvasId = $(this).parent().siblings().find('canvas').attr('id');
    const png = (document.getElementById(molCanvasId) as any).toDataURL();
    $.post('app/controllers/EntityAjaxController.php', {
      saveAsImage: true,
      realName: $(this).data('name'),
      content: png,
      id: $('#info').data('id'),
      type: $('#info').data('type')
    }).done(function(json) {
      notif(json);
      if (json.res) {
        $('#filesdiv').load('?mode=edit&id=' + $('#info').data('id') + ' #filesdiv > *', function() {
          displayMolFiles();
        });
      }
    });
  });

  // DESTROY UPLOAD
  $(document).on('click', '.uploadsDestroy', function() {
    const itemid = $(this).data('itemid');
    if (confirm($(this).data('msg'))) {
      $.post('app/controllers/EntityAjaxController.php', {
        uploadsDestroy: true,
        uploadId: $(this).data('id'),
        id: itemid,
        type: $(this).data('type')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#filesdiv').load('?mode=edit&id=' + itemid + ' #filesdiv > *', function() {
            displayMolFiles();
            display3DMolecules(true);
          });
        }
      });
    }
  });

  // ACTIVATE FANCYBOX
  $('[data-fancybox]').fancybox();
});
