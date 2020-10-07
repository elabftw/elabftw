/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

interface ActionReq {
  action: string;
  what: string;
  type?: string;
  params?: object;
}

interface ResponseMsg {
  res: boolean;
  msg: string;
  color?: string;
}

interface CheckableItem {
  id: string;
  randomid: string;
}

export {
  ActionReq,
  ResponseMsg,
  CheckableItem,
};
