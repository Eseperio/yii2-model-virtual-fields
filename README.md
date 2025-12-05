# yii2-model-virtual-fields

A Yii2 extension for adding virtual (dynamic) fields to ActiveRecord models without modifying database tables.

## Overview

This library allows developers to define new fields for any ActiveRecord entity at runtime. These virtual fields:

* Have a name, datatype, and validation rules
* Are stored in dedicated tables managed by the extension
* Are exposed on the model as **native properties** (`$model->virtualFieldName`)
* Automatically integrate with:
  * ActiveForm
  * DetailView
  * GridView
  * Model validation
  * Model persistence

## Features

- ✅ Add dynamic fields to existing models without altering database schemas
- ✅ Support for multiple data types: string, int, float, bool, date, datetime, json, text
- ✅ Automatic validation based on field type and requirements
- ✅ Collision-safe field naming (prevents conflicts with existing properties)
- ✅ Seamless integration with Yii2 widgets (forms, grids, detail views)
- ✅ Caching support for improved performance
- ✅ Full test coverage

## Installation

Install via Composer:

```bash
composer require eseperio/yii2-model-virtual-fields
```

## Configuration

### 1. Configure the Module

Add the module to your application configuration:

```php
'modules' => [
    'virtualFields' => [
        'class' => 'eseperio\virtualfields\Module',
        'entityMap' => [
            1 => 'app\models\User',
            2 => 'app\models\Product',
            3 => 'app\models\Order',
            // ... more entity type mappings
        ],
    ],
],
```

The `entityMap` property maps **integer IDs** to fully qualified class names of your ActiveRecord models. The extension uses these integer IDs internally to identify entity types.

### 2. Run Migrations

Apply the database migrations:

```bash
php yii migrate --migrationPath=@vendor/eseperio/yii2-model-virtual-fields/migrations
```

This will create the necessary tables:
- `virtual_field_definition` - stores field definitions
- `virtual_field_value` - stores field values

### 3. Attach Behavior to Models

Add the behavior to any ActiveRecord model you want to support virtual fields:

```php
namespace app\models;

use yii\db\ActiveRecord;
use eseperio\virtualfields\behaviors\VirtualFieldsBehavior;

class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'virtualFields' => [
                'class' => VirtualFieldsBehavior::class,
            ],
        ];
    }

    /**
     * Return the entity type ID from the module configuration
     */
    public function getObjectType()
    {
        return 1; // Must match the ID in entityMap
    }
}
```

## Usage

### Creating Virtual Fields

Create virtual field definitions programmatically or through a UI:

```php
use eseperio\virtualfields\models\VirtualFieldDefinition;

$field = new VirtualFieldDefinition([
    'entity_type' => 1, // User entity type
    'name' => 'phone_number',
    'label' => 'Phone Number',
    'data_type' => 'string',
    'required' => true,
    'active' => true,
]);
$field->save();

$field2 = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'birth_date',
    'label' => 'Date of Birth',
    'data_type' => 'date',
    'required' => false,
    'active' => true,
]);
$field2->save();
```

### Using Virtual Fields

Once defined, virtual fields work like native model properties:

```php
$user = User::findOne(1);

// Get virtual field value
echo $user->phone_number;

// Set virtual field value
$user->phone_number = '+1234567890';
$user->birth_date = '1990-01-15';

// Save (automatically saves virtual fields)
$user->save();

// Mass assignment works too
$user->load(Yii::$app->request->post());
$user->save();
```

**Important: Saving Virtual Fields Only**

When you modify **only virtual fields** (no native AR attributes), you need to call `saveVirtualFields()` after `save()` to ensure changes are persisted:

```php
$user = User::findOne(1);

// Only modifying virtual fields
$user->phone_number = '+1234567890';
$user->preferences = ['theme' => 'dark'];

// Must call saveVirtualFields() to persist changes
$user->save();
$user->saveVirtualFields();

// Or in one line:
$user->save() && $user->saveVirtualFields();
```

**Why?** When only virtual fields change, Yii2's `save()` may return early without triggering the `afterUpdate` event (since no AR attributes are "dirty"). The `saveVirtualFields()` method detects this and manually saves the virtual fields.

**When it's NOT needed:**
- When you also modify at least one native AR attribute (e.g., `$user->username = 'newname'`)
- For new records (inserts always trigger the proper events)
- When using mass assignment with mixed fields

### Using in Forms

Virtual fields integrate seamlessly with ActiveForm:

```php
use eseperio\virtualfields\helpers\VirtualFieldRenderer;

$form = ActiveForm::begin();

// Render standard fields
echo $form->field($model, 'username');
echo $form->field($model, 'email');

// Get virtual field definitions
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions($model->getObjectType());

// Render all virtual fields
echo VirtualFieldRenderer::renderFields($form, $model, $definitions);

// Or render individual fields
foreach ($definitions as $definition) {
    echo VirtualFieldRenderer::renderField($form, $model, $definition);
}

ActiveForm::end();
```

### Using in DetailView

```php
use yii\widgets\DetailView;
use eseperio\virtualfields\helpers\DetailViewHelper;

// Get virtual field definitions
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions($model->getObjectType());

// Build attributes array
$attributes = [
    'id',
    'username',
    'email',
];

// Add virtual field attributes
$attributes = array_merge($attributes, DetailViewHelper::getAttributes($definitions));

// Render DetailView
echo DetailView::widget([
    'model' => $model,
    'attributes' => $attributes,
]);
```

### Using in GridView

```php
use yii\grid\GridView;
use eseperio\virtualfields\helpers\GridViewHelper;

// Get virtual field definitions
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions(1); // User entity type

// Build columns array
$columns = [
    'id',
    'username',
    'email',
];

// Add virtual field columns
$columns = array_merge($columns, GridViewHelper::getColumns($definitions));

// Render GridView
echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => $columns,
]);
```

## Supported Data Types

The extension supports the following data types out of the box:

| Type | Description | PHP Type | Example |
|------|-------------|----------|---------|
| `string` | Short text | string | "John Doe" |
| `text` | Long text | string | "Lorem ipsum..." |
| `int` | Integer number | integer | 42 |
| `float` | Decimal number | float | 3.14 |
| `bool` | Boolean | boolean | true/false |
| `date` | Date | string | "2024-01-15" |
| `datetime` | Date and time | string | "2024-01-15 14:30:00" |
| `json` | JSON data | array | {"key": "value"} |

### Custom Data Types

You can register custom data types:

```php
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');

$service->registerDataType(
    'custom_type',
    function($value) { /* cast */ return $value; },
    function($value) { /* serialize */ return (string)$value; },
    function($value) { /* deserialize */ return $value; }
);
```

## Field Name Validation

The extension automatically validates field names to prevent collisions:

- ✅ Checks against database columns
- ✅ Checks against existing properties
- ✅ Checks against getter/setter methods
- ✅ Validates naming patterns (alphanumeric + underscore)
- ✅ Prevents use of reserved names

## Architecture

### Entity Type Mapping

The extension uses **integer IDs** to identify entity types internally. This design:

- Provides a stable, efficient identifier
- Allows flexibility in class naming/refactoring
- Enables multi-tenant scenarios

Configure the mapping in the module's `entityMap` property.

### Database Schema

**virtual_field_definition** table stores:
- Entity type (integer)
- Field name
- Data type
- Validation rules (required, multiple values)
- Options (JSON)
- Active status

**virtual_field_value** table stores:
- Definition reference
- Entity type and ID
- Value (stored as text, cast by type)

### Caching

Field definitions are cached for performance. Cache is automatically invalidated when definitions change.

## Best Practices

1. **Choose appropriate entity type IDs**: Use a systematic numbering scheme
2. **Be mindful of field names**: Avoid names that might conflict with future model properties
3. **Use appropriate data types**: This ensures proper validation and rendering
4. **Test thoroughly**: Virtual fields should be tested like any other model attribute
5. **Consider performance**: Virtual fields require additional database queries

## Troubleshooting

### Virtual fields not appearing

- Ensure the behavior is attached to your model
- Verify `getObjectType()` returns the correct entity type ID
- Check that field definitions are active
- Clear cache: `Yii::$app->cache->flush()`

### Validation not working

- Ensure validation rules are properly set in the field definition
- Check that the behavior's `beforeValidate` event is being triggered

### Name collision errors

- Choose different field names that don't conflict with existing properties
- Review the validation error message for specific conflicts

## Testing

This library includes comprehensive functional tests using Codeception and SQLite.

### Running Tests

1. Install dependencies:
```bash
composer install
```

2. Run migrations to set up the test database:
```bash
composer test-migrate
```

3. Run functional tests:
```bash
composer test-functional
```

### Migration Structure

The library has two sets of migrations:

- **`migrations/`** - Library migrations (virtual field tables)
  - `m000000_000000_create_virtual_field_definition_table.php`
  - `m000000_000001_create_virtual_field_value_table.php`

- **`tests/_app/migrations/`** - Test app migrations (test models)
  - `m000000_000002_create_test_model_table.php`
  - `m000000_000003_create_product_table.php`

The `composer test-migrate` script runs both sets of migrations in the correct order.

## License

MIT

## Contributing

Contributions are welcome! Please submit pull requests or open issues on GitHub.

## Credits

Developed by Eseperio.

