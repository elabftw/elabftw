/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import genbankToJson from 'bio-parsers/src/parsers/anyToJson';
import * as $OVE from 'open-vector-editor/umd/open-vector-editor.js';

declare global {
  interface Window {
    OVEsequenceData: Record<string, any>;
  }
}

$(document).ready(function() {
  if ($('#ove').length > 0) {
    const fileLocation = $('#ove').data('href');
    $.get(fileLocation, function(fileContent){
      genbankToJson(fileContent, function(sequenceData) {
        window.OVEsequenceData = sequenceData;
      });
    }, 'text');
  }
});
