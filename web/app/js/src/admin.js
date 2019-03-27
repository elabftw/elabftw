/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  function tinyMceInitLight() {
    tinymce.init({
      mode: 'specific_textareas',
      editor_selector: 'mceditable',
      browser_spellcheck: true,
      content_css: 'app/css/tinymce.css',
      plugins: 'table textcolor searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak',
      pagebreak_separator: '<pagebreak>',
      toolbar1: 'undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | codesample | link | save',
      removed_menuitems: 'newdocument',
      image_caption: true,
      content_style: '.mce-content-body {font-size:10pt;}',
      language : $('#info').data('lang')
    });
  }

  $(document).ready(function() {
    const confirmText = $('#info').data('confirm');

    // activate editors in new item type and common template
    tinyMceInitLight();

    // TEAMGROUPS
    var TeamGroups = {
      controller: 'app/controllers/TeamGroupsController.php',
      create: function() {
        var name = $('#teamGroupCreate').val();
        if (name.length > 0) {
          $.post(this.controller, {
            teamGroupCreate: name
          }).done(function(json) {
            notif(json);
            if (json.res) {
              $('#team_groups_div').load('admin.php #team_groups_div');
              $('#teamGroupCreate').val('');
            }
          });
        }
      },
      update: function(action) {
        var user;
        var group;
        if (action === 'add') {
          user = $('#teamGroupUserAdd').val();
          group = $('#teamGroupGroupAdd').val();
        } else {
          user = $('#teamGroupUserRm').val();
          group = $('#teamGroupGroupRm').val();
        }
        $.post(this.controller, {
          teamGroupUpdate: true,
          action: action,
          teamGroupUser: user,
          teamGroupGroup: group
        }).done(function() {
          $('#team_groups_div').load('admin.php #team_groups_div');
        });
      },
      destroy: function(id) {
        if (confirm(confirmText)) {
          $.post(this.controller, {
            teamGroupDestroy: true,
            teamGroupGroup: id
          }).done(function() {
            $('#team_groups_div').load('admin.php #team_groups_div');
          });
        }
        return false;
      }
    };

    // TEAM GROUP
    $(document).on('click', '#teamGroupCreateBtn', function() {
      TeamGroups.create();
    });

    $(document).on('click', '#teamGroupGroupAddBtn', function() {
      TeamGroups.update('add');
    });

    $(document).on('click', '#teamGroupGroupRmBtn', function() {
      TeamGroups.update('rm');
    });

    $(document).on('click', '.teamGroupDelete', function() {
      TeamGroups.destroy($(this).data('id'));
    });

    // VALIDATE USERS
    $(document).on('click', '.usersValidate', function() {
      $(this).attr('disabled', 'disabled').text('Please wait…');
      $.post('app/controllers/UsersAjaxController.php', {
        usersValidate: true,
        userid: $(this).data('userid')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          window.location.reload();
        }
      });
    });

    // STATUS
    var Status = {
      controller: 'app/controllers/StatusController.php',
      create: function() {
        var name = $('#statusName').val();
        var color = $('#statusColor').val();
        var isTimestampable = +$('#statusTimestamp').is(':checked');

        $.post(this.controller, {
          statusCreate: true,
          name: name,
          color: color,
          isTimestampable: isTimestampable
        }).done(function(json) {
          notif(json);
          if (json.res) {
            window.location.replace('admin.php?tab=4');
          }
        });
      },
      update: function(id) {
        var name = $('#statusName_' + id).val();
        var color = $('#statusColor_' + id).val();
        var isTimestampable = +$('#statusTimestamp_'+ id).is(':checked');
        var isDefault = $('#statusDefault_' + id).is(':checked');

        $.post(this.controller, {
          statusUpdate: true,
          id: id,
          name: name,
          color: color,
          isTimestampable: isTimestampable,
          isDefault: isDefault
        }).done(function(json) {
          notif(json);
        });
      },
      destroy: function(id) {
        $.post(this.controller, {
          statusDestroy: true,
          id: id
        }).done(function(json) {
          notif(json);
          if (json.res) {
            $('#status_' + id).hide();
          }
        });
      }
    };
    $(document).on('click', '#statusCreate', function() {
      Status.create();
    });

    $(document).on('click', '.statusSave', function() {
      Status.update($(this).data('id'));
    });

    $(document).on('click', '.statusDestroy', function() {
      Status.destroy($(this).data('id'));
    });

    // ITEMSTYPES
    var ItemsTypes = {
      controller: 'app/controllers/ItemsTypesAjaxController.php',
      create: function() {
        var name = $('#itemsTypesName').val();
        var color = $('#itemsTypesColor').val();
        var checkbox = $('#itemsTypesBookable').is(':checked');
        var bookable = 0;
        if (checkbox) {
          bookable = 1;
        }
        var template = tinymce.get('itemsTypesTemplate').getContent();
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
      },
      showEditor: function(id) {
        $('#itemsTypesTemplate_' + id).addClass('mceditable');
        tinyMceInitLight();
        $('#itemsTypesEditor_' + id).toggle();
      },
      update: function(id) {
        var name = $('#itemsTypesName_' + id).val();
        var color = $('#itemsTypesColor_' + id).val();
        var checkbox = $('#itemsTypesBookable_' + id).is(':checked');
        var bookable = 0;
        if (checkbox) {
          bookable = 1;
        }
        var template = tinymce.get('itemsTypesTemplate_' + id).getContent();

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
      },
      destroy: function(id) {
        $.post(this.controller, {
          itemsTypesDestroy: true,
          id: id
        }).done(function(json) {
          notif(json);
          if (json.res) {
            $('#itemstypes_' + id).hide();
          }
        });
      }
    };
    $('.itemsTypesEditor').hide();
    $(document).on('click', '#itemsTypesCreate', function() {
      ItemsTypes.create();
    });
    $(document).on('click', '.itemsTypesShowEditor', function() {
      ItemsTypes.showEditor($(this).data('id'));
    });
    $(document).on('click', '.itemsTypesUpdate', function() {
      ItemsTypes.update($(this).data('id'));
    });
    $(document).on('click', '.itemsTypesDestroy', function() {
      ItemsTypes.destroy($(this).data('id'));
    });

    // COMMON TEMPLATE
    $('#commonTplTemplate').closest('div').find('.button').click(function() {
      var template = tinymce.get('commonTplTemplate').getContent();
      $.post('app/controllers/AjaxController.php', {
        commonTplUpdate: template
      }).done(function(json) {
        notif(json);
      });
    });

    // validate on enter
    $('#teamGroupCreate').keypress(function (e) {
      let keynum;
      if (e.which) {
        keynum = e.which;
      }
      if (keynum === 13) { // if the key that was pressed was Enter (ascii code 13)
        TeamGroups.create();
      }
    });
    // edit the team group name
    $('h3.teamgroup_name').editable('app/controllers/TeamGroupsController.php', {
      indicator : 'Saving...',
      name : 'teamGroupUpdateName',
      submit : 'Save',
      cancel : 'Cancel',
      cancelcssclass : 'button button-delete',
      submitcssclass : 'button',
      style : 'display:inline'

    });

    // COLORPICKER
    $('.colorpicker').colorpicker({
      hsv: false,
      okOnEnter: true,
      rgb: false
    });

    // randomize the input of the color picker so even if user doesn't change the color it's a different one!
    // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
    var colorInput = Math.floor(Math.random()*16777215).toString(16);
    $('#itemsTypesColor').val(colorInput);
    $('#statusColor').val(colorInput);

    $('.tag-editable').editable(function(value) {
      $.post('app/controllers/TagsController.php', {
        update: true,
        newtag: value,
        tag: $(this).data('tag')
      });

      return(value);
    }, {
      tooltip : 'Click to edit',
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline'
    });
  });
}());
