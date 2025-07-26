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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Enums\Currency;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\MessageLevels;
use Elabftw\Enums\Metadata as MetadataEnum;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;

use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function nl2br;

/**
 * Twig filters
 */
final class TwigFilters
{
    /**
     * For displaying messages using bootstrap alerts
     * $level can be a string when coming from a twig template
     */
    public static function displayMessage(string $message, string|MessageLevels $level, bool $closable = true): string
    {
        $level = $level instanceof MessageLevels ? $level : MessageLevels::from($level);

        $crossLink = '';
        if ($closable) {
            $crossLink = "<a href='#' class='close' data-dismiss='alert'>&times;</a>";
        }

        // "status" role: see WCAG2.1 4.1.3
        return sprintf(
            "<div role='status' class='alert alert-%s'><i class='fa-fw fas %s color-%s'></i>%s %s</div>",
            $level->toAlertClass(),
            $level->toFaIcon(),
            $level->toAlertClass(),
            $crossLink,
            $message
        );
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
            if (!array_key_exists('extra_fields', $group)) {
                continue;
            }
            // group list item contains another list with fields
            $final .= sprintf("<div><h4 data-action='toggle-next' data-opened-icon='fa-caret-down' data-closed-icon='fa-caret-right' class='mt-4 d-inline togglable-section-title'><i class='fas fa-caret-down fa-fw mr-2'></i>%s</h4>", Tools::eLabHtmlspecialchars($group['name']));
            $final .= '<ul class="list-group">';
            foreach ($group['extra_fields'] as $field) {
                $final .= '<li class="list-group-item">';
                $newTab = ' target="_blank" rel="noopener"';
                if (($field['open_in_current_tab'] ?? false) === true) {
                    $newTab = '';
                }
                $description = isset($field[MetadataEnum::Description->value])
                    ? sprintf('<span class="smallgray">%s</span>', Tools::eLabHtmlspecialchars($field[MetadataEnum::Description->value]))
                    : '';
                $value = $field[MetadataEnum::Value->value] ?? '';
                $metadataType = $field[MetadataEnum::Type->value] ?? 'text';
                // type:checkbox is a special case
                if ($metadataType === 'checkbox') {
                    $checked = $field[MetadataEnum::Value->value] === 'on' ? ' checked="checked"' : '';
                    $value = '<input class="d-block" disabled type="checkbox"' . $checked . '>';
                }
                // type:text is another special case that becomes textarea, handles multiple lines
                elseif ($metadataType === 'text') {
                    $value = nl2br(Tools::eLabHtmlspecialchars($value));
                }
                // type:url is another special case
                elseif ($metadataType === 'url') {
                    $value = sprintf(
                        '<a href="%1$s"%2$s>%1$s</a>',
                        Tools::eLabHtmlspecialchars($value),
                        $newTab,
                    );
                }
                // type:email is another special case
                elseif ($metadataType === 'email') {
                    $value = sprintf(
                        '<a href="mailto:%1$s">%1$s</a>',
                        Tools::eLabHtmlspecialchars($value),
                    );
                }
                // type:exp/items is another special case
                elseif (in_array($metadataType, array(EntityType::Experiments->value, EntityType::Items->value), true)) {
                    $id = isset($field[MetadataEnum::Value->value]) ? (int) $field[MetadataEnum::Value->value] : 0;
                    $page = $metadataType === EntityType::Items->value ? EntityType::Items->toPage() : EntityType::Experiments->toPage();
                    $value = sprintf(
                        '<a href="/%s?mode=view&amp;id=%d"%s><span %s data-id="%d" data-endpoint=%s>%s</span></a>',
                        $page,
                        $id,
                        $newTab,
                        $id !== 0 ? 'data-replace-with-title="true"' : '',
                        $id,
                        $metadataType,
                        Tools::eLabHtmlspecialchars($value),
                    );
                }
                // type:users is also a special case where we go fetch the name of the user
                elseif ($metadataType === 'users' && !empty($value)) {
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
                    foreach ($value as $option) {
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
                    '<h5 class="mb-0">%s</h5>%s<h6>%s%s</h6>',
                    Tools::eLabHtmlspecialchars($field['name']),
                    $description,
                    $value,
                    $unit,
                );
                $final .= '</li>';
            }
            $final .= '</ul></div>';
        }
        return $final . $Metadata->getAnyContent();
    }

    public static function jsonDecode(string $json): array
    {
        return json_decode($json, true, 3, JSON_THROW_ON_ERROR);
    }

    public static function toSymbol(int $currency): string
    {
        return Currency::from($currency)->toSymbol();
    }

    public static function decrypt(?string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }
        return Crypto::decrypt($encrypted, Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY')));
    }

    public static function array2String(array $input, ?int $depth = null): string
    {
        $str = '';
        foreach ($input as $key => $value) {
            $depth ??= 0;
            if (is_array($value)) {
                $value = self::array2String($value, $depth + 1);
            }
            $str .= '<details style="--depth: ' . $depth . '"><summary>' . $key . '</summary>' . $value . '</details>';
        }
        return $str;
    }

    public static function any2string(string|array|null $input): string
    {
        if (is_string($input)) {
            return $input;
        }
        if (is_array($input)) {
            return self::array2String($input);
        }
        return '';
    }
}
