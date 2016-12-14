<?php

namespace app\components;

class CheckIfLoggedIn extends \yii\base\Behavior
{
	
	public function events () {
		return [
			\yii\web\Application::EVENT_BEFORE_REQUEST => 'checkIfLoggedIn'
		];
	}

	public function checkIfLoggedIn () {
	    if (!\Yii::$app->user->isGuest) {
	        if (\Yii::$app->user->identity->notif_mess == 1) {
	            if (\Yii::$app->getRequest()->getCookies()->has('last_visit')) {
        			\Yii::$app->response->cookies->add(new \yii\web\Cookie([
        			    'name' => 'last_visit',
        			    'value' => date('Y-m-d H:i:s'),
        			]));	                
	            }
                
                $last_posts = \app\models\Post::find()
                    ->select(['id', 'title'])
                    ->where(['>', 'date_update', \Yii::$app->getRequest()->getCookies()->getValue('last_visit')])
                    ->all();
                    
    			\Yii::$app->response->cookies->add(new \yii\web\Cookie([
    			    'name' => 'last_visit',
    			    'value' => date('Y-m-d H:i:s'),
    			]));
    			
    			foreach ($last_posts as $post) {
    			    \Yii::$app->getSession()->setFlash('info', 'New Post: '. \yii\helpers\Html::a($post->title, ['/site/post', 'id' => $post->id]));
    			}
	        }
	        
	    }
        
	}
}

?>