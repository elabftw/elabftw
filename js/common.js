/* 
 * Common functions used by eLabFTW
 */
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

