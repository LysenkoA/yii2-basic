<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\data\Pagination;

use app\models\LoginForm;
use app\models\ContactForm;
use app\models\EmailConfirmForm;
use app\models\PasswordResetRequestForm;
use app\models\ResetPasswordForm;
use app\models\SignupForm;


class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout',  'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],			
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $query = \app\models\Post::find();
        
        // var_dump($query);
        // exit();
        
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count(), 'pageSize' => 5]);
        $posts = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();        
        
        return $this->render('index', [
            'posts' => $posts,
            'pages' => $pages,
        ]);
    }
    
    public function actionPost($id){
        $post = \app\models\Post::findOne($id);
        
        return $this->render('post', [
            'post' => $post,
        ]);
        
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

	
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                Yii::$app->getSession()->setFlash('success', 'Confirm your email address.');
                return $this->goHome();
            }
        }
 
        return $this->render('signup', [
            'model' => $model,
        ]);
    }	
	
    public function actionEmailConfirm($token)
    {
        try {
            $model = new EmailConfirmForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
 
        if ($model->confirmEmail()) {
            Yii::$app->getSession()->setFlash('success', 'Thank you! Your Email successfully confirmed.');
        } else {
            Yii::$app->getSession()->setFlash('error', 'Error Confirmation Email.');
        }
		
		$model->autoLogin();
		
        return $this->goHome();
    }	
	
    public function actionPasswordResetRequest()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->getSession()->setFlash('success', 'Thank you! In your Email has been sent an email with a link to the password recovery.');
 
                return $this->goHome();
            } else {
                Yii::$app->getSession()->setFlash('error', 'Sorry. We are having problems with sending.');
            }
        }
 
        return $this->render('passwordResetRequest', [
            'model' => $model,
        ]);
    }
	
    public function actionPasswordReset($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
 
        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->getSession()->setFlash('success', 'Thank you! Password successfully changed.');
 
            return $this->goHome();
        }
 
        return $this->render('passwordReset', [
            'model' => $model,
        ]);
    }	
	
    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
        $model = new ContactForm();

		if ($user = Yii::$app->user->identity) {
            /** @var \app\models\User $user */
            $model->name = $user->username;
            $model->email = $user->email;
        }
		
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
	
	public function actionEvent() {
	    $component = new \app\component\Common();
	    
	    $component->on(\app\component\Common::EVENT_NOTIFY, [$component, 'notifyAdmin']);
	    
	    $component->sendEmail();
	}
}
