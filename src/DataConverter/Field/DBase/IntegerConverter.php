<?php declare(strict_types=1);

namespace TotalCRM\DBase\DataConverter\Field\DBase;

use TotalCRM\DBase\DataConverter\Field\AbstractFieldDataConverter;
use TotalCRM\DBase\Enum\FieldType;

/**
 * Class NumberConverter
 * @package TotalCRM\DBase\DataConverter\Field\DBase
 */
class IntegerConverter extends AbstractFieldDataConverter
{
    public static function getType(): string
    {
        return FieldType::INTEGER;
    }

    public function fromBinaryString(string $value)
    {
        $s = trim($value);
        if ('' === $s) {
            return null;
        }
        $s = unpack('V', $value)[1];
        return (int) $s;
    }

    public function toBinaryString($value): string
    {
        if (null === $value) {
            return str_repeat(chr(0x00), $this->column->length);
        }

        return str_pad(
            (string)round($value,0),
            $this->column->length,
            ' ',
            STR_PAD_LEFT
        );
    }
}
