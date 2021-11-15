/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

interface ResponseMsg {
  res: boolean;
  msg: string;
  color?: string;
  value?: string | Array<Todoitem> | Array<BoundEvent> | Array<UnfinishedEntities> | Array<Upload> | object | MetadataJson;
}

interface MetadataJson {
  metadata: string;
}

interface Upload {
  real_name: string;
  long_name: string;
}

interface Todoitem {
  id: number;
  body: string;
  creation_time: string;
}

interface UnfinishedEntities {
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
  UNAUTHGET = 'UNAUTHGET',
}

enum Action {
  Create = 'create',
  Read = 'read',
  Update = 'update',
  Destroy = 'destroy',

  DestroyStamppass = 'destroystamppass',
  Deduplicate = 'deduplicate',
  Duplicate = 'duplicate',
  Lock = 'lock',
}

enum Model {
  Apikey = 'apikey',
  Comment = 'comment',
  Config = 'config',
  Link = 'link',
  FavTag = 'favtag',
  PrivacyPolicy = 'privacypolicy',
  Status = 'status',
  Step = 'step',
  UnfinishedSteps = 'unfinishedsteps',
  Tag = 'tag',
  Team = 'team',
  TeamGroup = 'teamgroup',
  Todolist = 'todolist',
  Upload = 'upload',
  User = 'user',
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
  Comment = 'comment',
  Date = 'date',
  Finished = 'finished',
  List = 'list',
  Member = 'member',
  Metadata = 'metadata',
  TsOverride = 'ts_override',
  TsAuthority = 'ts_authority',
  PrivacyPolicy = 'privacypolicy',
  Rating = 'rating',
  RealName = 'real_name',
  Title = 'title',
  Unreference = 'unreference',
  UserId = 'userid',
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
  extraParams?: Record<string, unknown>;
}

export {
  BoundEvent,
  CheckableItem,
  ResponseMsg,
  Payload,
  Method,
  Action,
  MetadataJson,
  Model,
  Target,
  Todoitem,
  EntityType,
  Entity,
  UnfinishedEntities,
  Upload,
};
