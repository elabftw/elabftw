/* 
 * Common functions used by eLabFTW
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

