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
  value?: string | Array<Todoitem> | Array<UnfinishedEntities> | Array<Upload> | Array<Categories> | object;
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

interface CheckableItem {
  id: number;
  randomid: number;
}

interface Selected {
  experiments_categories: number[];
  experiments_status: number[];
  items_status: number[];
  items_types: number[];
  tags: number[];
  users: number[];
  can: string;
}

enum Method {
  GET = 'GET',
  POST = 'POST',
  PATCH = 'PATCH',
  DELETE = 'DELETE',
}

enum ProcurementState {
  Pending = 10,
  Validated = 20,
  PartiallyReceived = 30,
  Received = 40,
  Archived = 50,
}

enum Action {
  Create = 'create',
  CreateFromString = 'createfromstring',
  Read = 'read',
  Update = 'update',
  Destroy = 'destroy',

  AccessKey = 'accesskey',
  Add = 'add',
  Archive = 'archive',
  Bloxberg = 'bloxberg',
  Deduplicate = 'deduplicate',
  Disable2fa = 'disable2fa',
  Duplicate = 'duplicate',
  ExclusiveEditMode = 'exclusiveeditmode',
  Finish = 'finish',
  Lock = 'lock',
  Notif = 'notif',
  PatchUser2Team = 'patchuser2team',
  Pin = 'pin',
  RemoveExclusiveEditMode = 'removeexclusiveeditmode',
  Replace = 'replace',
  RequestAction = 'requestaction',
  Review = 'review',
  SendOnboardingEmails = 'sendonboardingemails',
  Sign = 'sign',
  Timestamp = 'timestamp',
  Unreference = 'unreference',
  UpdateMetadataField = 'updatemetadatafield',
  UpdatePassword = 'updatepassword',
  UpdateTag = 'updatetag',
  Validate = 'validate',
}

enum Model {
  Apikey = 'apikeys',
  Comment = 'comments',
  Config = 'config',
  FavTag = 'favtags',
  Idp = 'idps',
  IdpsSources = 'idps_sources',
  ItemsStatus = 'items_status',
  Link = 'links',
  Notification = 'notifications',
  ExperimentsCategories = 'experiments_categories',
  ExperimentsStatus = 'experiments_status',
  ExtraFieldsKeys = 'extra_fields_keys',
  Sigkeys = 'sig_keys',
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

// Match php enum EntityType
enum EntityType {
  Experiment = 'experiments',
  Item = 'items',
  ItemType = 'items_types',
  Template = 'experiments_templates',
}

enum Target {
  All = 'all',
  Body = 'body',
  Comment = 'comments',
  ContentType = 'content_type',
  Customid = 'custom_id',
  Date = 'date',
  Deadline = 'deadline',
  DeadlineNotif = 'deadline_notif',
  Finished = 'finished',
  LinkedExperiments = 'experiments',
  LinkedItems = 'items',
  List = 'list',
  Member = 'member',
  Metadata = 'metadata',
  MetadataField = 'metadatafield',
  Passphrase = 'passphrase',
  Rating = 'rating',
  RealName = 'real_name',
  Sigkey = 'sigkeys',
  State = 'state',
  Title = 'title',
  UserId = 'userid',
}

interface Entity {
  type: EntityType;
  id: number;
}

export {
  Action,
  Categories,
  CheckableItem,
  Selected,
  Entity,
  EntityType,
  Method,
  Model,
  ProcurementState,
  ResponseMsg,
  Target,
  Todoitem,
  UnfinishedEntities,
  Upload,
};
