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
        .done(function() {
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
    if (cssClass === 'ok') {
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
    return prmstr !== null && prmstr !== "" ? transformToAssocArray(prmstr) : {};
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

// show/hide the todolist
function toggleTodoList() {
    todoList = $('#todoList');
    if (todoList.css('display') === 'none') {
        todoList.css('display', 'inline');
    } else {
        todoList.css('display', 'none');
    }
}

// display mol files
function showMol(molFileContent) {
    // the first parameter is a random id
    // otherwise several .mol files will clash
    var viewer = new ChemDoodle.ViewerCanvas(Math.random(), 100, 100);
    viewer.specs.bonds_width_2D = 0.6;
    viewer.specs.bonds_saturationWidth_2D = 0.18;
    viewer.specs.bonds_hashSpacing_2D = 2.5;
    viewer.specs.atoms_font_size_2D = 10;
    viewer.specs.atoms_font_families_2D = ['Helvetica', 'Arial', 'sans-serif'];
    viewer.specs.atoms_displayTerminalCarbonLabels_2D = true;
    var mol = ChemDoodle.readMOL(molFileContent);
    viewer.loadMolecule(mol);
}

// go to url
function go_url(x) {
    if (x === '') {
        return;
    }
    window.location = x;
}

// EXPERIMENTS.PHP
// ===============

// VISIBILITY
function updateVisibility(item, visibility) {
    $.post("app/controllers/ExperimentsController.php", {
        updateVisibility: true,
        id: item,
        visibility: visibility
    }).done(function(data) {
        if (data === '0') {
            notif('There was an error!', 'ko');
        } else {
            notif('Visibility updated', 'ok');
        }
    });
}

// STATUS
function updateStatus(item, status) {
    $.post("app/controllers/ExperimentsController.php", {
        updateStatus: true,
        id: item,
        status : status
    }).done(function(data) {
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

// CREATE TAG
function createTag(e, item_id, type) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
        // get tag
        tag = $('#createTagInput').val();
        // POST request
        $.post('app/controllers/EntityController.php', {
            createTag: true,
            createTagTag: tag,
            createTagId: item_id,
            createTagType: type
        }).done(function () {
            if (type === 'experiments') {
                $('#tags_div').load('experiments.php?mode=edit&id=' + item_id + ' #tags_div');
            } else {
                $('#tags_div').load('database.php?mode=edit&id=' + item_id + ' #tags_div');
            }
            // clear input field
            $('#createTagInput').val('');
        });
    } // end if key is enter
}

// CREATE LINK
function experimentsCreateLink(e, item) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
        // get link
        link = decodeURIComponent($('#linkinput').val());
        // fix for user pressing enter with no input
        if (link.length > 0) {
            // parseint will get the id, and not the rest (in case there is number in title)
            link = parseInt(link, 10);
            if (isNaN(link) !== true) {
                // POST request
                $.post('app/controllers/ExperimentsController.php', {
                    createLink: true,
                    id: item,
                    linkId: link
                })
                // reload the link list
                .done(function () {
                    $("#links_div").load("experiments.php?mode=edit&id=" + item + " #links_div");
                    // clear input field
                    $("#linkinput").val("");
                    return false;
                });
            } // end if input is bad
        } // end if input < 0
    } // end if key is enter
}

// DESTROY LINK
function experimentsDestroyLink(link, item, confirmText) {
    var youSure = confirm(confirmText);
    if (youSure === true) {
        $.post('app/controllers/ExperimentsController.php', {
            destroyLink: true,
            id: item,
            linkId: link
        }).done(function (data) {
            if (data === '1') {
                notif('Link removed', 'ok');
                $("#links_div").load("experiments.php?mode=edit&id=" + item + " #links_div");
            } else {
                notif('Something went wrong! :(', 'ko');
            }
        });
    }
    return false;
}

// COMMENTS
function commentsCreateButtonDivShow() {
    $('#commentsCreateButtonDiv').show();
}

// create
function commentsCreate(id) {
    document.getElementById('commentsCreateButton').disabled = true;
    comment = $('#commentsCreateArea').val();
    // check length
    if (comment.length < 2) {
        notif('Comment too short!');
        document.getElementById('commentsCreateButton').disabled = false;
        return false;
    }

    $.post('app/controllers/CommentsController.php', {
        commentsCreate: true,
        comment: comment,
        id: id
    }).done(function(data) {
        if (data) {
            notif('Comment added', 'ok');
            // now we reload the comments part to show the comment we just submitted
            $('#expcomment_container').load("experiments.php?mode=view&id=" + id + " #expcomment");
        } else {
            notif('There was an error!');
        }
    });
}

// destroy
function commentsDestroy(id, expId, confirmText) {
    var you_sure = confirm(confirmText);
    if (you_sure === true) {
        $.post('app/controllers/CommentsController.php', {
            commentsDestroy: true,
            id: id
        }).done(function(data) {
             if (data == 1) {
                 notif('Comment deleted', 'ok');
                 $('#expcomment_container').load("experiments.php?mode=view&id=" + expId + " #expcomment");
             } else {
                 notif('Error while deleting comment', 'ko');
             }
        });
    } else {
        return false;
    }
}

// EXPERIMENTS
function experimentsDestroy(id, confirmText) {
    var you_sure = confirm(confirmText);
    if (you_sure !== true) {
        return false;
    }
    $.post('app/controllers/ExperimentsController.php', {
        destroy: true,
        id: id
    }).done(function(data) {
         if (data == 1) {
             notif('Experiment deleted', 'ok');
            window.location.replace('experiments.php');
         } else {
             notif('Error while deleting experiment', 'ko');
         }
    });
}

// DATABASE
function databaseDestroy(id, confirmText) {
    var you_sure = confirm(confirmText);
    if (you_sure !== true) {
        return false;
    }
    $.post('app/controllers/DatabaseController.php', {
        destroy: true,
        id: id
    }).done(function(data) {
         if (data == 1) {
             notif('Item deleted', 'ok');
            window.location.replace('database.php');
         } else {
             notif('Error while deleting item', 'ko');
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
    }).done(function(data) {
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
    }).done(function(data) {
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
    }).done(function() {
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
    }).done(function() {
        notif('Saved', 'ok');
    });
}

function itemsTypesDestroy(id) {
    $.post('app/controllers/ItemsTypesController.php', {
        itemsTypesDestroy: true,
        itemsTypesId: id
    }).done(function() {
        notif('Deleted', 'ok');
        $('#itemstypes_' + id).hide();
    });
}


// COMMON TEMPLATE
function commonTplUpdate() {
    template = tinymce.get('commonTplTemplate').getContent();
    $.post('app/controllers/ConfigController.php', {
        commonTplUpdate: template
    }).done(function() {
        notif('Saved', 'ok');
    });
}

// TEAM GROUP
function teamGroupCreate() {
    var name = $('#teamGroupCreate').val();
    if (name.length > 0) {
        $.post('app/controllers/TeamGroupsController.php', {
            teamGroupCreate: name
        }).done(function() {
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
    }).done(function() {
        $('#team_groups_div').load('admin.php #team_groups_div');
    });
}

function teamGroupDestroy(groupid, confirmText) {
    var you_sure = confirm(confirmText);
    if (you_sure === true) {
        $.post('app/controllers/TeamGroupsController.php', {
            teamGroupDestroy: true,
            teamGroupGroup: groupid
        }).done(function() {
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

// PROMOTE SYSADMIN
function promoteSysadmin() {
    // disable button on click
    document.getElementById('promoteSysadminButton').disabled = true;
    email = $('#promoteSysadmin').val();
    $.post('app/controllers/ConfigController.php', {
        promoteSysadmin: true,
        email: email
    }).done(function(data) {
        if (data) {
            notif('User promoted', 'ok');
            $('#teamsDiv').load('sysconfig.php #teamsDiv');
        } else {
            notif('There was an error!');
        }
    });
}

// TEAMS
function teamsCreate() {
    // disable button on click
    document.getElementById('teamsCreateButton').disabled = true;
    name = $('#teamsName').val();
    $.post('app/controllers/ConfigController.php', {
        teamsCreate: true,
        teamsName: name
    }).done(function(data) {
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
    $.post("app/controllers/ConfigController.php", {
        teamsUpdate: true,
        teamsUpdateId : id,
        teamsUpdateName : name
    }).done(function(data) {
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
    $.post("app/controllers/ConfigController.php", {
        teamsDestroy: true,
        teamsDestroyId: id
    }).done(function(data) {
        if (data) {
            notif('Team removed', 'ok');
            $('#teamsDiv').load('sysconfig.php #teamsDiv');
        } else {
            notif('Team not removed because not empty!', 'ko');
        }
    });
}

function teamsUpdateButtonEnable(id) {
    button = document.getElementById('teamsUpdateButton_' + id).disabled = false;
}

function teamsArchive(id) {
    // disable button on click
    document.getElementById('teamsArchiveButton_' + id).disabled = true;
    $.post("app/controllers/ConfigController.php", {
        teamsArchive: true,
        teamsArchiveId: id
    }).done(function(data) {
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
        $.post('app/controllers/ConfigController.php', {
            testemailSend: true,
            testemailEmail: email
        }).done(function(data) {
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
    $.post('app/controllers/ConfigController.php', {
        logsDestroy: true
    }).done(function(data) {
        if (data == 1) {
            notif('All logs cleared', 'ok');
        } else {
            notif('Something went wrong! :(', 'ko');
        }
        $('#logsDiv').load('sysconfig.php #logsDiv');
    });
}

// UPLOADS DESTROY
function uploadsDestroy(id, type, item_id, confirmText) {
    var youSure = confirm(confirmText);
    if (youSure === true) {
        $.post('app/controllers/EntityController.php', {
            uploadsDestroy: true,
            id: id,
            item_id: item_id,
            type: type
        }).done(function(data) {
            if (data === '1') {
                notif('File removed', 'ok');
                if (type === 'items') {
                    type = 'database';
                }
                $("#filesdiv").load(type + ".php?mode=edit&id=" + item_id + " #filesdiv");
            } else {
                notif('Something went wrong! :(<br>' + data, 'ko');
            }
        });
    }
}

// STAR RATINGS
function updateRating(rating, id) {
    $.post('app/controllers/DatabaseController.php', {
        rating: rating,
        id: id
    }).done(function(data) {
        if (data === '1') {
            notif('Rating updated', 'ok');
        } else {
            notif('Something went wrong! :(', 'ko');
        }
    });
}

// SEARCH PAGE
function insertParamAndReload(key, value) {
    key = escape(key); value = escape(value);

    var kvp = document.location.search.substr(1).split('&');
    if (kvp === '') {
        document.location.search = '?' + key + '=' + value;
    } else {

        var i = kvp.length; var x; while (i--) {
            x = kvp[i].split('=');

            if (x[0] == key) {
                x[1] = value;
                kvp[i] = x.join('=');
                break;
            }
        }

        if (i < 0) { kvp[kvp.length] = [key, value].join('='); }

        // reload the page
        document.location.search = kvp.join('&');
    }
}

// UCP

// for importing user template
function readFile(file, onLoadCallback){
    // check for the various File API support
    if (!window.FileReader) {
        alert('Please use a modern web browser. Import aborted.');
        return false;
    }
    var reader = new FileReader();
    reader.onload = onLoadCallback;
    reader.readAsText(file);
}

function exportTpl(name, id) {
    // we have the name of the template used for filename
    // and we have the id of the editor to get the content from
    // we don't use activeEditor because it requires a click inside the editing area
    var content = tinyMCE.get(id).getContent();
    var blob = new Blob([content], {type: "text/plain;charset=utf-8"});
    saveAs(blob, name + ".elabftw.tpl");
}
