<?php
use yii\helpers\Html;
 
/* @var $this yii\web\View */
/* @var $user app\modules\user\models\User */
 
$confirmLink = Yii::$app->urlManager->createAbsoluteUrl(['site/email-confirm', 'token' => $user->email_confirm_token]);
?>
 
Hello, <?= Html::encode($user->username) ?>!
 
To confirm the addresses, please click here:
 
<?= Html::a(Html::encode($confirmLink), $confirmLink) ?>
 
If you have not registered on our site, then delete this email.