/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

interface ActionReq {
  action: string;
  csrf?: string;
  what: string;
  type?: string;
  params?: object;
}

interface ResponseMsg {
  res: boolean;
  msg: string | Array<BoundEvent>;
  color?: string;
}

interface BoundEvent {
  item: string;
  start: string;
}

interface CheckableItem {
  id: number;
  randomid: number;
}

enum Method {
  POST = 'POST',
  GET = 'GET',
}

enum Action {
  Create = 'create',
  Read = 'read',
  Update = 'update',
  Destroy = 'destroy',
}

enum Model {
  Comment = 'comment',
  Link = 'link',
  Status = 'status',
  Step = 'step',
  Upload = 'upload',
}

enum Target {
  RealName = 'real_name',
  Comment = 'comment',
  Body = 'body',
  Finished = 'finished',
}

enum Type {
  Experiment = 'experiments',
  ExperimentTemplate = 'experiments_templates',
  Item = 'items',
  ItemTypes = 'items_types',
}

interface Entity {
  type: Type;
  id: number;
}


interface Payload {
  method: Method;
  action: Action;
  model: Model;
  entity?: {
    type: Entity['type'];
    id: Entity['id'];
  };
  content?: string;
  target?: Target;
  id?: number;
  extraParams?: {};
}

export {
  ActionReq,
  BoundEvent,
  CheckableItem,
  ResponseMsg,
  Payload,
  Method,
  Action,
  Model,
  Target,
  Type,
  Entity,
};
