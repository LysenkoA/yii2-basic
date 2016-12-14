<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */

$this->title = 'My Yii Application';
?>
<div class="site-index">

    <div class="body-content">
        
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <h1>News</h1>
                </div>
            </div>
        </div>

        <div class="row">
                
            <?php foreach($posts as $post): ?>
            
            <div class="col-md-4">
                <h2><?= $post->title ?></h2>

                <p><?= $post->preview ?></p>

                <p>
                    <?= Html::a('Read more...', ['site/post', 'id' => $post->id]) ?>
                </p>
            </div>
            
            <?php endforeach; ?>
            
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <?= LinkPager::widget([
                    'pagination' => $pages,
                ]); ?>
            </div>
        </div>

    </div>
</div>
