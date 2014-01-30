/* 
 * Common functions used by eLabFTW
 */
// Check for cookies
function checkCookiesEnabled() {
    var cookieEnabled = (navigator.cookieEnabled) ? true : false;
    if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) { 
        document.cookie="testcookie";
        cookieEnabled = (document.cookie.indexOf("testcookie") != -1) ? true : false;
    }
return (cookieEnabled);
}

// The main function to delete stuff
// id of the item you want to delete, its type, the message info you want to say, the url you want to redirect to
function deleteThis(id, type, info, redirect) {
    var you_sure = confirm('Delete this ?');
    if (you_sure === true) {
        $.post('delete.php', {
            id:id,
            type:type
        })
        .success(function() {
            document.cookie = info;
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

// for the footer
function mouseOverPhp(action){
if (action == 'on') {
    document.php.src ="img/phpon.gif";
} else {
document.php.src ="img/phpoff.gif";}
}
function mouseOverSql(action){
if (action == 'on') {
    document.mysql.src ="img/mysqlon.gif";
} else {
document.mysql.src ="img/mysqloff.gif";}
}
function mouseOverCss(action){
if (action == 'on') {
    document.css.src ="img/csson.gif";
} else {
document.css.src ="img/cssoff.gif";}
}

