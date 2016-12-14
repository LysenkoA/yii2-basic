<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = $post->title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-post">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        Date: <?= $post->date_create ?>
    </p>
    
    <div>
        <?= $post->body ?>
    </div>

</div>
