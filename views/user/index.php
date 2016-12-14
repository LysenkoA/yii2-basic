<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

use app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create User', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    
    
    
<?php Pjax::begin(); ?>    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'username',
            //'auth_key',
            //'password_hash',
            //'password_reset_token',
            // 'email_confirm_token:email',
            'email:email',
			[
				'attribute' => 'status',
				'value' => function ($model) {
					return User::getStatusesArray()[$model->status];
				},
				'filter' => User::getStatusesArray()
			],
			[
				'attribute' => 'role',
				'value' => function ($model) {
					return User::getRolesArray()[$model->role];
				},
				'filter' => User::getRolesArray()
			],
			[
				'label' => 'Reset Password',
				'format' => 'html',
				'value' => function ($model) {
					return Html::a('reset', ['user/reset-password', 'email' => $model->email]);
				},
			],
            // 'created_at',
            // 'updated_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
<?php Pjax::end(); ?></div>
