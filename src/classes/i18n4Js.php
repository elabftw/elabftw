<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Language;
use Elabftw\Traits\TwigTrait;

/**
 * This class is used to generate the translations files for i18next (javascript)
 * Use it with: bin/console dev:i18n4js
 */
class i18n4Js
{
    use TwigTrait;

    public function generate(): void
    {
        foreach (Language::cases() as $lang) {
            $this->generateTsFile($lang);
        }
    }

    private function getTerms(): array
    {
        return array(
            'click-to-edit' => _('Click to edit'),
            'comment-add' => _('Add a comment'),
            'dropzone-upload-area' => _('Drop files here to upload'),
            'dropzone-filesize-limit' => _('File size limit:'),
            'editing-metadata' =>_('You are currently editing the metadata attached to this entry.'),
            'entity-default-title' => _('Untitled'),
            'entity-delete-warning' => _('The selected item(s) and all associated data will be permanently deleted. This cannot be undone. Are you sure?'),
            'extra-fields' => _('Extra fields'),
            'filename' => _('Filename'),
            'generic-delete-warning' => _('Delete this?'),
            'link-delete-warning' => _('Delete this link?'),
            'new-apikey-warning' => _("This is the only time the key will be shown! Make sure to copy it somewhere safe as you won't be able to see it again:"),
            'nothing-selected' => _('Nothing selected!'),
            'now' => _('Now'),
            'please-wait' => _('Please wait…'),
            'replace-edited-file' => _('Do you want to replace the file on the server with this edit?'),
            'request-filename' => _('Enter name of the file'),
            'saved' => _('Saved'),
            'step-delete-warning' => _('Delete this step?'),
            'step-unfinish-warning' => _('Are you sure you want to undo this step?'),
            'tag-delete-warning' => _('Delete this tag?'),
            'template-title' => _('Template title'),
            'today' => _('Today'),
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
        $fs = FsTools::getFs(dirname(__DIR__, 2) . '/src/ts/langs');
        $fs->write($language->value . '.ts', $out);
    }
}
