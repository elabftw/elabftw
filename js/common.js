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
       window.setTimeout(removeNotif, 2500);
}

function removeNotif() {
    $('#overlay').fadeOut(500, function() {
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

// admin.php
// =========

// TEAM GROUP
function teamGroupUpdate(action) {
    if (action === 'add') {
        userid = $('#add_teamgroup_user').val();
        groupid = $('#add_teamgroup_group').val();
    } else {
        userid = $('#rm_teamgroup_user').val();
        groupid = $('#rm_teamgroup_group').val();
    }
    $.post('app/admin-ajax.php', {
        action: action,
        teamgroup_user: userid,
        teamgroup_group: groupid
    }).success(function() {
        $('#team_groups_div').load('admin.php #team_groups_div');
    });
}

function teamGroupCreate() {
    var name = $('#create_teamgroup').val();
    if (name.length > 0) {
        $.post('app/admin-ajax.php', {
            create_teamgroup: name
        }).success(function() {
            $('#team_groups_div').load('admin.php #team_groups_div');
            $('#create_teamgroup').val('');
        });
    }
}

function teamGroupDestroy(groupid, confirmText) {
    var you_sure = confirm(confirmText);
    if (you_sure === true) {
        $.post('app/admin-ajax.php', {
            destroy_teamgroup: true,
            teamgroup_group: groupid
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
function sendTestEmail() {
    var testemail = $('#testemail').val();
    $.post('app/sysconfig-ajax.php', {
        testemail: testemail
    }).success(function(data) {
        if (data == 1) {
            notif('Email sent!', 'ok');
        } else {
            notif('Something went wrong! :(', 'ko');
        }
    });
}

// update the name of a team
function updateTeam(team_id) {
    var new_team_name = document.getElementById('team_'+team_id).value;
    $.post("app/quicksave.php", {
        id : team_id,
        team_name : new_team_name
    }).done(function(returnValue) {
        // we will get output on error
        if (returnValue !== '') {
            document.getElementById('button_'+team_id).value = returnValue;
            document.getElementById('button_'+team_id).style.color = 'red';
        } else {
            document.getElementById('button_'+team_id).value = "<?php echo _('Saved'); ?>";
        }
        document.getElementById('button_'+team_id).disabled = true;
    });
}
