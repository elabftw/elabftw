/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getGetParameters } from './misc';

$(document).ready(function(){
  // scroll to anchor if there is a search
  const getParams = getGetParameters();
  if ((getParams as any).type) {
    window.location.hash = '#anchor';
  }
});
