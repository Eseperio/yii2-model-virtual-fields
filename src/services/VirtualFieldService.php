<?php

namespace eseperio\virtualfields\services;

use Yii;
use yii\base\Component;
use yii\caching\CacheInterface;
use yii\db\ActiveRecord;
use eseperio\virtualfields\models\VirtualFieldDefinition;
use eseperio\virtualfields\models\VirtualFieldValue;

/**
 * VirtualFieldService
 * 
 * Central service responsible for managing virtual field definitions and values.
 */
class VirtualFieldService extends Component
{
    /**
     * @var int Cache duration in seconds (default: 1 hour)
     */
    public $cacheDuration = 3600;

    /**
     * @var array Registered data type handlers
     */
    protected $dataTypes = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        // Register default data types
        $this->registerDefaultDataTypes();
    }

    /**
     * Register default data type handlers
     */
    protected function registerDefaultDataTypes()
    {
        $this->dataTypes = [
            'string' => [
                'cast' => function ($value) {
                    return (string) $value;
                },
                'serialize' => function ($value) {
                    return (string) $value;
                },
                'deserialize' => function ($value) {
                    return (string) $value;
                },
            ],
            'int' => [
                'cast' => function ($value) {
                    return (int) $value;
                },
                'serialize' => function ($value) {
                    return (string) $value;
                },
                'deserialize' => function ($value) {
                    return (int) $value;
                },
            ],
            'float' => [
                'cast' => function ($value) {
                    return (float) $value;
                },
                'serialize' => function ($value) {
                    return (string) $value;
                },
                'deserialize' => function ($value) {
                    return (float) $value;
                },
            ],
            'bool' => [
                'cast' => function ($value) {
                    return (bool) $value;
                },
                'serialize' => function ($value) {
                    return $value ? '1' : '0';
                },
                'deserialize' => function ($value) {
                    return $value === '1' || $value === 1 || $value === true;
                },
            ],
            'date' => [
                'cast' => function ($value) {
                    if ($value instanceof \DateTime) {
                        return $value->format('Y-m-d');
                    }
                    return $value;
                },
                'serialize' => function ($value) {
                    if ($value instanceof \DateTime) {
                        return $value->format('Y-m-d');
                    }
                    return $value;
                },
                'deserialize' => function ($value) {
                    return $value;
                },
            ],
            'datetime' => [
                'cast' => function ($value) {
                    if ($value instanceof \DateTime) {
                        return $value->format('Y-m-d H:i:s');
                    }
                    return $value;
                },
                'serialize' => function ($value) {
                    if ($value instanceof \DateTime) {
                        return $value->format('Y-m-d H:i:s');
                    }
                    return $value;
                },
                'deserialize' => function ($value) {
                    return $value;
                },
            ],
            'json' => [
                'cast' => function ($value) {
                    if (is_string($value)) {
                        return json_decode($value, true);
                    }
                    return $value;
                },
                'serialize' => function ($value) {
                    return json_encode($value);
                },
                'deserialize' => function ($value) {
                    return json_decode($value, true);
                },
            ],
            'text' => [
                'cast' => function ($value) {
                    return (string) $value;
                },
                'serialize' => function ($value) {
                    return (string) $value;
                },
                'deserialize' => function ($value) {
                    return (string) $value;
                },
            ],
        ];
    }

    /**
     * Get field definitions for an entity type
     * 
     * @param int $entityType
     * @param bool $activeOnly
     * @return VirtualFieldDefinition[]
     */
    public function getDefinitions($entityType, $activeOnly = true)
    {
        $cacheKey = "virtualfields:definitions:{$entityType}:" . ($activeOnly ? 'active' : 'all');
        
        $cache = $this->getCache();
        if ($cache) {
            $definitions = $cache->get($cacheKey);
            if ($definitions !== false) {
                return $definitions;
            }
        }

        $query = VirtualFieldDefinition::find()
            ->where(['entity_type' => $entityType]);
        
        if ($activeOnly) {
            $query->andWhere(['active' => true]);
        }
        
        $definitions = $query->all();

        if ($cache) {
            $cache->set($cacheKey, $definitions, $this->cacheDuration);
        }

        return $definitions;
    }

    /**
     * Get field values for a specific entity instance
     * 
     * @param int $entityType
     * @param int $entityId
     * @return array Associative array of field name => value
     */
    public function getValues($entityType, $entityId)
    {
        $definitions = $this->getDefinitions($entityType);
        $definitionIds = array_map(function ($def) {
            return $def->id;
        }, $definitions);

        if (empty($definitionIds)) {
            return [];
        }

        $values = VirtualFieldValue::find()
            ->where([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'definition_id' => $definitionIds,
            ])
            ->indexBy('definition_id')
            ->all();

        $result = [];
        foreach ($definitions as $definition) {
            $value = $values[$definition->id] ?? null;
            
            if ($value === null) {
                // Use default value if no value is set
                $result[$definition->name] = $this->deserializeValue(
                    $definition->default_value,
                    $definition->data_type
                );
            } else {
                $result[$definition->name] = $this->deserializeValue(
                    $value->value,
                    $definition->data_type
                );
            }
        }

        return $result;
    }

    /**
     * Set a field value for a specific entity instance
     * 
     * @param int $entityType
     * @param int $entityId
     * @param string $fieldName
     * @param mixed $value
     * @return bool
     */
    public function setValue($entityType, $entityId, $fieldName, $value)
    {
        $definition = $this->getDefinitionByName($entityType, $fieldName);
        
        if (!$definition) {
            return false;
        }

        // Handle null values - delete record if value is null
        if ($value === null) {
            VirtualFieldValue::deleteAll([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'definition_id' => $definition->id,
            ]);
            return true;
        }

        // Cast value to appropriate type before serialization
        $value = $this->castValue($value, $definition->data_type);

        // Find or create value record
        $valueRecord = VirtualFieldValue::findOne([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'definition_id' => $definition->id,
        ]);

        if (!$valueRecord) {
            $valueRecord = new VirtualFieldValue([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'definition_id' => $definition->id,
            ]);
        }

        $serializedValue = $this->serializeValue($value, $definition->data_type);
        $valueRecord->value = $serializedValue;

        return $valueRecord->save();
    }

    /**
     * Set multiple field values at once
     * 
     * @param int $entityType
     * @param int $entityId
     * @param array $values Associative array of field name => value
     * @return bool
     */
    public function setValues($entityType, $entityId, $values)
    {
        $success = true;
        
        foreach ($values as $fieldName => $value) {
            if (!$this->setValue($entityType, $entityId, $fieldName, $value)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Delete all values for a specific entity instance
     * 
     * @param int $entityType
     * @param int $entityId
     * @return int Number of rows deleted
     */
    public function deleteValues($entityType, $entityId)
    {
        return VirtualFieldValue::deleteAll([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * Get a field definition by name
     * 
     * @param int $entityType
     * @param string $fieldName
     * @return VirtualFieldDefinition|null
     */
    public function getDefinitionByName($entityType, $fieldName)
    {
        $definitions = $this->getDefinitions($entityType);
        
        foreach ($definitions as $definition) {
            if ($definition->name === $fieldName) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * Serialize a value for storage
     * 
     * @param mixed $value
     * @param string $dataType
     * @return string
     */
    public function serializeValue($value, $dataType)
    {
        if (!isset($this->dataTypes[$dataType])) {
            return (string) $value;
        }

        return call_user_func($this->dataTypes[$dataType]['serialize'], $value);
    }

    /**
     * Deserialize a value from storage
     * 
     * @param string $value
     * @param string $dataType
     * @return mixed
     */
    public function deserializeValue($value, $dataType)
    {
        if ($value === null) {
            return null;
        }

        if (!isset($this->dataTypes[$dataType])) {
            return $value;
        }

        return call_user_func($this->dataTypes[$dataType]['deserialize'], $value);
    }

    /**
     * Cast a value to the appropriate PHP type
     * 
     * @param mixed $value
     * @param string $dataType
     * @return mixed
     */
    public function castValue($value, $dataType)
    {
        if ($value === null) {
            return null;
        }

        if (!isset($this->dataTypes[$dataType])) {
            return $value;
        }

        return call_user_func($this->dataTypes[$dataType]['cast'], $value);
    }

    /**
     * Clear cache for entity type definitions
     * 
     * @param int|null $entityType If null, clears all virtual field caches
     */
    public function clearCache($entityType = null)
    {
        $cache = $this->getCache();
        if (!$cache) {
            return;
        }

        if ($entityType === null) {
            // Clear all virtual field caches - implementation depends on cache backend
            // For simplicity, we'll just clear by pattern if supported
            if (method_exists($cache, 'flush')) {
                $cache->flush();
            }
        } else {
            $cache->delete("virtualfields:definitions:{$entityType}:active");
            $cache->delete("virtualfields:definitions:{$entityType}:all");
        }
    }

    /**
     * Get the cache component
     * 
     * @return CacheInterface|null
     */
    protected function getCache()
    {
        return Yii::$app->has('cache') ? Yii::$app->get('cache') : null;
    }

    /**
     * Register a custom data type
     * 
     * @param string $name
     * @param callable $cast
     * @param callable $serialize
     * @param callable $deserialize
     */
    public function registerDataType($name, $cast, $serialize, $deserialize)
    {
        $this->dataTypes[$name] = [
            'cast' => $cast,
            'serialize' => $serialize,
            'deserialize' => $deserialize,
        ];
    }

    /**
     * Get all registered data types
     * 
     * @return array
     */
    public function getDataTypes()
    {
        return array_keys($this->dataTypes);
    }
}
