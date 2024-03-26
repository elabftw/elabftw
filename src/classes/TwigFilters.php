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
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;

use function is_array;
use function sprintf;

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

        // "status" role: see WCAG2.1 4.1.3
        return sprintf("<div role='status' class='alert alert-%s'><i class='fa-fw fas %s color-%s'></i>%s %s</div>", $alert, $icon, $alert, $crossLink, $message);
    }

    public static function toIcon(int $scope): string
    {
        return Scope::toIcon(Scope::from($scope));
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
            return $Metadata->getRaw();
        }

        $grouped = $Metadata->getGroupedExtraFields();

        foreach ($grouped as $group) {
            $final .= sprintf("<h4 data-action='toggle-next' class='mt-4 d-inline togglable-section-title'><i class='fas fa-caret-down fa-fw mr-2'></i>%s</h4>", Tools::eLabHtmlspecialchars($group['name']));
            $final .= '<div>';
            foreach ($group['extra_fields'] as $field) {
                $newTab = ' target="_blank" rel="noopener"';
                if (($field['open_in_current_tab'] ?? false) === true) {
                    $newTab = '';
                }
                $description = isset($field[MetadataEnum::Description->value])
                    ? sprintf('<span class="smallgray">%s</span>', Tools::eLabHtmlspecialchars($field[MetadataEnum::Description->value]))
                    : '';
                $value = $field[MetadataEnum::Value->value] ?? '';
                // type:checkbox is a special case
                if ($field[MetadataEnum::Type->value] === 'checkbox') {
                    $checked = $field[MetadataEnum::Value->value] === 'on' ? ' checked="checked"' : '';
                    $value = '<input class="d-block" disabled type="checkbox"' . $checked . '>';
                }
                // type:url is another special case
                elseif ($field[MetadataEnum::Type->value] === 'url') {
                    $value = sprintf(
                        '<a href="%1$s"%2$s>%1$s</a>',
                        Tools::eLabHtmlspecialchars($value),
                        $newTab,
                    );
                }
                // type:exp/items is another special case
                elseif (in_array($field[MetadataEnum::Type->value], array('experiments', 'items'), true)) {
                    $id = isset($field[MetadataEnum::Value->value]) ? (int) $field[MetadataEnum::Value->value] : 0;
                    $page = $field[MetadataEnum::Type->value] === 'items' ? 'database' : 'experiments';
                    $value = sprintf(
                        '<a href="/%s.php?mode=view&amp;id=%d"%s><span %s data-id="%d" data-endpoint=%s>%s</span></a>',
                        $page,
                        $id,
                        $newTab,
                        $id !== 0 ? 'data-replace-with-title="true"' : '',
                        $id,
                        $field[MetadataEnum::Type->value],
                        Tools::eLabHtmlspecialchars($value),
                    );
                }
                // type:users is also a special case where we go fetch the name of the user
                elseif ($field[MetadataEnum::Type->value] === 'users' && !empty($value)) {
                    try {
                        $linkedUser = new Users((int) $field[MetadataEnum::Value->value]);
                        $value = $linkedUser->userData['fullname'];
                    } catch (ResourceNotFoundException) {
                        $value = _('User could not be found.');
                    }
                }
                // multi select will be an array of options
                elseif (is_array($value)) {
                    $html = '';
                    foreach($value as $option) {
                        $html .= sprintf('<p>%s</p>', Tools::eLabHtmlspecialchars($option));
                    }
                    $value = $html;
                } else {
                    $value = Tools::eLabHtmlspecialchars($value);
                }

                $unit = '';
                if (!empty($field['unit'])) {
                    // a space before the unit so if there are no units we don't have a trailing space
                    $unit = ' ' . Tools::eLabHtmlspecialchars($field['unit']);
                }

                $final .= sprintf(
                    '<li class="list-group-item"><h5 class="mb-0">%s</h5>%s<h6>%s%s</h6></li>',
                    Tools::eLabHtmlspecialchars($field['name']),
                    $description,
                    $value,
                    $unit,
                );
            }
            $final .= '</div>';
        }
        return $final . $Metadata->getAnyContent();
    }

    public static function decrypt(?string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }
        return Crypto::decrypt($encrypted, Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY')));
    }
}
