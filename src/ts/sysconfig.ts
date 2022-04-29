/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif, reloadElement } from './misc';
import { Action, Method, Payload, Model } from './interfaces';
import i18next from 'i18next';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';
import Tab from './Tab.class';
import { Ajax } from './Ajax.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/sysconfig.php') {
    return;
  }

  const TabMenu = new Tab();
  const AjaxC = new Ajax();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  // GET the latest version information
  const updateUrl = 'https://get.elabftw.net/updates.json';
  const currentVersionDiv = document.getElementById('currentVersion') as HTMLElement;
  const latestVersionDiv = document.getElementById('latestVersion');
  const currentVersion = currentVersionDiv.innerText;
  // Note: this doesn't work on Chrome
  // see: https://bugs.chromium.org/p/chromium/issues/detail?id=571722
  // normal user-agent will be sent
  const headers = new Headers({
    'User-Agent': 'Elabftw/' + currentVersion,
  });

  fetch(updateUrl, {
    headers: headers,
  }).then(response => {
    if (!response.ok) {
      throw new Error('Error fetching latest version!');
    }
    return response.json();
  }).then(data => {
    latestVersionDiv.append(data.version);
    // get versions as number only so we can compare properly
    const numOnlyLatest = data.version.replace(/\D/g, '');
    const numOnlyCurrent = currentVersion.replace(/\D/g, '');
    if ((data.version === currentVersion) || (numOnlyCurrent > numOnlyLatest)) {
      // show a little green check if we have latest version
      const successIcon = document.createElement('i');
      successIcon.style.color = 'green';
      successIcon.classList.add('fas', 'fa-check', 'fa-lg', 'align-top', 'ml-1');
      latestVersionDiv.appendChild(successIcon);
    } else {
      currentVersionDiv.style.color = 'red';
      const warningDiv = document.createElement('div');
      warningDiv.classList.add('alert', 'alert-warning');
      const chevron = document.createElement('i');
      chevron.classList.add('fas', 'fa-chevron-right');
      warningDiv.appendChild(chevron);
      const text = document.createElement('span');
      text.classList.add('ml-1');
      text.innerText = `${data.date} - A new version is available!`;
      warningDiv.appendChild(text);
      const updateLink = document.createElement('a');
      updateLink.href = 'https://doc.elabftw.net/how-to-update.html';
      updateLink.classList.add('button', 'btn', 'btn-primary', 'text-white');
      updateLink.innerText = 'Update elabftw';
      const changelogLink = document.createElement('a');
      changelogLink.href = 'https://doc.elabftw.net/changelog.html';
      changelogLink.classList.add('button', 'btn', 'btn-primary', 'text-white');
      changelogLink.innerText = 'Read changelog';
      warningDiv.appendChild(updateLink);
      warningDiv.appendChild(changelogLink);
      document.getElementById('versionNotifZone').appendChild(warningDiv);
    }
  }).catch(error => latestVersionDiv.append(error));


  // TEAMS
  const Teams = {
    controller: 'app/controllers/SysconfigAjaxController.php',
    editUser2Team(action: Action, teamid: number, userid: number): void {
      const payload: Payload = {
        method: Method.POST,
        action: action,
        model: Model.User2Team,
        notif: true,
        extraParams: {
          teamid: teamid,
          userid: userid,
        },
      };
      AjaxC.send(payload)
        .then(json => {
          notif(json);
          reloadElement('editUsersBox');
        });
    },
    create: function(): void {
      const name = (document.getElementById('teamsName') as HTMLInputElement).value;
      $.post(this.controller, {
        teamsCreate: true,
        teamsName: name,
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
        teamsDestroyId: id,
      }).done(function(data) {
        Teams.destructor(data);
      });
    },
    destructor: function(json): void {
      notif(json);
      if (json.res) {
        reloadElement('teamsDiv');
      }
    },
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

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CLEAR-LOCKEDUSERS and CLEAR-LOCKOUTDEVICES
    if (el.matches('[data-action="clear-nologinusers"]') || el.matches('[data-action="clear-lockoutdevices"]')) {
      AjaxC.postForm('app/controllers/SysconfigAjaxController.php', { [el.dataset.action]: '1' })
        .then(res => res.json().then(json => {
          if (json.res) {
            reloadElement('bruteforceDiv');
          }
          notif(json);
        }));

    // ADD USER TO TEAM
    } else if (el.matches('[data-action="create-user2team"]')) {
      const selectEl = (el.previousElementSibling as HTMLSelectElement);
      Teams.editUser2Team(
        Action.Create,
        parseInt(selectEl.options[selectEl.selectedIndex].value, 10),
        parseInt(el.dataset.userid, 10),
      );
    // REMOVE USER FROM TEAM
    } else if (el.matches('[data-action="destroy-user2team"]')) {
      if (!confirm(i18next.t('generic-delete-warning'))) {
        return;
      }
      Teams.editUser2Team(
        Action.Destroy,
        parseInt(el.dataset.teamid, 10),
        parseInt(el.dataset.userid, 10),
      );
    }
  });

  /**
   * Timestamp provider select
   */
  const noAccountTsa = ['dfn', 'digicert', 'sectigo', 'globalsign'];
  if (document.getElementById('ts_authority')) {
    const select = (document.getElementById('ts_authority') as HTMLSelectElement);
    // trigger the function when the value is changed
    select.addEventListener('change', () => {
      updateTsFieldsVisibility(select);
    });
    // and also on page load
    updateTsFieldsVisibility(select);
  }

  function updateTsFieldsVisibility(select: HTMLSelectElement) {
    if (noAccountTsa.includes(select.value)) {
      // mask all
      document.getElementById('ts_loginpass').toggleAttribute('hidden', true);
      document.getElementById('ts_urldiv').toggleAttribute('hidden', true);
    } else if (select.value === 'universign') {
      // only make loginpass visible
      document.getElementById('ts_loginpass').removeAttribute('hidden');
      document.getElementById('ts_urldiv').toggleAttribute('hidden', true);
    } else if (select.value === 'custom') {
      // show all
      document.getElementById('ts_loginpass').removeAttribute('hidden');
      document.getElementById('ts_urldiv').removeAttribute('hidden');
    }
  }

  // MASS MAIL
  $(document).on('click', '#massSend', function() {
    $('#massSend').prop('disabled', true);
    $('#massSend').text('Sending…');
    $.post('app/controllers/SysconfigAjaxController.php', {
      massEmail: true,
      subject: $('#massSubject').val(),
      body: $('#massBody').val(),
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
      testemailEmail: email,
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

  $(document).on('click', '.idpsDestroy', function() {
    const elem = $(this);
    if (confirm(i18next.t('generic-delete-warning'))) {
      $.post('app/controllers/SysconfigAjaxController.php', {
        idpsDestroy: true,
        id: $(this).data('id'),
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
