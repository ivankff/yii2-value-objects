<?php

namespace ivankff\valueObjects;

use \yii\db\ActiveRecord;
use \yii\helpers\Json;

class ValueObjectsBehavior extends \yii\base\Behavior {

    public static $classMap = [];

    private $jsonMap = [];
    /**
     * @var IValueObject[]
     */
    private $objectsMap = [];

    private $_initialized = false;

    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'initObjects',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_BEFORE_INSERT => 'putJson',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'putJson',
            ActiveRecord::EVENT_AFTER_INSERT => 'putObjects',
            ActiveRecord::EVENT_AFTER_UPDATE => 'putObjects',
        ];
    }

    protected function getValueObjectAttributes() {
        $class = get_class($this->owner);
        if (!isset(self::$classMap[$class])) {
            if (!method_exists($class, 'valueObjects')) {
                // у самых глубоких объектов не будет такого метода
                $attributes = [];
            } else {
                $attributes = $class::valueObjects($this->owner);
            }
            self::$classMap[$class] = $attributes;
        }

        return self::$classMap[$class];
    }

    public function initObjects() {
        if (!$this->_initialized) {
            $this->createObjects();
            // BACKLOG В active record нет смысла инстанциировать value objects сразу, но в Model есть
            $this->putObjects();
            $this->setOwnerOldAttributes();
            $this->_initialized = true;
        }
    }

    public function afterFind() {
        $this->fillObjects();
        $this->putObjects();
        $this->setOwnerOldAttributes();
    }

    protected function fillObjects() {
        foreach ($this->objectsMap as $attribute => $object) {
            $this->fillObject($object, $attribute);
            $object->setOldAttributes($object->attributes);
        }
    }

    /**
     * BACKLOG split decoding and mapping
     * @param IValueObject $object
     * @param $attribute
     */
    protected function fillObject($object, $attribute) {
        $arAttribute = $object->getArAttribute() ?: $attribute;
        $json = $this->owner->$arAttribute;

        if (is_string($json) && strlen($json)) {
            try {
                $array = json_decode($json, true);
                $object->setAttributes($array);
            } catch (\Exception $e) {
                throw new ValueObjectMappingException('Error on creating object', 0, $e);
            }
        } elseif (is_array($json)) {
            $object->setAttributes($json);
        }
    }

    protected function createObjects() {
        foreach ($this->getValueObjectAttributes() as $attribute => $class) {
            $this->objectsMap[$attribute] = $this->createObject($attribute, $class);
        }
    }

    protected function createObject($attribute, $class) {
        if ($class instanceof IValueObject)
            $object = clone $class;
        else
            $object = new $class;

        $object->setOwner($this->owner);
        return $object;
    }

    protected function createJson($attribute) {
        $json = $this->objectsMap[$attribute]->getAttributesToSave();
        $this->jsonMap[$attribute] = $json;

        return $json;
    }

    protected function getJson($attribute) {
        if (!isset($this->jsonMap[$attribute])) {
            $this->jsonMap[$attribute] = $this->createJson($attribute);
        }

        return $this->jsonMap[$attribute];
    }

    protected function getObject($attribute) {
        if (!isset($this->objectsMap[$attribute])) {
            // BACKLOG i dont know how to get class there. Is it neccesarry at all?
            throw new \Error('not implemented');
            // $this->objectsMap[$attribute] = $this->createObject($attribute);
        }

        return $this->objectsMap[$attribute];
    }

    public function putJson() {
        foreach ($this->getValueObjectAttributes() as $attribute => $object) {
            $arAttribute = $object->getArAttribute() ?: $attribute;
            $this->owner->$arAttribute = $this->getJson($attribute);
        }
    }

    public function putObjects() {
        foreach ($this->getValueObjectAttributes() as $attribute => $object) {
            $arAttribute = $object->getArAttribute() ?: $attribute;
            $this->owner->$arAttribute = $this->getObject($attribute);
        }
    }

    protected function setOwnerOldAttributes() {
        if ($this->owner instanceOf \yii\db\ActiveRecordInterface && !$this->owner->isNewRecord) {
            foreach ($this->objectsMap as $attribute => $object) {
                $attribute = $object->getArAttribute() ?: $attribute;
                $this->owner->setOldAttribute($attribute, $object);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->objectsMap))
            return $this->objectsMap[$name];

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if (array_key_exists($name, $this->objectsMap))
            return true;

        return parent::canGetProperty($name, $checkVars);
    }

}
