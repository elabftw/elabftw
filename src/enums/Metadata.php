<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum Metadata: string
{
    case Elabftw = 'elabftw'; // namespace in metadata root
    case ExtraFields = 'extra_fields'; // holds the extra fields
    case DisplayMainText = 'display_main_text'; // key in elabftw namespace
    case Type = 'type'; // the type of an extra field
    case Description = 'description';
    case Value = 'value'; // holds the selected/input value of an extra field
    case Options = 'options'; // options for the dropdown/radio element
    case Position = 'position'; // number to order the extra fields
    case BlankValueOnDuplicate = 'blank_value_on_duplicate'; // is value of extra field to be blanked when the entity is duplicated
    case AllowMultiValues = 'allow_multi_values'; // for type=select, can multiple options be selected
    case Groups = 'extra_fields_groups'; // will be found in elabftw namespace
    case GroupId = 'group_id';
}
