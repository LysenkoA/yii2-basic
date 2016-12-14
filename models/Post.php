<?php

namespace app\models;

use Yii;
use \yii\behaviors\TimestampBehavior;
use \yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%post}}".
 *
 * @property integer $id
 * @property integer $author_id
 * @property string $title
 * @property string $preview
 * @property string $body
 * @property string $date_create
 * @property string $date_update
 *
 * @property User $author
 */
class Post extends ActiveRecord
{
    
    const EVENT_NEW_POST = 'new-post';
    
    public function sendMail($event){
        
        $users = User::find()
            ->select(['username', 'email'])
            ->where(['notif_mail' => 1])
            ->all();
            
        $send_to = [];
        foreach ($users as $user) {
            $send_to[$user->email] = $user->username;
        }
        
        return Yii::$app->mailer->compose(
                ['html' => 'new_post_mail_notification'],
                ['post' => $this]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($send_to)
            ->setSubject('New post')
            ->send();
    }
    
    public function init(){
        $this->on(self::EVENT_NEW_POST, [$this, 'sendMail']);
    }    
    
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'date_create',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'date_update',
                ],
                'value' => function() { 
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }    
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%post}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['author_id'], 'required'],
            [['author_id'], 'integer'],
            [['preview', 'body'], 'string'],
            [['date_create', 'date_update'], 'safe'],
            [['title'], 'string', 'max' => 100],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['author_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'author_id' => 'Author',
            'title' => 'Title',
            'preview' => 'Preview',
            'body' => 'Body',
            'date_create' => 'Date Create',
            'date_update' => 'Date Update',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'author_id']);
    }
}
