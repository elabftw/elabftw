/*
 * Common functions used by eLabFTW
 * https://www.elabftw.net
 */

$(document).ready(function() {
    // TOGGLABLE
    $(document).on('click', '.togglable-next', function() {
        $(this).next().toggle();
    });
    $('.togglable-hidden').hide();

    // HELP CONTAINER
    $('#help_container').hide();
    $('#help').click(function() {
        $('#help_container').toggle();
    });
    $(document).on('click', '.helpClose', function() {
        $('#help_container').hide();
    });

    // KEYBOARD SHORTCUTS
    const listener = new window.keypress.Listener();
    // disable listener when in input mode (and relisten on blur)
    $('input[type=text], textarea, input[type=search]')
        .bind('focus', function() { listener.stop_listening(); })
        .bind('blur', function() { listener.listen(); });

    listener.simple_combo($('#todoSc').data('toggle'), function() {
        $('#todoList').toggle();
    });
    listener.simple_combo($('#shortcuts').data('edit'), function() {
        window.location.href = '?mode=edit&id=' + $('#entityInfos').data('id');
    });
    listener.simple_combo($('#shortcuts').data('create'), function() {
        window.location.href = 'app/controllers/ExperimentsController.php?create=true';
    });
    listener.simple_combo($('#shortcuts').data('submit'), function() {
        document.forms.main_form.submit();
    });
    // MAKE THE FILE COMMENT FIELD EDITABLE
    $('.file-comment').on('mouseover', function() {
        makeEditableFileComment($(this).data('type'), $(this).data('id'), listener);
    });
    // MAKE THE COMMENT FIELD EDITABLE
    $('.comment.editable').on('mouseover', function() {
        makeEditableComment($(this).data('type'), listener);
    });

    $('.todoitem.editable').on('mouseover', function(){
        makeEditableTodoitem(listener);
    });

    // low quality fix for keyboard shortcuts being available in a reloaded comment input (after adding a comment)
    /*
    $('#comment_container').on('mouseover', '.editable', function() {
        listener.stop_listening();
    });
    */
});

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
    const htmlText = '<p>' + text + '</p>';
    let overlayClass;
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

// called when you click the save button of tinymce
function quickSave(type, id) {
    $.post('app/controllers/EntityController.php', {
        quickSave: true,
        type : type,
        id : id,
        // we need this to get the updated content
        title : document.getElementById('title_input').value,
        date : document.getElementById('datepicker').value,
        body : tinymce.activeEditor.getContent()
    }).done(function(data, textStatus, xhr) {
        // detect if the session timedout
        if (xhr.getResponseHeader('X-Elab-Need-Auth') === '1') {
            // store the modifications in local storage to prevent any data loss
            localStorage.setItem('body', tinymce.activeEditor.getContent());
            localStorage.setItem('id', id);
            localStorage.setItem('type', type);
            localStorage.setItem('date', new Date().toLocaleString());
            // reload the page so user gets redirected to the login page
            location.reload();
            return;
        }
        if (data.res) {
            notif(data.msg, 'ok');
        } else {
            notif(data.msg, 'ko');
        }
    });
}

// EDIT todoitem
function makeEditableTodoitem(listener) {
    listener.stop_listening();
    $('.todoitem.editable').editable(function(value, settings) {
        $.post('app/controllers/TodolistController.php', {
            update: true,
            body: value,
            id: $(this).attr('id')
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
            } else {
                notif(data.msg, 'ko');
            }
        });

        return(value);
        }, {
     tooltip : 'Click to edit',
     indicator : 'Saving...',
     name : 'fileComment',
     submit : 'Save',
     cancel : 'Cancel',
     style : 'display:inline'
    });
}

// EDIT COMMENT ON experiment/database
function makeEditableComment(type, listener) {
    listener.stop_listening();
    $('.comment.editable').editable('app/controllers/CommentsController.php', {
        // stop the keyboard shortcuts from being triggered
        before : function() { listener.stop_listening(); },
        name: 'update',
        type : 'textarea',
        submitdata: {type: type},
        width: '80%',
        height: '200',
        tooltip : 'Click to edit',
        //indicator : $(this).data('indicator'),
        //submit : $(this).data('submit'),
        //cancel : $(this).data('cancel'),
        submit: 'Save',
        cancel: 'Cancel',
        style : 'display:inline',
        submitcssclass : 'button mt-2',
        cancelcssclass : 'button button-delete mt-2',
        callback : function(result, settings, submitdata) {
            // show result in comment box
            $('#' + submitdata.id).html(submitdata.update.replace(/\n/g,"<br>"));
        }
    });
}

// EDIT COMMENT ON UPLOAD
function makeEditableFileComment(type, itemId, listener) {
    listener.stop_listening();
    $('.editable').editable(function(value, settings) {
        $.post('app/controllers/EntityController.php', {
            updateFileComment : true,
            type: type,
            comment : value,
            comment_id : $(this).attr('id'),
            id: itemId
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
            } else {
                notif(data.msg, 'ko');
            }
        });

        return(value);
        }, {
        tooltip : 'Click to edit',
        indicator : 'Saving...',
        name : 'fileComment',
        submit : 'Save',
        onblur : 'ignore',
        // stop the keyboard shortcuts from being triggered
        before : function() { listener.stop_listening(); },
        cancel : 'Cancel',
        submitcssclass : 'button',
        cancelcssclass : 'button button-delete',
        style : 'display:inline'
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

            if (x[0] === key) {
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

// parse the query from url
// from  http://stackoverflow.com/a/1099670
function getQueryParams(qs) {
    qs = qs.split('+').join(' ');

    var params = {},
    tokens,
    re = /[?&]?([^=]+)=([^&]*)/g;

    while ((tokens = re.exec(qs)) !== null) {
        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }

    return params;
}
