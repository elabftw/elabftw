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
  value?: string | Array<Todoitem> | Array<BoundEvent> | Array<UnfinishedEntities> | Array<Upload> | Array<Categories> | object;
}

interface Categories {
  category_id: string;
  category: string;
  color: string;
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
  PATCH = 'PATCH',
  DELETE = 'DELETE',
  UNAUTHGET = 'UNAUTHGET',
}

enum Action {
  Create = 'create',
  CreateFromString = 'createfromstring',
  Read = 'read',
  Update = 'update',
  Destroy = 'destroy',

  Add = 'add',
  Bloxberg = 'bloxberg',
  Deduplicate = 'deduplicate',
  Duplicate = 'duplicate',
  Lock = 'lock',
  Pin = 'pin',
  Replace = 'replace',
  Timestamp = 'timestamp',
  UpdateTag = 'updatetag',
  Unreference = 'unreference',
}

enum Model {
  Apikey = 'apikeys',
  Comment = 'comments',
  Config = 'config',
  FavTag = 'favtag',
  Link = 'links',
  Notification = 'notifications',
  PrivacyPolicy = 'privacypolicy',
  Status = 'status',
  Step = 'steps',
  Tag = 'tags',
  Team = 'teams',
  TeamGroup = 'teamgroups',
  Todolist = 'todolist',
  UnfinishedSteps = 'unfinishedsteps',
  Upload = 'uploads',
  User = 'users',
  User2Team = 'user2team',
}

// Match TYPE_ consts in AbstractEntity
enum EntityType {
  Experiment = 'experiments',
  Item = 'items',
  ItemType = 'items_types',
  Template = 'experiments_templates',
}

enum Target {
  All = 'all',
  Body = 'body',
  BoundEvent = 'boundevent',
  Comment = 'comments',
  ContentType = 'content_type',
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
  Categories,
  CheckableItem,
  Entity,
  EntityType,
  Method,
  Model,
  Payload,
  ResponseMsg,
  Target,
  Todoitem,
  UnfinishedEntities,
  Upload,
};
