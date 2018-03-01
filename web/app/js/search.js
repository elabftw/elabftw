/**
 * search.js - for the search page
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    $(document).ready(function(){
        // DATEPICKER
        $( ".datepicker" ).datepicker({dateFormat: 'yymmdd'});
        if ($('#searchin').val() === 'experiments') {
            $("#tag_db").hide();
        } else {
            $("#tag_exp").hide();
        }

        // scroll to anchor if there is a search
        var getParams = getGetParameters();
        if (getParams.type) {
            window.location.hash = "#anchor";
        }

        $('#searchonly').on('change', function() {
            insertParamAndReload('owner', $(this).val());
        });

        $('#searchin').on('change', function() {
            if (this.value == 'experiments') {
                $("#tag_exp").show();
                $("#tag_db").hide();
            } else {
                $("#tag_exp").hide();
                $("#tag_db").show();
            }
        });
    });
}());
