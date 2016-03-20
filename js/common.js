/*
 * Common functions used by eLabFTW
 * http://www.elabftw.net
 */

// The main function to delete stuff
// id of the item you want to delete, its type, the message info you want to say, the url you want to redirect to
function deleteThis(id, type, redirect) {
    var you_sure = confirm('Delete this ?');
    if (you_sure === true) {
        $.post('app/delete.php', {
            id:id,
            type:type
        })
        .success(function() {
            window.location = redirect;
        });
    } else {
        return false;
    }
}

// for editXP/DB, ctrl-shift-D will add the date
function addDateOnCursor() {
    var todayDate = new Date();
    var year = todayDate.getFullYear();
    // we use +1 on the month because january is 0
    var month = todayDate.getMonth() + 1;
    // we want to have two digits on the month
    if (month < 10) {
        month = "0" + month;
    }
    var day = todayDate.getDate();
    // we want to have two digits on the day
    if (day < 10) {
        day = "0" + day;
    }

    tinyMCE.activeEditor.execCommand('mceInsertContent', false, year + "-" + month + "-" + day + " ");
}

// notifications
function notif(text, cssClass) {
    var htmlText = '<p>' + text + '</p>';
    if (cssClass == 'ok') {
        overlayClass = 'overlay-ok';
    } else {
        overlayClass = 'overlay-ko';
    }
    var overlay = document.createElement('div');
       overlay.setAttribute('id','overlay');
       overlay.setAttribute('class', 'overlay ' + overlayClass);
       // show the overlay
       document.body.appendChild(overlay);
       // add text inside
       document.getElementById('overlay').innerHTML = htmlText;
       // wait a bit and make it disappear
       window.setTimeout(removeNotif, 2733);
}

function removeNotif() {
    $('#overlay').fadeOut(763, function() {
        $(this).remove();
    });
}


/* for menus on team, admin, sysconfig and ucp */

/* parse the $_GET from the url */
function getGetParameters() {
    var prmstr = window.location.search.substr(1);
    return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
}

/* put the $_GET in array */
function transformToAssocArray( prmstr ) {
    var params = {};
    var prmarr = prmstr.split("&");
    for ( var i = 0; i < prmarr.length; i++) {
        var tmparr = prmarr[i].split("=");
                params[tmparr[0]] = tmparr[1];
            }
    return params;
}
/* to check if the param is good */
function isInt(n) {
    return n % 1 === 0;
}

// To show the todolist
function showPanel() {
    var panel = $('#slide-panel');
    if (panel.css('display') == 'none') {
        panel.css('display', 'inline');
    } else {
        panel.css('display', 'none');
    }
    return false;
}
// display mol files
function showMol(molFileContent) {
    // the first parameter is a random id
    // otherwise several .mol files will clash
    var viewer = new ChemDoodle.ViewerCanvas(Math.random(), 100, 100);
    viewer.specs.bonds_width_2D = .6;
    viewer.specs.bonds_saturationWidth_2D = .18;
    viewer.specs.bonds_hashSpacing_2D = 2.5;
    viewer.specs.atoms_font_size_2D = 10;
    viewer.specs.atoms_font_families_2D = ['Helvetica', 'Arial', 'sans-serif'];
    viewer.specs.atoms_displayTerminalCarbonLabels_2D = true;
    var mol = ChemDoodle.readMOL(molFileContent);
    viewer.loadMolecule(mol);
}
// go to url
function go_url(x) {
    if (x == '') {
        return;
    }
    window.location = x;
}

// EXPERIMENTS.PHP
// ===============

// VISIBILITY
function experimentsUpdateVisibility(id, visibility) {
    $.post("app/controllers/ExperimentsController.php", {
        experimentsUpdateVisibility: true,
        experimentsUpdateVisibilityId : id,
        experimentsUpdateVisibilityVisibility : visibility
    }).success(function(data) {
        if (data === '0') {
            notif('There was an error!');
        } else {
            notif('Visibility updated', 'ok');
        }
    });
}

// STATUS
function experimentsUpdateStatus(id, status) {
    $.post("app/controllers/ExperimentsController.php", {
        experimentsUpdateStatus: true,
        experimentsUpdateStatusId : id,
        experimentsUpdateStatusStatus : status
    }).success(function(data) {
        if (data === '0') {
            notif('There was an error!');
        } else { // it returns the color
            notif('Status updated', 'ok');
            // change the color of the item border
            // we first remove any status class
            $("#main_section").css('border', null);
            // and we add our new border color
            // first : get what is the color of the new status
            css = '6px solid #' + data;
            $("#main_section").css('border-left', css);
        }
    });
}


// admin.php
// =========

// STATUS
function statusCreate() {
    name = $('#statusName').val();
    color = $('#statusColor').val();
    $.post('app/controllers/StatusController.php', {
        statusCreate: true,
        statusName: name,
        statusColor: color
    }).success(function(data) {
        if (data) {
            notif('Saved', 'ok');
            window.location.replace('admin.php?tab=3');
        } else {
            notif('Error', 'ko');
        }
    });
}

function statusUpdate(id) {
    name = $('#statusName_' + id).val();
    color = $('#statusColor_' + id).val();
    defaultBox = $('#statusDefault_' + id).val(); // 'on' if checked

    $.post('app/controllers/StatusController.php', {
        statusUpdate: true,
        statusId: id,
        statusName: name,
        statusColor: color,
        statusDefault: defaultBox
    }).success(function(data) {
        if (data) {
            notif('Saved', 'ok');
            window.location.replace('admin.php?tab=3');
        } else {
            notif('Error', 'ko');
        }
    });
}

// ITEMS TYPES
function itemsTypesCreate() {
    name = $('#itemsTypesName').val();
    color = $('#itemsTypesColor').val();
    template = tinymce.get('itemsTypesTemplate').getContent();
    $.post('app/controllers/ItemsTypesController.php', {
        itemsTypesCreate: true,
        itemsTypesName: name,
        itemsTypesColor: color,
        itemsTypesTemplate: template
    }).success(function() {
        notif('Saved', 'ok');
        window.location.replace('admin.php?tab=4');
    });
}

function itemsTypesShowEditor(id) {
    $('#itemsTypesEditor_' + id).toggle();
}

function itemsTypesUpdate(id) {
    name = $('#itemsTypesName_' + id).val();
    color = $('#itemsTypesColor_' + id).val();
    template = tinymce.get('itemsTypesTemplate_' + id).getContent();
    $.post('app/controllers/ItemsTypesController.php', {
        itemsTypesUpdate: true,
        itemsTypesId: id,
        itemsTypesName: name,
        itemsTypesColor: color,
        itemsTypesTemplate: template
    }).success(function() {
        notif('Saved', 'ok');
        window.location.replace('admin.php?tab=4');
    });
}


// COMMON TEMPLATE
function commonTplUpdate() {
    template = tinymce.get('commonTplTemplate').getContent();
    $.post('app/controllers/CommonTplController.php', {
        commonTplUpdate: template
    }).success(function() {
        notif('Saved', 'ok');
    });
}

// TEAM GROUP
function teamGroupCreate() {
    var name = $('#teamGroupCreate').val();
    if (name.length > 0) {
        $.post('app/controllers/TeamGroupsController.php', {
            teamGroupCreate: name
        }).success(function() {
            $('#team_groups_div').load('admin.php #team_groups_div');
            $('#teamGroupCreate').val('');
        });
    }
}

function teamGroupUpdate(action) {
    if (action === 'add') {
        user = $('#teamGroupUserAdd').val();
        group = $('#teamGroupGroupAdd').val();
    } else {
        user = $('#teamGroupUserRm').val();
        group = $('#teamGroupGroupRm').val();
    }
    $.post('app/controllers/TeamGroupsController.php', {
        teamGroupUpdate: true,
        action: action,
        teamGroupUser: user,
        teamGroupGroup: group
    }).success(function() {
        $('#team_groups_div').load('admin.php #team_groups_div');
    });
}

function teamGroupDestroy(groupid, confirmText) {
    var you_sure = confirm(confirmText);
    if (you_sure === true) {
        $.post('app/controllers/TeamGroupsController.php', {
            teamGroupDestroy: true,
            teamGroupGroup: groupid
        }).success(function() {
            $("#team_groups_div").load("admin.php #team_groups_div");
        });
    }
    return false;
}
// END TEAM GROUP

// used on import csv/zip to go to next step
function goNext(x) {
    if(x === '') {
        return;
    }
    document.cookie = 'itemType='+x;
    $('.import_block').show();
}

// sysconfig.php
// =============

// TEAMS
function teamsCreate() {
    // disable button on click
    document.getElementById('teamsCreateButton').disabled = true;
    name = $('#teamsName').val();
    $.post('app/controllers/TeamsController.php', {
        teamsCreate: true,
        teamsName: name
    }).success(function(data) {
        if (data) {
            notif('Team created', 'ok');
            $('#teamsDiv').load('sysconfig.php #teamsDiv');
        } else {
            notif('There was an error!');
        }
    });
}

function teamsUpdate(id) {
    document.getElementById('teamsUpdateButton_' + id).disabled = true;
    name = $('#team_' + id).val();
    $.post("app/controllers/TeamsController.php", {
        teamsUpdate: true,
        teamsUpdateId : id,
        teamsUpdateName : name
    }).success(function(data) {
        if (data) {
            notif('Name updated', 'ok');
            $('#teamsDiv').load('sysconfig.php #teamsDiv');
        } else {
            notif('Error', 'ko');
        }
    });
}

function teamsDestroy(id) {
    // disable button on click
    document.getElementById('teamsDestroyButton_' + id).disabled = true;
    $.post("app/controllers/TeamsController.php", {
        teamsDestroy: true,
        teamsDestroyId: id
    }).success(function(data) {
        if (data) {
            notif('Team removed', 'ok');
            $('#teamsDiv').load('sysconfig.php #teamsDiv');
        } else {
            notif('Team not removed because not empty!', 'ko');
        }
    });
}

function teamsUpdateButtonEnable(id) {
    button = document.getElementById('teamsUpdateButton_' + id).disabled = false
};

function teamsArchive(id) {
    // disable button on click
    document.getElementById('teamsArchiveButton_' + id).disabled = true;
    $.post("app/controllers/TeamsController.php", {
        teamsArchive: true,
        teamsArchiveId: id
    }).success(function(data) {
        notif('Feature not yet implemented :)');
    document.getElementById('teamsArchiveButton_' + id).disabled = false;
        /*
        if (data) {
            notif('Team archived', 'ok');
            $('#teamsDiv').load('sysconfig.php #teamsDiv');
        } else {
            notif('Team not archived', 'ko');
        }
        */
    });
}

// called when mail_method selector is changed; enables/disables the config for the selected/unselected method
function toggleMailMethod(value) {
    if (value == 'sendmail') {
        $('#smtp_config').hide();
        $('#sendmail_config').show();
    } else if (value == 'smtp') {
        $('#smtp_config').show();
        $('#sendmail_config').hide();
    } else if (value == 'php') {
        $('#smtp_config').hide();
        $('#sendmail_config').hide();
        $('#general_mail_config').show();
    } else {
        $('#smtp_config').hide();
        $('#sendmail_config').hide();
        $('#general_mail_config').hide();
    }
}

// send a test email to provided adress
function testemailSend() {
    email = $('#testemailEmail').val();
    // check the email loosely
    if (/\S+@\S+\.\S+/.test(email)) {
        document.getElementById('testemailButton').disabled = true;
        $.post('app/controllers/Sysconfig.php', {
            testemailSend: true,
            testemailEmail: email
        }).success(function(data) {
            if (data === '1') {
                notif('Email sent!', 'ok');
                document.getElementById('testemailButton').disabled = false;
            } else {
                notif('Something went wrong! :(', 'ko');
            }
        });
    } else {
        notif('Email address looks weird', 'ko');
    }
}

// LOGS
function logsDestroy() {
    // disable button on click
    document.getElementById('logsDestroyButton').disabled = true;
    $.post('app/controllers/LogsController.php', {
        logsDestroy: true
    }).success(function(data) {
        if (data == 1) {
            notif('All logs cleared', 'ok');
        } else {
            notif('Something went wrong! :(', 'ko');
        }
        $('#logsDiv').load('sysconfig.php #logsDiv');
    });
}
