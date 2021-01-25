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
import { getTinymceBaseConfig } from './tinymce';

$(document).ready(function() {
  if (window.location.pathname !== '/sysconfig.php') {
    return;
  }
  // TEAMS
  const Teams = {
    controller: 'app/controllers/SysconfigAjaxController.php',
    editUserToTeam(userid, action): void {
      $('#editUserToTeamUserid').attr('value', userid);
      $('#editUserToTeamAction').attr('value', action);
    },
    create: function(): void {
      const name = $('#teamsName').val();
      $.post(this.controller, {
        teamsCreate: true,
        teamsName: name
      }).done(function(data) {
        Teams.destructor(data);
      });
    },
    update: function(id): void {
      const name = $('#teamName_' + id).val();
      const orgid = $('#teamOrgid_' + id).val();
      const visible = $('#teamVisible_' + id).val();
      $.post(this.controller, {
        teamsUpdate: true,
        id : id,
        name : name,
        orgid : orgid,
        visible : visible,
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
    if (confirm(i18next.t('generic-delete-warning'))) {
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

  tinymce.init(getTinymceBaseConfig('sysconfig'));
});
