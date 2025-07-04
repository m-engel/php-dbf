<?php declare(strict_types=1);

namespace TotalCRM\DBase\DataConverter\Record;

use TotalCRM\DBase\DataConverter\Encoder\EncoderInterface;
use TotalCRM\DBase\DataConverter\Field\DBase\CharConverter;
use TotalCRM\DBase\DataConverter\Field\DBase\DateConverter;
use TotalCRM\DBase\DataConverter\Field\DBase\IgnoreConverter;
use TotalCRM\DBase\DataConverter\Field\DBase\IntegerConverter;
use TotalCRM\DBase\DataConverter\Field\DBase\LogicalConverter;
use TotalCRM\DBase\DataConverter\Field\DBase\MemoConverter;
use TotalCRM\DBase\DataConverter\Field\DBase\NumberConverter;
use TotalCRM\DBase\DataConverter\Field\FieldDataConverterInterface;
use TotalCRM\DBase\Exception\InvalidColumnException;
use TotalCRM\DBase\Header\Column;
use TotalCRM\DBase\Record\AbstractRecord;
use TotalCRM\DBase\Record\RecordInterface;
use TotalCRM\DBase\Table\Table;

class DBaseDataConverter implements RecordDataConverterInterface
{
    /** @var Table */
    protected $table;

    /** @var EncoderInterface */
    protected $encoder;

    public function __construct(Table $table, EncoderInterface $encoder)
    {
        $this->table = $table;
        $this->encoder = $encoder;
    }

    /**
     * @return FieldDataConverterInterface[]
     */
    protected static function getFieldConverters(): array
    {
        return [
            DateConverter::class,
            IgnoreConverter::class,
            LogicalConverter::class,
            MemoConverter::class,
            NumberConverter::class,
            CharConverter::class,
            IntegerConverter::class
        ];
    }

    /**
     * @return array [deleted, data]
     */
    public function fromBinaryString(string $rawData): array
    {
        $result = [
            'deleted'     => $rawData && (AbstractRecord::FLAG_DELETED === ord($rawData[0])),
            'data'        => [],
        ];

        foreach ($this->table->header->columns as $column) {
            $normalValue = null;
            if ($rawData) {
                $rawValue = substr($rawData, $column->bytePosition, $column->length);
                $normalValue = $this->normalizeField($column, $rawValue);
            }
            $result['data'][$column->name] = $normalValue;
        }

        return $result;
    }

    public function toBinaryString(RecordInterface $record): string
    {
        $result = chr($record->isDeleted() ? AbstractRecord::FLAG_DELETED : AbstractRecord::FLAG_NOT_DELETED);
        foreach ($this->table->header->columns as $column) {
            $result .= $this->denormalizeField($column, $record);
        }

        if (($act = strlen($result)) !== ($len = $this->table->header->recordByteLength)) {
            throw new \LogicException(sprintf('Invalid number of bytes in binary string. Expected: %d. Actual: %d', $len, $act));
        }

        return $result;
    }

    private function findFieldConverter(Column $column): FieldDataConverterInterface
    {
        foreach (static::getFieldConverters() as $class) {
            if ($column->type === $class::getType()) {
                return new $class($this->table, $column, $this->encoder);
            }
        }
        
        throw new InvalidColumnException(sprintf('Cannot find Field for `%s` data type', $column->type));
    }

    /**
     * @return bool|false|float|int|string|null
     *
     * @throws InvalidColumnException If dataType not exists
     */
    protected function normalizeField(Column $column, string $value)
    {
        return $this->findFieldConverter($column)->fromBinaryString($value);
    }

    protected function denormalizeField(Column $column, RecordInterface $record): string
    {
        $value = $record->getGenuine($column->name); //todo memo get raw value

        return $this->findFieldConverter($column)->toBinaryString($value);
    }
}
