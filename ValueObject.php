<?php

namespace ivankff\valueObjects;

use yii\base\Component;
use ReflectionClass;
use yii\db\ActiveRecord;

/**
 * @property-read ActiveRecord $owner
 */
class ValueObject extends Component implements IValueObject {
    const EVENT_INIT = 'init';

    /**
     * @var ActiveRecord
     */
    private $_owner;
    /**
     * @var string|null
     * `null` if this value object name matches with AR attribute name
     * `string` if not
     */
    private $_arAttribute;
    /**
     * @var array list
     */
    private $_attributes;
    /**
     * @var array
     * k => v
     */
    private $_oldAttributes;

    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    public function behaviors() {
        return [
            ValueObjectsBehavior::class
        ];
    }

    public function toArray() {
        $data = [];
        foreach ($this->attributes() as $attr) {
            $value = $this->$attr;
            if ($value instanceOf ValueObject) {
                $data[$attr] = $value->toArray();
            } else {
                $data[$attr] = $value;
            }
        }

        return $data;
    }

    public function attributes()
    {
        if (!$this->_attributes) {
            $class = new ReflectionClass($this);
            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic()) {
                    $this->_attributes[] = $property->getName();
                }
            }
        }

        return $this->_attributes;
    }

    public function setAttributes($values) {
        if (is_array($values)) {
            $attributes = array_flip($this->attributes());
            foreach ($values as $name => $value) {
                if (isset($attributes[$name])) {
                    $oldValue = $this->$name;
                    if ($oldValue instanceOf IValueObject && is_array($value)) {
                        $this->$name->setAttributes($value);
                    } else {
                        $this->$name = $value;
                    }
                }
            }
        }
    }

    public function getAttributes($attrs = null) {
        $data = [];

        if ($attrs === null) {
            $attrs = $this->attributes();
        }

        foreach ($attrs as $attr) {
            $value = $this->$attr;
            if ($value instanceOf IValueObject) {
                $data[$attr] = $value->toArray();
            } else {
                $data[$attr] = $this->$attr;
            }
        }

        return $data;
    }

    public function setOldAttributes($attrs) {
        $this->_oldAttributes = $attrs;
    }

    public function getIsChanged() {
        return !empty($this->getDirtyAttributes());
    }

    public function getDirtyAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributes();
        }
        $names = array_flip($names);
        $attributes = [];
        if ($this->_oldAttributes === null) {
            foreach ($this->attributes as $name => $value) {
                if (isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach ($this->attributes as $name => $value) {
                if (isset($names[$name]) && (!array_key_exists($name, $this->_oldAttributes) || $value !== $this->_oldAttributes[$name])) {
                    $attributes[$name] = $value;
                }
            }
        }
        return $attributes;
    }

    public function isAttributeChanged($name, $identical = true)
    {
        $attributes = $this->attributes;
        if (isset($attributes[$name], $this->_oldAttributes[$name])) {
            if ($identical) {
                return $attributes[$name] !== $this->_oldAttributes[$name];
            }
            return $attributes[$name] != $this->_oldAttributes[$name];
        }
        return isset($attributes[$name]) || isset($this->_oldAttributes[$name]);
    }

    public function getOldAttribute($attr) {
        return $this->_oldAttributes[$attr];
    }

    public function getAttribute($attr) {
        return $this->$attr;
    }

    /** @return ActiveRecord */
    public function getOwner() { return $this->_owner; }

    /** @param ActiveRecord $owner */
    public function setOwner($owner) { $this->_owner = $owner; }

    /** @return string|null */
    public function getArAttribute() { return $this->_arAttribute; }

    /** @param string|null $arAttribute */
    public function setArAttribute($arAttribute) { $this->_arAttribute = $arAttribute; }

    /** @return array */
    public function getAttributesToSave() { return $this->getAttributes(); }

}
