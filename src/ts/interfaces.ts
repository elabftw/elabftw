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
  msg: string;
  color?: string;
  value?: string | Array<Todoitem> | Array<BoundEvent> | Array<UnfinishedExperiments>;
}

interface Todoitem {
  id: number;
  body: string;
  creation_time: string;
}

interface UnfinishedExperiments {
  id: number;
  title: string;
  steps: Array<string>;
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

  Deduplicate = 'deduplicate',
  Duplicate = 'duplicate',
  Lock = 'lock',
  Unreference = 'unreference',
}

enum Model {
  Apikey = 'apikey',
  Comment = 'comment',
  Link = 'link',
  Status = 'status',
  Step = 'step',
  Tag = 'tag',
  TeamGroup = 'teamgroup',
  Todolist = 'todolist',
  Upload = 'upload',
}

enum EntityType {
  Experiment = 'experiment',
  Item = 'item',
  ItemType = 'itemtype',
  Template = 'template',
}

enum Target {
  All = 'all',
  Body = 'body',
  BoundEvent = 'boundevent',
  Date = 'date',
  Comment = 'comment',
  Finished = 'finished',
  Member = 'member',
  Metadata = 'metadata',
  RealName = 'real_name',
  Tag = 'tag',
  Title = 'title',
}

interface Entity {
  type: EntityType;
  id: number;
}


interface Payload {
  method: Method;
  action: Action;
  model: Model | EntityType;
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
  Todoitem,
  EntityType,
  Entity,
  UnfinishedExperiments,
};
