/**
 * footer.js - for the footer
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    // ADVANCED SEARCH BAR (TOP RIGHT)
    $('#adv_search').hide();
    $('#big_search_input').click(function() {
        $('#adv_search').show();
    });

    // HELP CONTAINER
    $('#help_container').hide();
    $('#help').click(function() {
        $('#help_container').toggle();
    });
    $(document).on('click', '.helpClose', function() {
        $('#help_container').hide();
    });
}());
