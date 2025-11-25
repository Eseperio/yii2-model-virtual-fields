# Best Practices

## Entity Type Mapping

### Choose Stable IDs

Entity type IDs should be stable and never change once in production:

```php
// GOOD - Use a consistent numbering scheme
'entityMap' => [
    1 => 'app\models\User',
    2 => 'app\models\Product',
    3 => 'app\models\Order',
],

// BAD - Don't use arbitrary or changing IDs
'entityMap' => [
    42 => 'app\models\User',
    999 => 'app\models\Product',
],
```

### Use Constants

Consider using constants for entity type IDs:

```php
class EntityTypes
{
    const USER = 1;
    const PRODUCT = 2;
    const ORDER = 3;
}

// In configuration
'entityMap' => [
    EntityTypes::USER => 'app\models\User',
    EntityTypes::PRODUCT => 'app\models\Product',
    EntityTypes::ORDER => 'app\models\Order',
],

// In model
public function getObjectType()
{
    return EntityTypes::USER;
}
```

## Field Naming

### Use Descriptive Names

Choose clear, descriptive names that won't conflict with future model attributes:

```php
// GOOD
'name' => 'customer_phone_number'
'name' => 'product_warranty_period'

// AVOID - too generic, likely to conflict
'name' => 'phone'
'name' => 'status'
'name' => 'type'
```

### Follow Conventions

Use the same naming conventions as your database columns:

```php
// If your database uses snake_case
'name' => 'birth_date'
'name' => 'home_address'

// Stay consistent throughout your application
```

### Avoid Reserved Words

The extension validates against common reserved names, but be mindful:

```php
// AVOID
'name' => 'id'
'name' => 'attributes'
'name' => 'errors'
```

## Performance Optimization

### Enable Caching

Always configure a cache component for production:

```php
'components' => [
    'cache' => [
        'class' => 'yii\caching\RedisCache',
        // or
        'class' => 'yii\caching\FileCache',
    ],
],
```

### Limit Virtual Fields

Don't go overboard with virtual fields. Consider:

- Each virtual field adds database queries
- Too many fields can impact performance
- Regular model attributes are faster

**Rule of thumb:** Use virtual fields for truly dynamic data that changes frequently or varies by deployment.

### Eager Load When Possible

If you're displaying multiple models with virtual fields:

```php
// Instead of loading fields for each model in a loop
foreach ($users as $user) {
    // Virtual fields are loaded here (N+1 problem)
    echo $user->phone_number;
}

// Consider loading definitions once
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$definitions = $service->getDefinitions(1); // Cache this
```

## Validation

### Set Appropriate Requirements

Only mark fields as required if they truly are:

```php
$field = new VirtualFieldDefinition([
    'required' => true, // Use judiciously
]);
```

### Use Data Types Wisely

Choose the most restrictive data type that fits your needs:

```php
// GOOD - specific type
'data_type' => 'int' // For age, count, etc.
'data_type' => 'date' // For dates without time

// LESS IDEAL - too permissive
'data_type' => 'string' // For everything
```

## Database Considerations

### Index Strategy

The extension creates appropriate indexes, but monitor your database:

- The `virtual_field_value` table grows with data
- Consider archiving old data
- Monitor query performance

### Backup and Migration

Include virtual field data in your backup strategy:

```bash
# Backup includes virtual field tables
mysqldump -u user -p database > backup.sql
```

When migrating:

1. Export virtual field definitions
2. Apply them to the new environment
3. Values will migrate with entity records

## Security

### Validate Field Names

The extension validates field names automatically, but be aware:

```php
// User-generated field names should be reviewed
$field = new VirtualFieldDefinition([
    'name' => $userInput, // Validated by FieldNameValidator
]);

if (!$field->validate()) {
    // Handle validation errors
}
```

### Sanitize Values

Virtual field values are stored as text and should be sanitized:

```php
// When displaying
echo Html::encode($model->custom_field);

// JSON fields are already safe if properly structured
```

### Access Control

Implement access control for field management:

```php
public function actionCreateField()
{
    if (!Yii::$app->user->can('manageVirtualFields')) {
        throw new ForbiddenHttpException();
    }
    
    // Create field logic
}
```

## Testing

### Test Virtual Fields

Include virtual fields in your test suite:

```php
public function testVirtualFieldSaving()
{
    $user = new User([
        'username' => 'test',
        'email' => 'test@example.com',
    ]);
    
    $user->phone_number = '+1234567890';
    $this->assertTrue($user->save());
    
    $user->refresh();
    $this->assertEquals('+1234567890', $user->phone_number);
}
```

### Mock When Necessary

For unit tests, you can mock the service:

```php
$mockService = $this->createMock(VirtualFieldService::class);
// Configure mock behavior
```

## Documentation

### Document Your Fields

Keep documentation of which fields exist for each entity type:

```markdown
## User Virtual Fields

- `phone_number` (string) - User's phone number
- `birth_date` (date) - Date of birth
- `newsletter_subscription` (bool) - Newsletter opt-in status
```

### Comment Field Creation

When creating fields programmatically, add comments:

```php
// Customer requested field for tracking referral source
$field = new VirtualFieldDefinition([
    'entity_type' => EntityTypes::USER,
    'name' => 'referral_source',
    'label' => 'How did you hear about us?',
    'data_type' => 'string',
]);
```

## Monitoring

### Log Field Changes

Consider logging when fields are created or modified:

```php
Yii::info("Virtual field created: {$field->name} for entity type {$field->entity_type}", 'virtualFields');
```

### Track Usage

Monitor which fields are actually being used:

```php
// Periodically check for unused fields
$unusedFields = VirtualFieldDefinition::find()
    ->where(['active' => true])
    ->andWhere(['not exists', 
        VirtualFieldValue::find()
            ->where('definition_id = virtual_field_definition.id')
    ])
    ->all();
```

## Migration Strategy

### Development to Production

1. Create fields in development
2. Export field definitions
3. Create migration script:

```php
class m240101_000000_add_user_virtual_fields extends Migration
{
    public function safeUp()
    {
        $fields = [
            ['entity_type' => 1, 'name' => 'phone_number', 'data_type' => 'string'],
            ['entity_type' => 1, 'name' => 'birth_date', 'data_type' => 'date'],
        ];
        
        foreach ($fields as $fieldData) {
            $field = new VirtualFieldDefinition($fieldData);
            $field->save();
        }
    }
    
    public function safeDown()
    {
        VirtualFieldDefinition::deleteAll([
            'entity_type' => 1,
            'name' => ['phone_number', 'birth_date'],
        ]);
    }
}
```

## Troubleshooting

### Clear Cache Issues

If fields aren't appearing:

```php
// Clear the cache
$module = Yii::$app->getModule('virtualFields');
$service = $module->get('service');
$service->clearCache();

// Or clear all application cache
Yii::$app->cache->flush();
```

### Check Field Status

Ensure fields are active:

```php
$field = VirtualFieldDefinition::findOne(['name' => 'problem_field']);
var_dump($field->active); // Should be true
```

### Verify Behavior Attachment

Ensure the behavior is properly attached:

```php
$user = new User();
$behaviors = $user->getBehaviors();
var_dump(isset($behaviors['virtualFields'])); // Should be true
```

## Summary

- Use stable entity type IDs
- Choose descriptive, collision-safe field names
- Enable caching in production
- Limit the number of virtual fields
- Choose appropriate data types
- Test thoroughly
- Document your fields
- Monitor usage and performance
