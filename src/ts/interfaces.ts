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
  value?: string | Array<Todoitem> | Array<BoundEvent> | Array<UnfinishedEntities> | Array<Upload> | object | PartialEntity;
}

interface PartialEntity {
  body: string;
  metadata: string;
}

interface Upload {
  id?: number;
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
  GET = 'GET',
  POST = 'POST',
  UNAUTHGET = 'UNAUTHGET',
}

enum Action {
  Create = 'create',
  Read = 'read',
  Update = 'update',
  Destroy = 'destroy',

  Deduplicate = 'deduplicate',
  DestroyStamppass = 'destroystamppass',
  Duplicate = 'duplicate',
  ImportLinks = 'importlinks',
  Lock = 'lock',
  Pin = 'pin',
  Replace = 'replace',
}

enum Model {
  Apikey = 'apikey',
  Comment = 'comment',
  Config = 'config',
  FavTag = 'favtag',
  Link = 'link',
  Notification = 'notification',
  PrivacyPolicy = 'privacypolicy',
  Status = 'status',
  Step = 'step',
  Tag = 'tag',
  Team = 'team',
  TeamGroup = 'teamgroup',
  Todolist = 'todolist',
  UnfinishedSteps = 'unfinishedsteps',
  Upload = 'upload',
  User = 'user',
  User2Team = 'user2team',
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
  Deadline = 'deadline',
  DeadlineNotif = 'deadline_notif',
  Finished = 'finished',
  List = 'list',
  Member = 'member',
  Metadata = 'metadata',
  MetadataField = 'metadatafield',
  PrivacyPolicy = 'privacypolicy',
  Rating = 'rating',
  RealName = 'real_name',
  Title = 'title',
  TsAuthority = 'ts_authority',
  Unreference = 'unreference',
  UploadId = 'uploadid',
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
  // no notif key means no notif wanted
  notif?: boolean;
  extraParams?: Record<string, unknown>;
}

export {
  Action,
  BoundEvent,
  CheckableItem,
  Entity,
  EntityType,
  Method,
  Model,
  PartialEntity,
  Payload,
  ResponseMsg,
  Target,
  Todoitem,
  UnfinishedEntities,
  Upload,
};
