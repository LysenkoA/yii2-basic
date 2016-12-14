<?php
use yii\helpers\Html;
 
/* @var $this yii\web\View */
/* @var $user app\modules\user\models\User */
 
$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/password-reset', 'token' => $user->password_reset_token]);
?>
 
Hello, <?= Html::encode($user->username) ?>!
 
Click the link to change your password:
 
<?= Html::a(Html::encode($resetLink), $resetLink) ?>