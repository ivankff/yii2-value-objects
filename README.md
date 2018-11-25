
# Yii2 Value Objects behavior

Extend your ActiveRecord by nested objects and typed collections, serialized and stored in table field

Example

```php
class User extends ActiveRecord
{
    // using value objects require attached behavior
    public function behaviors()
    {
        return [
            'valueObjects' => 'ivankff\valueObjects\ValueObjectsBehavior',
        ];
    }

    /**
     * Value objects map
     *
     * @param static $owner
     * @return array
     */
    public static function valueObjects($owner) {
        // define value objects on model attributes
        return [
            // $this->profile attribute will be an instance of defined anonymous class
            'profile' => new class extends ValueObject {
                public $github;
                public $phones = [];
            },
        ];
    }
}

$user = new User();
$user->profile->github = 'https://github.com/equicolor/';
$user->profile->phones[] = '555-55-555';
$user->save();

```
Now ```profile``` field of ```user``` table contains json:

```json
{"github":"https://github.com/equicolor/","phones":["555-55-555"]}
```
It will be converted to object on afterFind event.

A more complex example with collections

```php
<?php
use ivankff\valueObjects\ValueObject;
use yii\db\ActiveRecord; 

/**
 * @property integer $id
 * @property Offer $offer
 */
class Campaign extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'valueObjects' => 'ivankff\valueObjects\ValueObjectsBehavior',
        ];
    }
    /**
     * Value objects map
     *
     * @param static $owner
     * @return array
     */
    public static function valueObjects($owner) {
        return [
            // you can define value object as simple class
            'offer' => new Offer(['arAttribute' => 'offer_column_in_database']),
        ];
    }

    // other methods ...
}

```

# Roadmap
* Refactoring
* Tests
* Proper error handling
* Validation
* Separate serealization engine

You are welcome to create issues =)
