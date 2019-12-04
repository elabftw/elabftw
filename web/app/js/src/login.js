/**
 * login.js - for the login page
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  $(document).ready(function() {
    $('a.trigger').on('click', function() {
      $('.resetPasswordDiv').slideToggle('slow');
    });
  });
}());
