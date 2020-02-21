/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import tinymce from 'tinymce/tinymce';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/hr';
import 'tinymce/plugins/image';
import 'tinymce/plugins/imagetools';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/paste';
import 'tinymce/plugins/save';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/template';
import 'tinymce/themes/silver';
import 'tinymce/themes/mobile';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });
  // TEAMS
  const Teams = {
    controller: 'app/controllers/SysconfigAjaxController.php',
    editUserToTeam(userid, action): void {
      $('#editUserToTeamUserid').attr('value', userid);
      $('#editUserToTeamAction').attr('value', action);
    },
    create: function(): void {
      (document.getElementById('teamsCreateButton') as HTMLButtonElement).disabled = true;
      const name = $('#teamsName').val();
      $.post(this.controller, {
        teamsCreate: true,
        teamsName: name
      }).done(function(data) {
        Teams.destructor(data);
      });
    },
    update: function(id): void {
      (document.getElementById('teamsUpdateButton_' + id) as HTMLButtonElement).disabled = true;
      const name = $('#teamName_' + id).val();
      const orgid = $('#teamOrgid_' + id).val();
      $.post(this.controller, {
        teamsUpdate: true,
        teamsUpdateId : id,
        teamsUpdateName : name,
        teamsUpdateOrgid : orgid
      }).done(function(data) {
        Teams.destructor(data);
      });
    },
    destroy: function(id): void {
      (document.getElementById('teamsDestroyButton_' + id) as HTMLButtonElement).disabled = true;
      $.post(this.controller, {
        teamsDestroy: true,
        teamsDestroyId: id
      }).done(function(data) {
        Teams.destructor(data);
      });
    },
    destructor: function(json): void {
      notif(json);
      if (json.res) {
        $('#teamsDiv').load('sysconfig.php #teamsDiv');
      }
    }
  };

  $(document).on('keyup', '.teamNameInput', function() {
    (document.getElementById('teamsUpdateButton_' + $(this).data('id')) as HTMLButtonElement).disabled = false;
  });

  $(document).on('click', '#teamsCreateButton', function() {
    Teams.create();
  });
  $(document).on('click', '.teamsUpdateButton', function() {
    Teams.update($(this).data('id'));
  });
  $(document).on('click', '.teamsDestroyButton', function() {
    Teams.destroy($(this).data('id'));
  });
  $(document).on('click', '.teamsArchiveButton', function() {
    notif({'msg': 'Feature not yet implemented :)', 'res': true});
  });
  $(document).on('click', '.editUserToTeam', function() {
    Teams.editUserToTeam($(this).data('userid'), $(this).data('action'));
  });

  // MAIL METHOD in a function because is also called in document ready
  function toggleMailMethod(method): void {
    switch (method) {
    case 'sendmail':
      $('#smtp_config').hide();
      $('#sendmail_config').show();
      break;
    case 'smtp':
      $('#smtp_config').show();
      $('#sendmail_config').hide();
      break;
    default:
      $('#smtp_config').hide();
      $('#sendmail_config').hide();
      $('#general_mail_config').hide();
    }
  }
  $(document).on('change', '#selectMailMethod', function() {
    toggleMailMethod($(this).val());
  });

  // MASS MAIL
  $(document).on('click', '#massSend', function() {
    $('#massSend').prop('disabled', true);
    $('#massSend').text('Sending…');
    $.post('app/controllers/SysconfigAjaxController.php', {
      massEmail: true,
      subject: $('#massSubject').val(),
      body: $('#massBody').val()
    }).done(function(json) {
      notif(json);
      if (json.res) {
        $('#massSend').text('Sent!');
      } else {
        $('#massSend').prop('disabled', false);
        $('#massSend').css('background-color', '#e6614c');
        $('#massSend').text('Error');
      }
    });
  });

  // TEST EMAIL
  $(document).on('click', '#testemailButton', function() {
    const email = $('#testemailEmail').val();
    (document.getElementById('testemailButton') as HTMLButtonElement).disabled = true;
    $('#testemailButton').text('Sending…');
    $.post('app/controllers/SysconfigAjaxController.php', {
      testemailSend: true,
      testemailEmail: email
    }).done(function(json) {
      notif(json);
      if (json.res) {
        $('#massSend').text('Sent!');
        (document.getElementById('testemailButton') as HTMLButtonElement).disabled = false;
      } else {
        $('#testemailButton').text('Error');
        $('#testemailButton').css('background-color', '#e6614c');
      }
    });
  });

  $(document).on('click', '#editSmtpPassword', function() {
    $('#hidden_smtp_password').toggle();
  });

  // we need to add this otherwise the button will stay disabled with the browser's cache (Firefox)
  const inputList = document.getElementsByTagName('input');
  for (let i=0; i < inputList.length; i++) {
    const input = inputList[i];
    input.disabled = false;
  }
  // honor already saved mail_method setting and hide unused options accordingly
  toggleMailMethod($('#selectMailMethod').val());

  $(document).on('click', '.idpsDestroy', function() {
    const elem = $(this);
    if (confirm($(this).data('confirm'))) {
      $.post('app/controllers/SysconfigAjaxController.php', {
        idpsDestroy: true,
        id: $(this).data('id')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          elem.closest('div').hide(600);
        }
      });
    }
  });

  tinymce.init({
    mode: 'specific_textareas',
    editor_selector: 'mceditable', // eslint-disable-line @typescript-eslint/camelcase
    browser_spellcheck: true, // eslint-disable-line @typescript-eslint/camelcase
    skin_url: 'app/css/tinymce', // eslint-disable-line @typescript-eslint/camelcase
    height: '500',
    plugins: 'table searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak hr',
    pagebreak_separator: '<pagebreak>', // eslint-disable-line @typescript-eslint/camelcase
    toolbar1: 'undo redo | styleselect bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | codesample | link',
    removed_menuitems: 'newdocument, image', // eslint-disable-line @typescript-eslint/camelcase
    image_caption: false, // eslint-disable-line @typescript-eslint/camelcase
    content_style: '.mce-content-body {font-size:10pt;}', // eslint-disable-line @typescript-eslint/camelcase
    language: 'en_GB'
  });
});
