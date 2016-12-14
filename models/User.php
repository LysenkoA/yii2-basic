<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property integer $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email_confirm_token
 * @property string $email
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property Post[] $posts
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface
{
    public $password;
    
	// дописать
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }	
	
    const STATUS_BLOCKED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_WAIT = 2;
    
    const ROLE_USER = 1;
    const ROLE_MODERATOR = 2;
    const ROLE_ADMIN = 3;
    
    const EVENT_NEW_USER = 'new-user';
    
    public function sendMail($event){
        Yii::$app->mailer->compose('newUser', ['user' => $this])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('New user')
            ->send();
    }
	
    public function init(){
    
      $this->on(self::EVENT_NEW_USER, [$this, 'sendMail']);

    }    
    	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
			['username', 'match', 'pattern' => '#^[\w_-]+$#i'],
			['username', 'unique', 'targetClass' => self::className(), 'message' => 'This username has already been taken.'],
			[['username', 'email'], 'string', 'min' => 2, 'max' => 255],
            ['email', 'email'],
            ['email', 'unique', 'targetClass' => self::className(), 'message' => 'This email address has already been taken.'],			
            [['status', 'role'], 'integer'],
			['status', 'default', 'value' => self::STATUS_ACTIVE],
			['role', 'default', 'value' => self::ROLE_USER],
			['password', 'string', 'min' => 8],
            [['notif_mail', 'notif_mess'], 'integer'],
            [['notif_mail', 'notif_mess'], 'default', 'value' => 0],
			
            //[['auth_key'], 'string', 'max' => 32],
            //[['password_reset_token'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'email_confirm_token' => 'Email Confirm Token',
            'email' => 'Email',
            'status' => 'Status',
            'role' => 'Role',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'notif_mail' => 'Email notification',
            'notif_mess' => 'Web notification',            
        ];
    }
	
    public function getStatusName()
    {
        return ArrayHelper::getValue(self::getStatusesArray(), $this->status);
    }
	
    public static function getStatusesArray()
    {
        return [
            self::STATUS_BLOCKED => 'Blocked User',
            self::STATUS_ACTIVE => 'Active User',
            self::STATUS_WAIT => 'Wait User',
        ];
    }
    
    public static function getRolesArray()
    {
        return [
            self::ROLE_USER => 'User',
            self::ROLE_MODERATOR => 'Moderator',
            self::ROLE_ADMIN => 'Admin',
        ];
    }	

    public static function isUserAdmin($username)
    {
        if (static::findOne(['username' => $username, 'role' => self::ROLE_ADMIN])) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function isUserModerator($username)
    {
        if (static::findOne(['username' => $username, 'role' => self::ROLE_MODERATOR])) {
            return true;
        } else {
            return false;
        }
    }    

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }
 
	function emailConfirmation($model) {
		Yii::$app->mailer->compose('emailConfirm', ['user' => $model])
			->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
			->setTo($model->email)
			->setSubject('Email confirmation for ' . Yii::$app->name)
			->send();			
	}
	
	
	
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
	
    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
 
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
 
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->generateAuthKey();
            }
            return true;
        }
        return false;
    }	
	
    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }
 
    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }
 
    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }
 
    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
	

    /**
     * @param string $email_confirm_token
     * @return static|null
     */
    public static function findByEmailConfirmToken($email_confirm_token)
    {
        return static::findOne(['email_confirm_token' => $email_confirm_token, 'status' => self::STATUS_WAIT]);
    }
 
    /**
     * Generates email confirmation token
     */
    public function generateEmailConfirmToken()
    {
        $this->email_confirm_token = Yii::$app->security->generateRandomString();
    }
 
    /**
     * Removes email confirmation token
     */
    public function removeEmailConfirmToken()
    {
        $this->email_confirm_token = null;
    }	
	
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosts()
    {
        return $this->hasMany(Post::className(), ['author_id' => 'id']);
    }
	
	/*
	 * IdentityInterface
	 */
	
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }
 
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('findIdentityByAccessToken is not implemented.');
    }
 
    public function getId()
    {
        return $this->getPrimaryKey();
    }
 
    public function getAuthKey()
    {
        return $this->auth_key;
    }
 
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
	
}
