<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\Language;
use Elabftw\Traits\TwigTrait;
use League\Flysystem\FilesystemOperator;

/**
 * This class is used to generate the translations files for i18next (javascript)
 * Use it with: bin/console dev:i18n4js
 */
final class i18n4Js
{
    use TwigTrait;

    public function __construct(private FilesystemOperator $fs) {}

    public function generate(): void
    {
        foreach (Language::cases() as $lang) {
            $this->generateTsFile($lang);
        }
    }

    private function getTerms(): array
    {
        return array(
            '2FA' => _('2FA'),
            'add-compound' => _('Add compound'),
            'add-team' => _('Add team'),
            'add-to-team' => _('Add selected users to team'),
            'archive-user' => _('Archive user'),
            'archive-user-description' => _('Archiving a user means their account will be disabled. This action is reversible.'),
            'add-user-error' => _('Use the autocompletion menu to add users.'),
            'can-manage-compounds' => _('Can manage compounds'),
            'can-manage-inventory-locations' => _('Can manage inventory locations'),
            'can-manage-users2teams' => _('Can manage users to teams'),
            'click-to-edit' => _('Click to edit'),
            'cancel' => _('Cancel'),
            'clear' => _('Clear'),
            'copied' => _('Copied to clipboard.'),
            'check-required' => _('Please check required fields.'),
            'comment-add' => _('Add a comment'),
            'confirm-clear-spreadsheet' => _('Discard current spreadsheet? All unsaved changes will be lost.'),
            'custom-id-in-use' => _('Custom ID is already used! Try another one.'),
            'create-one-experiments' => _('Create an experiment without using a template'),
            'create-one-items' => _('Create a resource without using a template'),
            'create-one-experiments_templates' => _('Create an experiment template'),
            'create-one-items_types' => _('Create a resource template'),
            'delete' => _('Delete'),
            'delete-confirmation' => _('Delete {{num, number}} line(s)?'),
            'delete-selected' => _('Delete selected rows'),
            'dropzone-upload-area' => _('Drop files here to upload'),
            'dropzone-filesize-limit' => _('File size limit:'),
            'edit' => _('Edit'),
            'edit-compound' => _('Edit compound'),
            'editing-metadata' => _('You are currently editing the metadata attached to this entry.'),
            'current-edit' => _('Currently editing'),
            'email' => _('Email'),
            'enable-permission' => _('You must allow at least one permission setting.'),
            'encryption' => _('Encryption'),
            'entity-default-title' => _('Untitled'),
            'entity-delete-warning' => _('The selected item(s) and all associated data will be permanently deleted. This cannot be undone. Are you sure?'),
            'entries-processed' => _('{{num, number}} entries processed'),
            'error' => _('Error'),
            'error-no-filename' => _('File name is missing.'),
            'error-fetch-request' => _('There was an error while fetching the requested data: {{error}}'),
            'error-parsing-metadata' => _('There was an error while parsing metadata (extra fields). Full error has been logged to the browser console.'),
            'export' => _('Export'),
            'export-success' => _('Export successful.'),
            'extra-fields' => _('Extra fields'),
            'filename' => _('Filename'),
            'firstname' => _('Firstname'),
            'format' => _('Format'),
            'illegal-action' => _('This action cannot apply to current selection'),
            'is-admin' => _('Is Admin'),
            'is-archived' => _('Is Archived'),
            'is-owner' => _('Is Owner'),
            'is-sysadmin' => _('Is Sysadmin'),
            'last-login' => _('Last login'),
            'lastname' => _('Lastname'),
            'file-imported' => _('File imported successfully'),
            'file-too-large' => _('Error: file is too large!'),
            'filter-by-category' => _('Filter by category'),
            'form-validation-error' => _('Error validating the form.'),
            'generic-delete-warning' => _('Delete this?'),
            'hide-deleted' => _('Hide deleted'),
            'filter-delete-warning' => _('Remove this filter?'),
            'import' => _('Import'),
            'import-spreadsheet' => _('Import a spreadsheet (xls, csv, ods, ...)'),
            // import errors often print html content
            'import-error' => _('Error during import. Please check the console for more information.'),
            'invalid-info' => _('Invalid syntax or information provided.'),
            'loading' => _('Loading'),
            'link-delete-warning' => _('Delete this link?'),
            'location-name' => _('Location name'),
            // https://www.i18next.com/translation-function/formatting
            'multi-changes-confirm' => _('Apply to {{num, number}} entries?'),
            'name' => _('Name'),
            'new-spreadsheet' => _('New spreadsheet'),
            'no-self-links' => _('Linking an item to itself is not allowed. Please select a different target.'),
            'not-found' => _('No matching result found.'),
            'not-set' => _('Not set'),
            'nothing-selected' => _('Nothing selected!'),
            'now' => _('Now'),
            'oc-version-warning' => _('Warning: current OpenCloning version is incompatible with this version of eLabFTW.'),
            'onboarding-email-sent' => _('Onboarding email(s) will soon be sent.'),
            'only-a-sysadmin' => _('Only a Sysadmin can modify this.'),
            'ownership-transfer' => _('Your entry has been successfully transferred to the selected user.'),
            'please-wait' => _('Please wait…'),
            'readonly' => _('Read-only'),
            'remove' => _('Remove'),
            'rename-column' => _('New title for the column'),
            'replace-edited-file' => _('Do you want to replace the file on the server with this edit?'),
            'replace-existing' => _('Overwrite original file'),
            'request-filename' => _('Enter name of the file'),
            'resource-not-found' => _('Nothing to show with this id'),
            'revisions-error' => _('Select two revisions to compare them.'),
            'save' => _('Save'),
            'save-and-go-back' => _('Save and go back'),
            'save-attachment' => _('Save as attachment'),
            'saved' => _('Saved'),
            'search' => _('Search'),
            'select-resource' => _('Select a resource'),
            'show-deleted' => _('Show deleted'),
            'signing' => _('Signing'),
            'sort-by-column' => _('Sort by column'),
            'step-delete-warning' => _('Delete this step?'),
            'step-unfinish-warning' => _('Are you sure you want to undo this step?'),
            'tag-delete-warning' => _('Delete this tag?'),
            'teams' => _('Teams'),
            'template-title' => _('Template title'),
            'today' => _('Today'),
            'type-3-chars' => _('At least 3 characters are required to search'),
            'unarchive-and-add-to-team' => _('Unarchive and add to team {{team, string}}'),
            'undefined-group' => _('Undefined group'),
            'unarchive-user' => _('Unarchive user'),
            'unarchive-user-description' => _('Unarchiving a user means their account will be restored. This action is reversible.'),
            'upload-file-comment' => _('File comment'),
            'userid' => _('Userid'),
            'valid-until' => _('Valid until'),
            'validated' => _('Validated'),
        );
    }

    /**
     * Configure gettext domain and generate a file
     * @psalm-suppress UnusedFunctionCall
     */
    private function generateTsFile(Language $language): void
    {
        $locale = $language->value . '.utf8';
        $domain = 'messages';
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, dirname(__DIR__, 2) . '/src/langs');
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);

        $Twig = $this->getTwig(true);
        $out = $Twig->render('ts-lang.ts.twig', array('terms' => $this->getTerms()));
        $this->fs->write($language->value . '.ts', $out);
    }
}
