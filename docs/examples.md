# Examples

## Example 1: User Profile Extensions

Add dynamic profile fields to a User model without changing the database schema.

### Setup

```php
// config/web.php
'modules' => [
    'virtualFields' => [
        'class' => 'eseperio\virtualfields\Module',
        'entityMap' => [
            1 => 'app\models\User',
        ],
    ],
],
```

### Model

```php
// app/models/User.php
namespace app\models;

use yii\db\ActiveRecord;
use eseperio\virtualfields\behaviors\VirtualFieldsBehavior;

class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'virtualFields' => VirtualFieldsBehavior::class,
        ];
    }
    
    public function getObjectType()
    {
        return 1;
    }
}
```

### Creating Fields

```php
use eseperio\virtualfields\models\VirtualFieldDefinition;

// Phone number field
$phone = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'phone_number',
    'label' => 'Phone Number',
    'data_type' => 'string',
    'required' => true,
]);
$phone->save();

// Bio field
$bio = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'bio',
    'label' => 'Biography',
    'data_type' => 'text',
]);
$bio->save();

// Newsletter subscription
$newsletter = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'newsletter',
    'label' => 'Subscribe to Newsletter',
    'data_type' => 'bool',
    'default_value' => '0',
]);
$newsletter->save();
```

### Usage

```php
$user = User::findOne(1);
$user->phone_number = '+1234567890';
$user->bio = 'Software developer and open source enthusiast';
$user->newsletter = true;
$user->save();

echo $user->phone_number; // '+1234567890'
```

## Example 2: Product Specifications

Add dynamic product specifications for an e-commerce site.

### Setup

```php
'entityMap' => [
    2 => 'app\models\Product',
],
```

### Creating Specification Fields

```php
// Weight
$weight = new VirtualFieldDefinition([
    'entity_type' => 2,
    'name' => 'weight',
    'label' => 'Weight (kg)',
    'data_type' => 'float',
]);
$weight->save();

// Dimensions as JSON
$dimensions = new VirtualFieldDefinition([
    'entity_type' => 2,
    'name' => 'dimensions',
    'label' => 'Dimensions',
    'data_type' => 'json',
]);
$dimensions->save();

// Color
$color = new VirtualFieldDefinition([
    'entity_type' => 2,
    'name' => 'color',
    'label' => 'Color',
    'data_type' => 'string',
]);
$color->save();

// In stock
$inStock = new VirtualFieldDefinition([
    'entity_type' => 2,
    'name' => 'in_stock',
    'label' => 'In Stock',
    'data_type' => 'bool',
]);
$inStock->save();
```

### Usage

```php
$product = new Product([
    'name' => 'Laptop',
    'price' => 999.99,
]);

$product->weight = 2.5;
$product->dimensions = [
    'length' => 35,
    'width' => 24,
    'height' => 2,
];
$product->color = 'Silver';
$product->in_stock = true;

$product->save();
```

### Product Grid with Virtual Fields

```php
use yii\grid\GridView;
use eseperio\virtualfields\helpers\GridViewHelper;

$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions(2); // Product entity type

$columns = [
    'id',
    'name',
    'price:currency',
];

$columns = array_merge($columns, GridViewHelper::getColumns($definitions));
$columns[] = ['class' => 'yii\grid\ActionColumn'];

echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => $columns,
]);
```

## Example 3: Multi-tenant Configuration

Different tenants need different fields for the same entity.

### Setup

```php
use eseperio\virtualfields\models\VirtualFieldDefinition;

// Tenant A needs SSN field
if ($tenantId === 'tenant_a') {
    $ssn = new VirtualFieldDefinition([
        'entity_type' => 1,
        'name' => 'ssn',
        'label' => 'Social Security Number',
        'data_type' => 'string',
        'required' => true,
    ]);
    $ssn->save();
}

// Tenant B needs employee ID
if ($tenantId === 'tenant_b') {
    $empId = new VirtualFieldDefinition([
        'entity_type' => 1,
        'name' => 'employee_id',
        'label' => 'Employee ID',
        'data_type' => 'string',
        'required' => true,
    ]);
    $empId->save();
}
```

## Example 4: Event Registration with Custom Fields

Create dynamic registration forms for events.

### Event Model

```php
namespace app\models;

class EventRegistration extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'virtualFields' => VirtualFieldsBehavior::class,
        ];
    }
    
    public function getObjectType()
    {
        return 3; // EventRegistration entity type
    }
}
```

### Creating Event-Specific Fields

```php
// For a conference
$tshirtSize = new VirtualFieldDefinition([
    'entity_type' => 3,
    'name' => 'tshirt_size',
    'label' => 'T-Shirt Size',
    'data_type' => 'string',
    'options' => json_encode(['placeholder' => 'S, M, L, XL']),
]);
$tshirtSize->save();

$dietaryRestrictions = new VirtualFieldDefinition([
    'entity_type' => 3,
    'name' => 'dietary_restrictions',
    'label' => 'Dietary Restrictions',
    'data_type' => 'text',
]);
$dietaryRestrictions->save();

$arrival = new VirtualFieldDefinition([
    'entity_type' => 3,
    'name' => 'arrival_date',
    'label' => 'Arrival Date',
    'data_type' => 'date',
    'required' => true,
]);
$arrival->save();
```

### Registration Form

```php
use yii\widgets\ActiveForm;
use eseperio\virtualfields\helpers\VirtualFieldRenderer;

$form = ActiveForm::begin();

echo $form->field($model, 'name');
echo $form->field($model, 'email');

// Dynamically render event-specific fields
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions(3);

echo VirtualFieldRenderer::renderFields($form, $model, $definitions);

echo Html::submitButton('Register', ['class' => 'btn btn-primary']);
ActiveForm::end();
```

## Example 5: API Integration with JSON Fields

Store complex API responses as JSON virtual fields.

### Setup

```php
$apiResponse = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'social_profiles',
    'label' => 'Social Profiles',
    'data_type' => 'json',
]);
$apiResponse->save();
```

### Usage

```php
$user = User::findOne(1);

// Store complex data structure
$user->social_profiles = [
    'twitter' => [
        'username' => '@johndoe',
        'followers' => 1500,
        'verified' => false,
    ],
    'linkedin' => [
        'url' => 'https://linkedin.com/in/johndoe',
        'connections' => 500,
    ],
    'github' => [
        'username' => 'johndoe',
        'repos' => 25,
        'stars' => 150,
    ],
];

$user->save();

// Access nested data
$twitterFollowers = $user->social_profiles['twitter']['followers'];
```

## Example 6: Calculated Fields with Getters

Combine virtual fields with calculated properties.

### Model

```php
class Product extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'virtualFields' => VirtualFieldsBehavior::class,
        ];
    }
    
    public function getObjectType()
    {
        return 2;
    }
    
    // Calculated field based on virtual field
    public function getTotalWeight()
    {
        $weight = $this->weight ?? 0;
        $packaging = $this->packaging_weight ?? 0;
        return $weight + $packaging;
    }
}
```

## Example 7: Batch Field Creation

Create multiple fields at once programmatically.

```php
use eseperio\virtualfields\models\VirtualFieldDefinition;

$fields = [
    [
        'entity_type' => 1,
        'name' => 'first_name',
        'label' => 'First Name',
        'data_type' => 'string',
        'required' => true,
    ],
    [
        'entity_type' => 1,
        'name' => 'last_name',
        'label' => 'Last Name',
        'data_type' => 'string',
        'required' => true,
    ],
    [
        'entity_type' => 1,
        'name' => 'date_of_birth',
        'label' => 'Date of Birth',
        'data_type' => 'date',
    ],
    [
        'entity_type' => 1,
        'name' => 'preferences',
        'label' => 'User Preferences',
        'data_type' => 'json',
    ],
];

foreach ($fields as $fieldData) {
    $field = new VirtualFieldDefinition($fieldData);
    if (!$field->save()) {
        print_r($field->errors);
    }
}
```

## Example 8: Custom Validation Rules

Add custom validation for virtual fields.

```php
class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'virtualFields' => VirtualFieldsBehavior::class,
        ];
    }
    
    public function rules()
    {
        return [
            // Regular rules
            [['username', 'email'], 'required'],
            
            // Custom validation for virtual field
            ['phone_number', 'match', 'pattern' => '/^\+?[1-9]\d{1,14}$/'],
            ['age', 'integer', 'min' => 18, 'max' => 120],
        ];
    }
    
    public function getObjectType()
    {
        return 1;
    }
}
```

## Example 9: Search with Virtual Fields

Include virtual fields in search models.

```php
class UserSearch extends User
{
    public function rules()
    {
        return [
            [['id', 'username', 'email'], 'safe'],
            [['phone_number', 'bio'], 'safe'], // Virtual fields
        ];
    }
    
    public function search($params)
    {
        $query = User::find();
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        $this->load($params);
        
        if (!$this->validate()) {
            return $dataProvider;
        }
        
        // Filter by regular fields
        $query->andFilterWhere(['id' => $this->id])
              ->andFilterWhere(['like', 'username', $this->username])
              ->andFilterWhere(['like', 'email', $this->email]);
        
        // Note: Virtual fields are loaded after query execution,
        // so filtering must be done differently if needed
        
        return $dataProvider;
    }
}
```

## Tips

1. **Start Simple**: Begin with a few virtual fields and expand as needed
2. **Test Thoroughly**: Virtual fields should be tested like regular attributes
3. **Document Well**: Keep track of which fields exist for each entity
4. **Monitor Performance**: Watch database query counts when using many virtual fields
5. **Use Caching**: Always enable caching in production environments

## Next Steps

- Review [Best Practices](best-practices.md)
- Learn about [Advanced Topics](advanced-topics.md)
- Check the [API Documentation](api-reference.md)
