# Usage Guide

## Basic Usage

### Accessing Virtual Fields

Once virtual fields are defined and the behavior is attached to your model, you can access them just like regular model attributes:

```php
$user = User::findOne(1);

// Get virtual field value
$phoneNumber = $user->phone_number;
echo $user->birth_date;

// Set virtual field value
$user->phone_number = '+1234567890';
$user->birth_date = '1990-01-15';

// Save (automatically saves virtual fields)
if ($user->save()) {
    echo "User and virtual fields saved!";
}
```

### Mass Assignment

Virtual fields work seamlessly with mass assignment:

```php
$user = new User();
$user->load(Yii::$app->request->post());

if ($user->save()) {
    // Both regular and virtual fields are saved
}
```

### Validation

Virtual fields are automatically validated based on their data type and configuration:

```php
$user = new User();
$user->phone_number = 'invalid'; // Will be validated as string
$user->age = 'not a number'; // Will fail if age is defined as 'int'

if (!$user->validate()) {
    // Virtual field validation errors are included
    print_r($user->errors);
}
```

## Working with Forms

### Using VirtualFieldRenderer

The `VirtualFieldRenderer` helper makes it easy to render virtual fields in forms:

```php
use yii\widgets\ActiveForm;
use eseperio\virtualfields\helpers\VirtualFieldRenderer;

$form = ActiveForm::begin();

// Regular fields
echo $form->field($model, 'username');
echo $form->field($model, 'email');

// Get virtual field definitions
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions($model->getObjectType());

// Render all virtual fields automatically
echo VirtualFieldRenderer::renderFields($form, $model, $definitions);

echo Html::submitButton('Save', ['class' => 'btn btn-primary']);
ActiveForm::end();
```

### Rendering Individual Fields

You can also render virtual fields one by one for more control:

```php
foreach ($definitions as $definition) {
    echo VirtualFieldRenderer::renderField($form, $model, $definition, [
        'placeholder' => 'Enter ' . $definition->label,
    ]);
}
```

## Using in DetailView

The `DetailViewHelper` generates attribute configurations for DetailView:

```php
use yii\widgets\DetailView;
use eseperio\virtualfields\helpers\DetailViewHelper;

$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions($model->getObjectType());

$attributes = [
    'id',
    'username',
    'email',
    'created_at:datetime',
];

// Add all virtual fields
$attributes = array_merge($attributes, DetailViewHelper::getAttributes($definitions));

echo DetailView::widget([
    'model' => $model,
    'attributes' => $attributes,
]);
```

### Customizing DetailView Attributes

You can customize how virtual fields appear:

```php
$attributes = DetailViewHelper::getAttributes($definitions, [
    'phone_number' => [
        'format' => 'raw',
        'value' => function($model) {
            return Html::a($model->phone_number, 'tel:' . $model->phone_number);
        },
    ],
]);
```

## Using in GridView

The `GridViewHelper` generates column configurations for GridView:

```php
use yii\grid\GridView;
use eseperio\virtualfields\helpers\GridViewHelper;

$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions(1); // User entity type

$columns = [
    'id',
    'username',
    'email',
];

// Add all virtual fields
$columns = array_merge($columns, GridViewHelper::getColumns($definitions));

// Add action column
$columns[] = ['class' => 'yii\grid\ActionColumn'];

echo GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => $columns,
]);
```

### Customizing GridView Columns

```php
$columns = GridViewHelper::getColumns($definitions, [
    'phone_number' => [
        'headerOptions' => ['style' => 'width: 150px'],
        'contentOptions' => ['class' => 'text-center'],
    ],
]);
```

## Programmatic Field Management

### Creating Field Definitions

```php
use eseperio\virtualfields\models\VirtualFieldDefinition;

$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'custom_field',
    'label' => 'Custom Field',
    'data_type' => 'string',
    'required' => false,
    'multiple' => false,
    'active' => true,
]);

if ($field->save()) {
    echo "Field created successfully";
}
```

### Setting Field Options

```php
$field->setOptionsArray([
    'min' => 1,
    'max' => 100,
    'placeholder' => 'Enter value between 1 and 100',
]);
$field->save();
```

### Deactivating Fields

```php
$field = VirtualFieldDefinition::findOne(['entity_type' => 1, 'name' => 'old_field']);
$field->active = false;
$field->save();
```

## Direct Service Usage

You can interact with virtual fields directly through the service:

```php
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');

// Get all definitions for an entity type
$definitions = $service->getDefinitions(1);

// Get values for a specific entity
$values = $service->getValues(1, $userId);

// Set a single value
$service->setValue(1, $userId, 'phone_number', '+1234567890');

// Set multiple values
$service->setValues(1, $userId, [
    'phone_number' => '+1234567890',
    'birth_date' => '1990-01-15',
]);

// Delete all values for an entity
$service->deleteValues(1, $userId);

// Clear cache
$service->clearCache(1);
```

## Working with Different Data Types

### String and Text

```php
$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'bio',
    'data_type' => 'text', // For long text
]);
```

### Numbers

```php
// Integer
$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'age',
    'data_type' => 'int',
]);

// Float
$field = new VirtualFieldDefinition([
    'entity_type' => 2,
    'name' => 'rating',
    'data_type' => 'float',
]);
```

### Boolean

```php
$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'newsletter_subscription',
    'data_type' => 'bool',
]);
```

### Dates

```php
// Date only
$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'birth_date',
    'data_type' => 'date',
]);

// Date and time
$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'appointment_time',
    'data_type' => 'datetime',
]);
```

### JSON

```php
$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'preferences',
    'data_type' => 'json',
]);

// Usage
$user->preferences = [
    'theme' => 'dark',
    'language' => 'en',
    'notifications' => true,
];
$user->save();
```

## Next Steps

- Review [Best Practices](best-practices.md)
- Check out [Examples](examples.md)
- Learn about [Advanced Topics](advanced-topics.md)
