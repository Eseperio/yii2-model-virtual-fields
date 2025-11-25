<?php

namespace tests\_app\models;

use yii\db\ActiveRecord;
use eseperio\virtualfields\behaviors\VirtualFieldsBehavior;

/**
 * TestModel
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $created_at
 * @property int $updated_at
 */
class TestModel extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%test_model}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
            ],
            'virtualFields' => [
                'class' => VirtualFieldsBehavior::class,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'email'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get the entity type for virtual fields
     * 
     * @return int
     */
    public function getObjectType()
    {
        return 1;
    }
}
