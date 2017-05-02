/*
 * Common functions used by eLabFTW
 * https://www.elabftw.net
 */
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
        var json = JSON.parse(data);
        if (json.res) {
            notif(json.msg, 'ok');
            // change the lock icon
            current = $('#lock').attr('src');
            if (current === 'app/img/lock-gray.png') {
                $('#lock').attr('src', 'app/img/unlock.png');
            } else {
                $('#lock').attr('src', 'app/img/lock-gray.png');
            }
        } else {
            notif(json.msg, 'ko');
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
        var json = JSON.parse(data);
        if (json.res) {
            notif(json.msg, 'ok');
        } else {
            notif(json.msg, 'ko');
        }
    });
}


// EXPERIMENTS
// ===========

// VISIBILITY
function updateVisibility(item, visibility) {
    $.post("app/controllers/ExperimentsController.php", {
        updateVisibility: true,
        id: item,
        visibility: visibility
    }).done(function(data) {
        var json = JSON.parse(data);
        if (json.res) {
            notif(json.msg, 'ok');
        } else {
            notif(json.msg, 'ko');
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
        var json = JSON.parse(data);
        if (json.res) {
            notif(json.msg, 'ok');
            // change the color of the item border
            // we first remove any status class
            $("#main_section").css('border', null);
            // and we add our new border color
            // first : get what is the color of the new status
            css = '6px solid #' + json.color;
            $("#main_section").css('border-left', css);
        } else {
            notif(json.msg, 'ko');
        }
    });
}

// CREATE TAG
function createTag(e, type, item) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum === 13) { // if the key that was pressed was Enter (ascii code 13)
        // get tag
        tag = $('#createTagInput').val();
        // POST request
        $.post('app/controllers/EntityController.php', {
            createTag: true,
            tag: tag,
            id: item,
            type: type
        }).done(function () {
            if (type === 'items') {
                type = 'database';
            }
            $('#tags_div').load(type + '.php?mode=edit&id=' + item + ' #tags_div');
            // clear input field
            $('#createTagInput').val('');
        });
    } // end if key is enter
}
// DESTROY TAG
function destroyTag(type, item, tag){
    var youSure = confirm('Delete this?');
    if (youSure) {
        $.post('app/controllers/EntityController.php', {
            destroyTag: true,
            type:type,
            id:item,
            tag_id:tag,
        }).done(function() {
            if (type === 'items') {
                type = 'database';
            }
            $('#tags_div').load(type + '.php?mode=edit&id=' + item + ' #tags_div');
        });
    }
    return false;
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
            if (!isNaN(link)) {

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
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                $("#links_div").load("experiments.php?mode=edit&id=" + item + " #links_div");
            } else {
                notif(json.msg, 'ko');
            }
        });
    }
    return false;
}

// ENTITY DESTROY
function entityDestroy(type, id, confirmText) {
    var youSure = confirm(confirmText);
    if (youSure !== true) {
        return false;
    }
    if (type === 'experiments') {
        controller = 'app/controllers/ExperimentsController.php';
        location = 'experiments.php';
    } else {
        controller = 'app/controllers/DatabaseController.php';
        location = 'database.php';
    }

    $.post(controller, {
        destroy: true,
        id: id
    }).done(function(data) {
        var json = JSON.parse(data);
        if (json.res) {
            notif(json.msg, 'ok');
            window.location.replace(location);
        } else {
            notif(json.msg, 'ko');
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
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
            } else {
                notif(json.msg, 'ko');
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
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                if (type === 'items') {
                    type = 'database';
                }
                $("#filesdiv").load(type + ".php?mode=edit&id=" + itemId + " #filesdiv");
            } else {
                notif(json.msg, 'ko');
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
        var json = JSON.parse(data);
        if (json.res) {
            notif(json.msg, 'ok');
        } else {
            notif(json.msg, 'ko');
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

// TEMPLATES DESTROY
function templatesDestroy(id) {
    var youSure = confirm('Delete this ?');
    if (youSure === true) {
        $.post('app/controllers/UcpController.php', {
            templatesDestroy: true,
            id: id
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                window.location.replace('ucp.php?tab=3');
            } else {
                notif(json.msg, 'ko');
            }
        });
    } else {
        return false;
    }
}

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
        exp_id: expId
    }).done(function(data) {
        var json = JSON.parse(data);
        $('#decodedDiv').html(json.msg);
    });
}

function generateApiKey() {
    $.post('app/controllers/UsersController.php', {
        generateApiKey: true
    }).done(function(data) {
        var json = JSON.parse(data);
        $("#api_div").load("profile.php #api_div");
    });
}
