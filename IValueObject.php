<?php

namespace ivankff\valueObjects;

use yii\db\ActiveRecord;

interface IValueObject {

    /**
     * @param $attributes
     */
    public function setAttributes($attributes);

    /**
     * @return ActiveRecord
     */
    public function getOwner();

    /**
     * @param ActiveRecord $owner
     */
    public function setOwner($owner);

    /**
     * @return string|null
     */
    public function getArAttribute();

    /**
     * @param string|null $arAttribute
     */
    public function setArAttribute($arAttribute);

}
