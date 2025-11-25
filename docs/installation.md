# Installation Guide

## Requirements

- PHP >= 7.4
- Yii2 >= 2.0.14

## Installation via Composer

Run the following command in your project directory:

```bash
composer require eseperio/yii2-model-virtual-fields
```

## Configuration

### 1. Configure the Module

Add the module to your application configuration file (e.g., `config/web.php` or `config/main.php`):

```php
return [
    // ... other configuration
    'modules' => [
        'virtualFields' => [
            'class' => 'eseperio\virtualfields\Module',
            'entityMap' => [
                1 => 'app\models\User',
                2 => 'app\models\Product',
                3 => 'app\models\Order',
                // Add more entity type mappings as needed
            ],
        ],
        // ... other modules
    ],
    // ... other configuration
];
```

**Important:** The `entityMap` property maps integer IDs to fully qualified class names. Choose stable integer IDs that won't change in your application.

### 2. Run Database Migrations

Apply the required database migrations:

```bash
php yii migrate --migrationPath=@vendor/eseperio/yii2-model-virtual-fields/migrations
```

This will create two tables:
- `virtual_field_definition` - stores field definitions
- `virtual_field_value` - stores field values

### 3. Attach Behavior to Models

Add the `VirtualFieldsBehavior` to any ActiveRecord model that should support virtual fields:

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

### 4. Create Virtual Fields

You can create virtual field definitions through code or a UI:

```php
use eseperio\virtualfields\models\VirtualFieldDefinition;

$field = new VirtualFieldDefinition([
    'entity_type' => 1,
    'name' => 'phone_number',
    'label' => 'Phone Number',
    'data_type' => 'string',
    'required' => true,
    'active' => true,
]);
$field->save();
```

## Next Steps

- Read the [Usage Guide](usage-guide.md) to learn how to use virtual fields
- Check out [Examples](examples.md) for common use cases
- Review [Best Practices](best-practices.md) for production deployments
