<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use JsonException;

use function _;
use function array_key_exists;
use function array_map;
use function explode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_scalar;
use function json_decode;
use function json_encode;
use function sprintf;
use function str_replace;
use function trim;
use function mb_strtolower;

final class MetadataHelpers
{
    public static function mergeMetadata(?string $source, string $incoming): string
    {
        $sourceMetadata = self::decodeMetadata($source);
        $incomingMetadata = self::decodeMetadata($incoming);

        $sourceFields = $sourceMetadata['extra_fields'] ?? array();
        $incomingFields = $incomingMetadata['extra_fields'] ?? array();

        if (!is_array($sourceFields) || !is_array($incomingFields)) {
            throw new ImproperActionException(_('Invalid metadata extra_fields provided.'));
        }

        foreach ($incomingFields as $name => $incomingField) {
            if (!is_array($incomingField)) {
                throw new ImproperActionException(
                    sprintf(_('Invalid metadata field %s.'), $name)
                );
            }

            // New field: there is no source schema, so keep the complete
            // incoming field definition.
            if (!array_key_exists($name, $sourceFields)) {
                if (array_key_exists('value', $incomingField)) {
                    self::guardMetadataValueCompatibility(
                        $name,
                        $incomingField,
                        $incomingField['value'],
                    );
                    $incomingField['value'] = self::normalizeMetadataValue(
                        $incomingField,
                        $incomingField['value'],
                    );
                }

                $sourceFields[$name] = $incomingField;
                continue;
            }

            if (!is_array($sourceFields[$name])) {
                throw new ImproperActionException(
                    sprintf(_('Invalid metadata field %s.'), $name)
                );
            }

            // Existing field: the source/template owns the schema.
            // Incoming metadata only supplies the value.
            if (!array_key_exists('value', $incomingField)) {
                continue;
            }

            $value = $incomingField['value'];

            self::guardMetadataValueCompatibility(
                $name,
                $sourceFields[$name],
                $value,
            );

            $sourceFields[$name]['value'] = self::normalizeMetadataValue(
                $sourceFields[$name],
                $value,
            );
        }

        $sourceMetadata['extra_fields'] = $sourceFields;

        return json_encode($sourceMetadata, JSON_THROW_ON_ERROR);
    }

    public static function mergeMetadataValues(string $baseMetadata, string $incomingMetadata): string
    {
        // base metadata comes from the template and contains the field schema
        $base = self::decodeMetadata($baseMetadata);
        // incoming metadata usually comes from CSV/API and contains the values to inject.
        $incoming = self::decodeMetadata($incomingMetadata);
        // ensure both metadata arrays have an extra_fields array.
        $base['extra_fields'] ??= array();
        $incoming['extra_fields'] ??= array();

        foreach ($incoming['extra_fields'] as $name => $incomingField) {
            $value = $incomingField['value'] ?? '';

            if (isset($base['extra_fields'][$name])) {
                $baseField = &$base['extra_fields'][$name];
                self::guardMetadataSchemaCompatibility($name, $baseField, $incomingField);
                self::guardMetadataValueCompatibility($name, $baseField, $value);
                // Preserve the existing field schema and only update its value
                $baseField['value'] = self::normalizeMetadataValue($baseField, $value);
                unset($baseField);
                continue;
            }
            // new fields: keep incoming schema, but normalize its value if it has a known type.
            $incomingField['value'] = self::normalizeMetadataValue($incomingField, $value);
            $base['extra_fields'][$name] = $incomingField;
        }

        return json_encode($base, JSON_THROW_ON_ERROR);
    }

    private static function decodeMetadata(?string $metadata): array
    {
        if ($metadata === null || $metadata === '' || $metadata === '{}') {
            return array('extra_fields' => array());
        }

        try {
            $decoded = json_decode($metadata, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ImproperActionException(_('Invalid metadata JSON provided.'));
        }

        if (!is_array($decoded)) {
            throw new ImproperActionException(_('Invalid metadata JSON provided.'));
        }

        return $decoded;
    }

    private static function normalizeMetadataValue(array $field, mixed $value): string|array
    {
        $type = $field['type'] ?? 'text';

        if (
            $type === 'select-multi'
            || ($type === 'select' && ($field['allow_multi_values'] ?? false) === true)
        ) {
            if (is_array($value)) {
                return array_map(
                    static fn(mixed $option): string => trim((string) $option),
                    $value,
                );
            }

            return array_map('trim', explode(',', (string) $value));
        }

        $value = trim((string) $value);

        return match ($type) {
            'checkbox' => self::normalizeCheckboxValue($value),
            'number' => str_replace(',', '.', $value),
            default => $value,
        };
    }

    private static function guardMetadataSchemaCompatibility(string $name, array $baseField, array $incomingField): void
    {
        // existing fields keep their original schema. If the incoming metadata explicitly specifies
        // a schema (currently type or unit), it must match the stored one. Value-only updates are still allowed.
        foreach (array('type', 'unit') as $key) {
            // The incoming metadata does not specify this schema key, so keep the existing definition
            if (!array_key_exists($key, $incomingField)) {
                continue;
            }

            // reject attempts to merge an incompatible schema into an existing field
            if (!array_key_exists($key, $baseField) || $incomingField[$key] !== $baseField[$key]) {
                throw new ImproperActionException(sprintf(_('Metadata field %s has incompatible %s.'), $name, $key));
            }
        }
    }

    private static function guardMetadataValueCompatibility(
        string $name,
        array $field,
        mixed $value,
    ): void {
        if ($value === '' || $value === null || $value === array()) {
            return;
        }

        $type = $field['type'] ?? 'text';

        if ($type === 'number') {
            if (
                !is_scalar($value)
                || !is_numeric(str_replace(',', '.', trim((string) $value)))
            ) {
                throw new ImproperActionException(
                    sprintf(_('Metadata field %s expects a number.'), $name)
                );
            }

            return;
        }

        if (!in_array($type, array('select', 'select-one', 'select-multi', 'radio'), true)) {
            return;
        }

        $options = $field['options'] ?? array();

        if (!is_array($options)) {
            throw new ImproperActionException(
                sprintf(_('Metadata field %s has invalid options.'), $name)
            );
        }

        $isMulti = $type === 'select-multi'
            || ($type === 'select' && ($field['allow_multi_values'] ?? false) === true);

        if (is_array($value)) {
            if (!$isMulti) {
                throw new ImproperActionException(
                    sprintf(_('Metadata field %s expects a single value.'), $name)
                );
            }

            $values = $value;
        } elseif ($isMulti) {
            $values = array_map('trim', explode(',', (string) $value));
        } else {
            $values = array(trim((string) $value));
        }

        foreach ($values as $option) {
            if ($option !== '' && !in_array($option, $options, true)) {
                throw new ImproperActionException(
                    sprintf(
                        _('Metadata field %s contains an invalid option.'),
                        $name,
                    )
                );
            }
        }
    }

    private static function normalizeCheckboxValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $truthyValues = array(
            // machine-ish
            '1',
            'true',
            'on',
            'checked',
            'x',

            // English
            'yes',
            'y',
            'yeah',
            'yep',
            'yup',
            'sure',
            'okay',
            'ok',
            'aye',

            // French
            'oui',
            'ouais',
            'ouaip',
            'grave',
            'putain mais carrément quoi',

            // Spanish
            'sí',
            'si',
            'claro',

            // Portuguese
            'sim',

            // German / Dutch
            'ja',
            'jawohl',

            // Italian
            'sì',

            // Slavic
            'da',
            'tak',
            'ano',

            // Nordic
            'joo',
            'kyllä',
            'ja',
            'jep',

            // Hungarian
            'igen',

            // Turkish
            'evet',

            // Japanese
            'hai',
            'はい',

            // symbols
            '+',
            '✓',
            '✔',
        );

        return in_array(mb_strtolower(trim($value)), $truthyValues, true) ? 'on' : '';
    }
}
