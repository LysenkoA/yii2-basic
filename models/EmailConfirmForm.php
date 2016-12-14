<?php

namespace app\models;
 
use yii\base\InvalidParamException;
use yii\base\Model;
use Yii;
 
class EmailConfirmForm extends Model
{
    /**
     * @var User
     */
    private $_user;
 
    /**
     * Creates a form model given a token.
     *
     * @param  string $token
     * @param  array $config
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($token, $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidParamException('No confirmation code.');
        }
        $this->_user = User::findByEmailConfirmToken($token);
        if (!$this->_user) {
            throw new InvalidParamException('Invalid token.');
        }
        parent::__construct($config);
    }
 
    /**
     * Confirm email.
     *
     * @return boolean if email was confirmed.
     */
    public function confirmEmail()
    {
        $user = $this->_user;
        $user->status = User::STATUS_ACTIVE;
        $user->removeEmailConfirmToken();
 
        return $user->save();
    }
	
	public function autoLogin () {
		return Yii::$app->user->login($this->_user, 3600*24*30);
	}
}
