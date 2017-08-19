/*
 * Common functions used by eLabFTW
 * https://www.elabftw.net
 */
$(document).ready(function() {
    $(document).on('click', '.togglable-next', function() {
        $(this).next().toggle();
    });
    $('.togglable-hidden').hide();
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

// ENTITY
function toggleLock(type, id) {
    $.post("app/controllers/EntityController.php", {
        lock: true,
        type: type,
        id: id
    }).done(function(data) {
        if (data.res) {
            notif(data.msg, 'ok');
            // change the lock icon
            current = $('#lock').attr('src');
            if (current === 'app/img/lock-gray.png') {
                $('#lock').attr('src', 'app/img/unlock.png');
            } else {
                $('#lock').attr('src', 'app/img/lock-gray.png');
            }
        } else {
            notif(data.msg, 'ko');
        }
    });
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
    }).done(function(data) {
        if (data.res) {
            notif(data.msg, 'ok');
        } else {
            notif(data.msg, 'ko');
        }
    });
}


// EDIT COMMENT ON UPLOAD
function makeEditableFileComment(type, itemId) {
    $('.thumbnail p.editable').editable(function(value, settings) {
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
     cancel : 'Cancel',
     style : 'display:inline'
    });
}

// UPLOADS DESTROY
function uploadsDestroy(id, type, itemId, confirmText) {
    var youSure = confirm(confirmText);
    if (youSure === true) {
        $.post('app/controllers/EntityController.php', {
            uploadsDestroy: true,
            upload_id: id,
            id: itemId,
            type: type
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
                if (type === 'items') {
                    type = 'database';
                }
                $("#filesdiv").load(type + ".php?mode=edit&id=" + itemId + " #filesdiv");
            } else {
                notif(data.msg, 'ko');
            }
        });
    }
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

// decode asn1token
function decodeAsn1(path, expId) {
    $.post('app/controllers/ExperimentsController.php', {
        asn1: path,
        id: expId
    }).done(function(data) {
        $('#decodedDiv').html(data.msg);
    });
}

function generateApiKey() {
    $.post('app/controllers/UsersController.php', {
        generateApiKey: true
    }).done(function() {
        $("#api_div").load("profile.php #api_div");
    });
}
