<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'auth_key')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'password_reset_token')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'password')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'status')->dropDownList(
        User::getStatusesArray(),
        ['prompt'=> '-- Select status --']
    ) ?>
    
    <?= $form->field($model, 'role')->dropDownList(
        User::getRolesArray(),
        ['prompt'=> '-- Select role --']
    ) ?>
    
    <p>Notification</p>
    <?= $form->field($model, 'notif_mail')->checkbox() ?>
    <?= $form->field($model, 'notif_mess')->checkbox() ?>    
    

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
