<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum Action: string
{
    case AccessKey = 'accesskey';
    case Add = 'add';
    case AllowUntrusted = 'allowuntrusted';
    case Archive = 'archive';
    case Bloxberg = 'bloxberg';
    case ClearLockedOutDevices = 'clearlockedoutdevices';
    case Create = 'create';
    case CreateFromString = 'createfromstring';
    case CreateSigkeys = 'createsigkeys';
    case Destroy = 'destroy';
    case Disable2fa = 'disable2fa';
    case Duplicate = 'duplicate';
    case Email = 'email';
    case Finish = 'finish';
    case ForceLock = 'forcelock';
    case ForceUnlock = 'forceunlock';
    case Lock = 'lock';
    case Notif = 'notif';
    case NotifDestroy = 'notifdestroy';
    case PatchUser2Team = 'patchuser2team';
    case Pin = 'pin';
    case RemoveExclusiveEditMode = 'removeexclusiveeditmode';
    case Replace = 'replace';
    case Restore = 'restore';
    case Review = 'review';
    case SendOnboardingEmails = 'sendonboardingemails';
    case SetCanread = 'setcanread';
    case SetCanwrite = 'setcanwrite';
    case SetNextCustomId = 'setnextcustomid';
    case Sign = 'sign';
    case Test = 'test';
    case Timestamp = 'timestamp';
    case Unarchive = 'unarchive';
    case Unreference = 'unreference';
    case Update = 'update';
    case UpdateMetadataField = 'updatemetadatafield';
    case UpdateOwner = 'updateowner';
    case UpdatePassword = 'updatepassword';
    case UpdateTag = 'updatetag';
    case Validate = 'validate';
}
