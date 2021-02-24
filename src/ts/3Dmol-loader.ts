/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
// this webpack loader removes the tracking code from 3Dmol
// I couldn't find another way to set the notrack:true option
module.exports = function(source: string): string {
  const get = /\$\.get\("https:\/\/3dmol\.csb\.pitt\.edu\/track\/report\.cgi"\);/g;
  return source.replace(get, '');
};
