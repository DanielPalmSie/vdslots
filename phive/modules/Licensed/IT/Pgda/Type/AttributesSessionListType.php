<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Services\PgdaEntity;
use Rakit\Validation\RuleQuashException;

/**
 * Class AttributesSessionListType
 * @package IT\Pgda\Type
 */
class AttributesSessionListType extends PgdaEntity
{
    /**
     * @var AttributesSessionType[]
     */
    protected $attributes_session = [];

    /**
     * @var array
     */
    protected $fillable = [
        'attributes_session',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->setAttributesSession();
        return $this;
    }

    /**
     * @throws RuleQuashException
     */
    protected function setAttributesSession()
    {
        $attributes_session = $this->attributes_session;
        $this->attributes_session = [];
        foreach ($attributes_session as $key => $attribute_session) {
            $this->attributes_session[$key] = (new AttributesSessionType())->fill($attribute_session);
            if (! empty($this->attributes_session[$key]->errors)) {
                $this->errors = array_merge($this->errors, $this->attributes_session[$key]->errors);
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAttributesSession(): array
    {
        $result_attributes_session_list = [];
        foreach ($this->attributes_session as $attribute_session) {
            if (! ($attribute_session instanceof AttributesSessionType)) {
                throw new \Exception('Attributes Session item isn\'t a AttributesSessionType object');
            }
            foreach ($attribute_session->toArray() as $name => $field) {
                $result_attributes_session_list[] = $field;
            }
        }

        return $result_attributes_session_list;
    }

    /**
     * @return int
     */
    public function getNumberAttributes(): int
    {
        return count($this->attributes_session);
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        $format = "";
        $format_string = end($this->attributes_session)->getFormat();
        for ($i = 0; $i < $this->getNumberAttributes(); $i++) {
            $format .= $format_string;
        }

        return $format;
    }

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    public function toArray(array $array = []): array
    {
        return $this->getAttributesSession();
    }
}