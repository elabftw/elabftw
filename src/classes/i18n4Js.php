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
            'add-compound' => _('Add compound'),
            'click-to-edit' => _('Click to edit'),
            'cancel' => _('Cancel'),
            'comment-add' => _('Add a comment'),
            'custom-id-in-use' => _('Custom ID is already used! Try another one.'),
            'dropzone-upload-area' => _('Drop files here to upload'),
            'dropzone-filesize-limit' => _('File size limit:'),
            'edit' => _('Edit'),
            'edit-compound' => _('Edit compound'),
            'editing-metadata' => _('You are currently editing the metadata attached to this entry.'),
            'entity-default-title' => _('Untitled'),
            'entity-delete-warning' => _('The selected item(s) and all associated data will be permanently deleted. This cannot be undone. Are you sure?'),
            'error-no-category' => _('A category is required to fetch the next Custom ID'),
            'extra-fields' => _('Extra fields'),
            'filename' => _('Filename'),
            'generic-delete-warning' => _('Delete this?'),
            'hide-deleted' => _('Hide deleted'),
            'link-delete-warning' => _('Delete this link?'),
            'location-name' => _('Location name'),
            // https://www.i18next.com/translation-function/formatting
            'multi-changes-confirm' => _('Apply to {{num, number}} entries?'),
            'no-self-links' => _('Linking an item to itself is not allowed. Please select a different target.'),
            'not-set' => _('Not set'),
            'nothing-selected' => _('Nothing selected!'),
            'now' => _('Now'),
            'onboarding-email-sent' => _('Onboarding email(s) will soon be sent.'),
            'please-wait' => _('Please wait…'),
            'remove' => _('Remove'),
            'replace-edited-file' => _('Do you want to replace the file on the server with this edit?'),
            'request-filename' => _('Enter name of the file'),
            'resource-not-found' => _('Nothing to show with this id'),
            'save' => _('Save'),
            'saved' => _('Saved'),
            'show-deleted' => _('Show deleted'),
            'sort-by-column' => _('Sort by column'),
            'step-delete-warning' => _('Delete this step?'),
            'step-unfinish-warning' => _('Are you sure you want to undo this step?'),
            'tag-delete-warning' => _('Delete this tag?'),
            'template-title' => _('Template title'),
            'today' => _('Today'),
            'type-3-chars' => _('At least 3 characters are required to search'),
            'unarchive-and-add-to-team' => _('Unarchive and add to team {{team, string}}'),
            'undefined-group' => _('Undefined group'),
            'upload-file-comment' => _('File comment'),
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
