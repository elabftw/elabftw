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

    // MAKE THE FILE COMMENT FIELD EDITABLE
    $('.file-comment.editable').each(function() {
        makeEditableFileComment();
    });
    // MAKE THE COMMENT FIELD EDITABLE
    $('.comment.editable').each(function() {
        makeEditableComment($(this));
    });

    // MAKE TODOITEMS EDITABLE
    $('.todoitem.editable').each(function() {
        makeEditableTodoitem($(this));
    });
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
function makeEditableTodoitem(element) {
    $(element).editable(function(value, settings) {
        $.post('app/controllers/TodolistController.php', {
            update: true,
            body: value,
            id: $(this).attr('id')
        });

        return(value);
        }, {
     tooltip : 'Click to edit',
     indicator : 'Saving...',
     onblur: 'submit',
     style : 'display:inline'
    });
}

// EDIT COMMENT ON experiment/database
function makeEditableComment(element) {
    $(element).editable('app/controllers/CommentsController.php', {
        name: 'update',
        type : 'textarea',
        submitdata: {type: $(element).data('type')},
        width: '80%',
        height: '200',
        tooltip : 'Click to edit',
        indicator : $(element).data('indicator'),
        submit : $(element).data('submit'),
        cancel : $(element).data('cancel'),
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
function makeEditableFileComment() {
    $('.editable').editable(function(value, settings) {
        $.post('app/controllers/EntityController.php', {
            updateFileComment : true,
            type: $(this).data('type'),
            comment : value,
            comment_id : $(this).attr('id'),
            id: $(this).data('itemid')
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
            } else {
                notif(data.msg, 'ko');
            }
        });

        return(value);
        }, {
        tooltip : 'File comment',
        placeholder: 'File comment',
        indicator : 'Saving...',
        name : 'fileComment',
        onedit: function() {
            if ($(this).text() === 'Click to add a comment') {
                $(this).text('');
            }
        },
        submit : 'Save',
        onblur : 'ignore',
        cancel : 'Cancel',
        submitcssclass : 'button',
        cancelcssclass : 'button button-delete',
        style : 'display:inline'
    });
}

// insert a get param in the url and reload the page
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
