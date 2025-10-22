<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

/**
 * Modifiable users table column
 */
enum UsersColumn: string
{
    case AllowUntrusted = 'allow_untrusted';
    case AlwaysShowOwned = 'always_show_owned';
    case AppendPdfs = 'append_pdfs';
    case CanManageCompounds = 'can_manage_compounds';
    case CanManageInventoryLocations = 'can_manage_inventory_locations';
    case CanManageUsers2teams = 'can_manage_users2teams';
    case CjkFonts = 'cjk_fonts';
    case DefaultRead = 'default_read';
    case DefaultWrite = 'default_write';
    case DisableShortcuts = 'disable_shortcuts';
    case DisplayMode = 'display_mode';
    case Email = 'email';
    case Entrypoint = 'entrypoint';
    case Firstname = 'firstname';
    case IncFilesPdf = 'inc_files_pdf';
    case IsSysadmin = 'is_sysadmin';
    case Lang = 'lang';
    case Lastname = 'lastname';
    case Limit = 'limit_nb';
    case MfaSecret = 'mfa_secret';
    case NotifCommentCreated = 'notif_comment_created';
    case NotifCommentCreatedEmail = 'notif_comment_created_email';
    case NotifEventDeleted = 'notif_event_deleted';
    case NotifEventDeletedEmail = 'notif_event_deleted_email';
    case NotifStepDeadline = 'notif_step_deadline';
    case NotifStepDeadlineEmail = 'notif_step_deadline_email';
    case NotifUserCreated = 'notif_user_created';
    case NotifUserCreatedEmail = 'notif_user_created_email';
    case NotifUserNeedValidation = 'notif_user_need_validation';
    case NotifUserNeedValidationEmail = 'notif_user_need_validation_email';
    case Orcid = 'orcid';
    case Orderby = 'orderby';
    case Orgid = 'orgid';
    case PdfFormat = 'pdf_format';
    case PdfSignature = 'pdf_sig';
    case ScCreate = 'sc_create';
    case ScEdit = 'sc_edit';
    case ScopeExperiments = 'scope_experiments';
    case ScopeEvents = 'scope_events';
    case ScopeItems = 'scope_items';
    case ScopeItemsTypes = 'scope_items_types';
    case ScopeExperimentsTemplates = 'scope_experiments_templates';
    case ScopeTeamgroups = 'scope_teamgroups';
    case ScFavorite = 'sc_favorite';
    case SchedulerLayout = 'scheduler_layout';
    case ScSearch = 'sc_search';
    case ScTodo = 'sc_todo';
    case ShowWeekends = 'show_weekends';
    case Sort = 'sort';
    case UseIsodate = 'use_isodate';
    case UploadsLayout = 'uploads_layout';
    case UseMarkdown = 'use_markdown';
    case ValidUntil = 'valid_until';
    case Validated = 'validated';
}
