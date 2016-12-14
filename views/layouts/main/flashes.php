<?php foreach(Yii::$app->session->getAllFlashes() as $type => $message): ?>
        <div class="alert alert-<?= $type ?>" role="alert"><?= $message ?></div>
<?php endforeach ?>