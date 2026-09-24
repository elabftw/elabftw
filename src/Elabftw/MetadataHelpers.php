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
use function is_float;
use function is_int;
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

        $sourceFields = self::getExtraFields($sourceMetadata);
        $incomingFields = self::getExtraFields($incomingMetadata);

        foreach ($incomingFields as $name => $incomingField) {

            // New field: there is no source schema, so keep the complete
            // incoming field definition.
            if (!array_key_exists($name, $sourceFields)) {
                if (array_key_exists('value', $incomingField)) {
                    $incomingField['value'] = self::validateAndNormalizeMetadataValue(
                        $name,
                        $incomingField,
                        $incomingField['value'],
                    );
                }

                $sourceFields[$name] = $incomingField;
                continue;
            }

            // Existing field: the source/template owns the schema.
            // Incoming metadata only supplies the value.
            if (!array_key_exists('value', $incomingField)) {
                continue;
            }

            $value = $incomingField['value'];

            $sourceFields[$name]['value'] = self::validateAndNormalizeMetadataValue(
                $name,
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
        $base['extra_fields'] = self::getExtraFields($base);
        $incoming['extra_fields'] = self::getExtraFields($incoming);

        foreach ($incoming['extra_fields'] as $name => $incomingField) {
            $value = $incomingField['value'] ?? '';

            if (isset($base['extra_fields'][$name])) {
                $baseField = &$base['extra_fields'][$name];
                self::guardMetadataSchemaCompatibility($name, $baseField, $incomingField);
                // Preserve the existing field schema and only update its value
                $baseField['value'] = self::validateAndNormalizeMetadataValue($name, $baseField, $value);
                unset($baseField);
                continue;
            }
            // new fields: keep incoming schema, but validate and normalize its value.
            $incomingField['value'] = self::validateAndNormalizeMetadataValue($name, $incomingField, $value);
            $base['extra_fields'][$name] = $incomingField;
        }

        return json_encode($base, JSON_THROW_ON_ERROR);
    }

    public static function validateAndNormalizeMetadataValue(
        string $name,
        array $field,
        mixed $value,
        bool $preserveNumericTypes = false,
    ): string|array|int|float {
        self::guardMetadataValueCompatibility($name, $field, $value);

        return self::normalizeMetadataValue($field, $value, $preserveNumericTypes);
    }

    private static function getExtraFields(array $metadata): array
    {
        $fields = $metadata['extra_fields'] ?? array();
        if (!is_array($fields)) {
            throw new ImproperActionException(_('Invalid metadata extra_fields provided.'));
        }

        foreach ($fields as $name => $field) {
            if (!is_array($field)) {
                throw new ImproperActionException(
                    sprintf(_('Invalid metadata field %s.'), $name)
                );
            }
        }

        return $fields;
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

    private static function normalizeMetadataValue(
        array $field,
        mixed $value,
        bool $preserveNumericTypes = false,
    ): string|array|int|float {
        $type = $field['type'] ?? 'text';

        if (self::isMultiValueField($field)) {
            if (is_array($value)) {
                $values = $value;
            } elseif (in_array($type, array('select', 'select-multi'), true)) {
                // Preserve the existing comma-separated import behavior for multi-select fields.
                $values = array_map('trim', explode(',', (string) $value));
            } else {
                // For every other field type, a scalar is one value, even if it contains commas.
                $values = array($value);
            }

            return array_map(
                static fn(mixed $item): string|int|float => self::normalizeMetadataScalarValue(
                    $type,
                    $item,
                    $preserveNumericTypes,
                ),
                $values,
            );
        }

        return self::normalizeMetadataScalarValue($type, $value, $preserveNumericTypes);
    }

    private static function normalizeMetadataScalarValue(
        string $type,
        mixed $value,
        bool $preserveNumericTypes = false,
    ): string|int|float {
        if ($preserveNumericTypes && (is_int($value) || is_float($value))) {
            return $value;
        }

        $value = trim((string) $value);

        return match ($type) {
            'checkbox' => self::normalizeCheckboxValue($value),
            'number' => str_replace(',', '.', $value),
            default => $value,
        };
    }

    private static function isMultiValueField(array $field): bool
    {
        return ($field['allow_multi_values'] ?? false) === true
            || ($field['type'] ?? '') === 'select-multi';
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
        $type = $field['type'] ?? 'text';
        $isMulti = self::isMultiValueField($field);

        if (is_array($value)) {
            if (!$isMulti) {
                throw new ImproperActionException(
                    sprintf(_('Metadata field %s expects a single value.'), $name)
                );
            }
            if ($value === array()) {
                return;
            }
            $values = $value;
        } elseif ($value === '' || $value === null) {
            return;
        } elseif ($isMulti && in_array($type, array('select', 'select-multi'), true)) {
            // Preserve the existing comma-separated import behavior for multi-select fields.
            $values = array_map('trim', explode(',', (string) $value));
        } else {
            $values = array($value);
        }

        foreach ($values as $item) {
            if ($item !== null && !is_scalar($item)) {
                throw new ImproperActionException(
                    sprintf(_('Metadata field %s contains an invalid value.'), $name)
                );
            }
        }

        if ($type === 'number') {
            foreach ($values as $item) {
                if (
                    $item !== ''
                    && $item !== null
                    && !is_numeric(str_replace(',', '.', trim((string) $item)))
                ) {
                    throw new ImproperActionException(
                        sprintf(_('Metadata field %s expects a number.'), $name)
                    );
                }
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

        $options = array_map(
            static fn(mixed $option): mixed => is_scalar($option) ? mb_strtolower(trim((string) $option)) : $option,
            $options,
        );

        foreach ($values as $option) {
            if ($option !== '' && $option !== null && !in_array(mb_strtolower(trim((string) $option)), $options, true)) {
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
