window.onbeforeunload = function (e) {
    e = e || window.event;
    // no need to have a text here because it's not displayed to the user
    return '?';
};
