<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Enums\Metadata as MetadataEnum;
use Elabftw\Models\Config;
use function implode;
use function is_array;

/**
 * Twig filters
 */
class TwigFilters
{
    /**
     * For displaying messages using bootstrap alerts
     *
     * @param string $message The message to display
     * @param string $type Can be 'ok', 'ko' or 'warning'
     * @param bool $cross do we display a cross or not?
     * @return string the HTML of the message
     */
    public static function displayMessage(string $message, string $type, bool $cross = true): string
    {
        $icon = 'fa-info-circle';
        $alert = 'success';

        if ($type === 'ko') {
            $icon = 'fa-exclamation-triangle';
            $alert = 'danger';
        } elseif ($type === 'warning') {
            $icon = 'fa-chevron-right';
            $alert = $type;
        }

        $crossLink = '';

        if ($cross) {
            $crossLink = "<a href='#' class='close' data-dismiss='alert'>&times;</a>";
        }

        return sprintf("<div class='alert alert-%s'><i class='fa-fw fas %s color-%s'></i>%s %s</div>", $alert, $icon, $alert, $crossLink, $message);
    }

    /**
     * Process the metadata json string into html
     * @psalm-suppress PossiblyUnusedMethod this method is used in twig templates
     */
    public static function formatMetadata(string $json): string
    {
        $final = '';
        $Metadata = new Metadata($json);
        $extraFields = $Metadata->getExtraFields();
        if (empty($extraFields)) {
            return $final;
        }
        // sort the elements based on the position attribute. If not set, will be at the end.
        uasort($extraFields, function (array $a, array $b): int {
            return ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
        });

        $grouped = $Metadata->getGroupedExtraFields();

        foreach ($grouped as $group) {
            $final .= sprintf("<h4 data-action='toggle-next' class='mt-4 d-inline togglable-section-title'><i class='fas fa-caret-down fa-fw mr-2'></i>%s</h4>", $group['name']);
            $final .= '<div>';
            foreach ($group['extra_fields'] as $field) {
                $description = isset($field[MetadataEnum::Description->value])
                    ? sprintf('<span class="smallgray">%s</span>', $field[MetadataEnum::Description->value])
                    : '';
                $value = $field[MetadataEnum::Value->value];
                // checkbox is a special case
                if ($field[MetadataEnum::Type->value] === 'checkbox') {
                    $checked = $field[MetadataEnum::Value->value] === 'on' ? 'checked' : '';
                    $value = '<input class="d-block" disabled type="checkbox" ' . $checked . '>';
                }
                // url is another special case
                if ($field[MetadataEnum::Type->value] === 'url') {
                    $newTab = 'target="_blank" rel="noopener"';
                    if (($field['open_in_current_tab'] ?? false) === true) {
                        $newTab = '';
                    }
                    $value = '<a href="' . $value . '" ' . $newTab . '>' . $value . '</a>';
                }

                // multi select will be an array
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $final .= sprintf('<li class="list-group-item"><h5 class="mb-0">%s</h5>%s<h6>%s</h6></li>', $field['name'], $description, $value);
            }
            $final .= '</div>';
        }
        return $final;
    }

    public static function decrypt(?string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }
        return Crypto::decrypt($encrypted, Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY')));
    }
}
