window.onbeforeunload = function (e) {
    const dialogText = 'Are you sure you want to close this window?';
    e.returnValue = dialogText;
    return dialogText;
};
