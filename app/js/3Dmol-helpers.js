/**
 * 3Dmol-helpers.js - for the little menu top left of the molecule files uploaded and read by 3Dmol.js
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    // CARTOON (only working for protein structures!)
    $(document).on('click', '.3dmol-cartoon', function() {
        $3Dmol.viewers[$(this).data('divid')].setStyle({cartoon: {color:'spectrum'}}).render();
    });

    // CROSS
    $(document).on('click', '.3dmol-cross', function() {
        $3Dmol.viewers[$(this).data('divid')].setStyle({},{cross:{}}).render();
    });

    // LINE
    $(document).on('click', '.3dmol-line', function() {
        $3Dmol.viewers[$(this).data('divid')].setStyle({},{line:{}}).render();
    });

    // SPHERE
    $(document).on('click', '.3dmol-sphere', function() {
        $3Dmol.viewers[$(this).data('divid')].setStyle({},{sphere:{}}).render();
    });

    // STICK
    $(document).on('click', '.3dmol-stick', function() {
        $3Dmol.viewers[$(this).data('divid')].setStyle({},{stick:{}}).render();
    });
}());
