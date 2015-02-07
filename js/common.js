/* 
 * Common functions used by eLabFTW
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
            // document.cookie = info;
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

// show and remove 'Saved !'
function showSaved() {
    var text = '<center><p>Saved !</p></center>';
    var overlay = document.createElement('div');
       overlay.setAttribute('id','overlay');
       overlay.setAttribute('class', 'overlay');
       // show the overlay
       document.body.appendChild(overlay);
       // add text inside
       document.getElementById('overlay').innerHTML = text;
       // wait a bit and make it disappear
       window.setTimeout(removeSaved, 2000);
}

function removeSaved() {
       document.body.removeChild(document.getElementById('overlay'));
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
