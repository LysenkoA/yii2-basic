<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\User */

$this->title = 'User Profile';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">

    <h1><?= Html::encode($this->title) ?></h1>

        <div class="user-form">
        
            <?php $form = ActiveForm::begin(); ?>
            
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
                    
                        <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
                        
                        <?= $form->field($model, 'password')->textInput(['maxlength' => true])->label('New Password') ?>
                    </div>
                    <div class="col-md-6">
                        <h2>Notification</h2>
                        <?= $form->field($model, 'notif_mail')->checkbox() ?>
                        <?= $form->field($model, 'notif_mess')->checkbox() ?>                            
                    </div>
                </div>            
        
            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
        
            <?php ActiveForm::end(); ?>
        
        </div>


</div>
