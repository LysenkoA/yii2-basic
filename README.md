<p>Для работы с почтой используется компонент <code>swiftmailer</code> и почтовый host <code>gmail</code>.</p>

<p>В приложении реализован следующий функционал:</p>

<h2>Регистрация пользователей c подтверждением почтового ящика</h2>
<p>Форма  <code>views/site/signup.php</code></p>

<p>Модель <code>models/SignupForm.php</code></p>
<pre>
	<?php

	namespace app\models;

	use Yii;
	use yii\base\Model;

	/**
	 * LoginForm is the model behind the login form.
	 *
	 * @property User|null $user This property is read-only.
	 *
	 */
	class LoginForm extends Model
	{
	    public $username;
	    public $password;
	    public $rememberMe = true;

	    private $_user = false;


	    /**
	     * @return array the validation rules.
	     */
	    public function rules()
	    {
	        return [
	            // username and password are both required
	            [['username', 'password'], 'required'],
	            // rememberMe must be a boolean value
	            ['rememberMe', 'boolean'],
	            // password is validated by validatePassword()
	            ['password', 'validatePassword'],
	        ];
	    }

	    /**
	     * Validates the password.
	     * This method serves as the inline validation for password.
	     *
	     * @param string $attribute the attribute currently being validated
	     * @param array $params the additional name-value pairs given in the rule
	     */
	    public function validatePassword($attribute, $params)
	    {
	        if (!$this->hasErrors()) {
	            $user = $this->getUser();

	            if (!$user || !$user->validatePassword($this->password)) {
	                $this->addError($attribute, 'Incorrect username or password.');
	            } elseif ($user && $user->status == User::STATUS_BLOCKED) {
	                $this->addError('username', 'Your account is blocked.');
	            } elseif ($user && $user->status == User::STATUS_WAIT) {
	                $this->addError('username', 'Your account has not been confirmed.');
	            }
	        }
	    }

	    /**
	     * Logs in a user using the provided username and password.
	     * @return bool whether the user is logged in successfully
	     */
	    public function login()
	    {
	        if ($this->validate()) {
	            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
	        }
	        return false;
	    }

	    /**
	     * Finds user by [[username]]
	     *
	     * @return User|null
	     */
	    public function getUser()
	    {
	        if ($this->_user === false) {
	            $this->_user = User::findByUsername($this->username);
	        }

	        return $this->_user;
	    }
	}
	
</pre>
<p>Контроллер <code>controllers/SiteController.php</code></p>
<pre>
	...
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
    ...
</pre>
<p>После регистрации пользователь получает на почту уведомление о регистрации с необходимостью подтвердить email</p>
<code>SiteController.php</code>
<pre>
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
</pre>
<p><code>models/EmailConfirmForm.php</code></p>
<pre>
<?php

namespace app\models;
 
use yii\base\InvalidParamException;
use yii\base\Model;
use Yii;
 
class EmailConfirmForm extends Model
{
    /**
     * @var User
     */
    private $_user;
 
    /**
     * Creates a form model given a token.
     *
     * @param  string $token
     * @param  array $config
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($token, $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidParamException('No confirmation code.');
        }
        $this->_user = User::findByEmailConfirmToken($token);
        if (!$this->_user) {
            throw new InvalidParamException('Invalid token.');
        }
        parent::__construct($config);
    }
 
    /**
     * Confirm email.
     *
     * @return boolean if email was confirmed.
     */
    public function confirmEmail()
    {
        $user = $this->_user;
        $user->status = User::STATUS_ACTIVE;
        $user->removeEmailConfirmToken();
 
        return $user->save();
    }
	
	public function autoLogin () {
		return Yii::$app->user->login($this->_user, 3600*24*30);
	}
}
	
</pre>

<h2>CRUD управление новостями и пользователями с разграничением прав.</h2>
<h3>Новости</h3>
<p>Разграничение прав происходит в контроллере <code>controllers/PostController.php</code> (используется поведение)</p>
<pre>
	...


	class PostController extends Controller
	{
	    /**
	     * @inheritdoc
	     */
	    public function behaviors()
	    {
	        return [
	            'access' => [
	                'class' => AccessControl::className(),
	                'rules' => [
	                    [
	                        'actions' => ['index', 'login', 'error'],
	                        'allow' => true,
	                    ],                    
	                    [
	                        'actions' => ['view'],
	                        'allow' => true,
	                        'roles' => ['@'],
	                    ],
	                    [
	                        'actions' => ['create', 'update', 'delete'],
	                        'allow' => true,
	                        'roles' => ['@'],
	                        'matchCallback' => function ($rule, $action) {
	                            if (
	                                User::isUserModerator(Yii::$app->user->identity->username) || 
	                                User::isUserAdmin(Yii::$app->user->identity->username)
	                            ) {
	                                return true;
	                            } else {
	                                false;
	                            }
	                        }
	                    ],
	                ],
	            ],
	            'verbs' => [
	                'class' => VerbFilter::className(),
	                'actions' => [
	                    'delete' => ['POST'],
	                ],
	            ],
	        ];
	    }


	...
</pre>
<p>Анонимный пользователь  может просматривать превю (список) новостей </p>
<pre>
...
    [
        'actions' => ['index', 'login', 'error'],
        'allow' => true,
    ],
... 	
</pre>
<p>Пользователь может просматривать полные новости</p>
<pre>
...

    [
        'actions' => ['view'],
        'allow' => true,
        'roles' => ['@'],
    ],

...
</pre>
<p>Модератор и администратор может редактировать и добавлять новости</p>
<pre>
...

[
    'actions' => ['create', 'update', 'delete'],
    'allow' => true,
    'roles' => ['@'],
    'matchCallback' => function ($rule, $action) {
        if (
            User::isUserModerator(Yii::$app->user->identity->username) || 
            User::isUserAdmin(Yii::$app->user->identity->username)
        ) {
            return true;
        } else {
            false;
        }
    }
],

...	
</pre>
<h3>Пользователи</h3>
<p>Только администратор может управлять пользователями сайта. Зарегистрированный пользователь имеет только доступ к своему профилю (личный кабинет)</p>
<p><code>controllers/UserController.php</code></p>
<pre>

...

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['profile'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return (Yii::$app->request->get('id') == Yii::$app->user->identity->id) ? true : false;
                        }
                    ],
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'reset-password'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                           return User::isUserAdmin(Yii::$app->user->identity->username);
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

...
	
</pre>
<p>Роли и статусы пользователь объявлены в моделе <code>models/User.php</code></p>

<pre>
	
...

const STATUS_BLOCKED = 0;
const STATUS_ACTIVE = 1;
const STATUS_WAIT = 2;

const ROLE_USER = 1;
const ROLE_MODERATOR = 2;
const ROLE_ADMIN = 3;

...

    public static function getStatusesArray()
    {
        return [
            self::STATUS_BLOCKED => 'Blocked User',
            self::STATUS_ACTIVE => 'Active User',
            self::STATUS_WAIT => 'Wait User',
        ];
    }
    
    public static function getRolesArray()
    {
        return [
            self::ROLE_USER => 'User',
            self::ROLE_MODERATOR => 'Moderator',
            self::ROLE_ADMIN => 'Admin',
        ];
    }	

...

</pre>

<p>Для того, чтобы проверить роль пользователя есть следующие методы данного класса</p>
<pre>
...
    public static function isUserAdmin($username)
    {
        if (static::findOne(['username' => $username, 'role' => self::ROLE_ADMIN])) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function isUserModerator($username)
    {
        if (static::findOne(['username' => $username, 'role' => self::ROLE_MODERATOR])) {
            return true;
        } else {
            return false;
        }
    }  	
...
</pre>

<h2>Пользователь может получать уведомления о новых новостях только на e-mail, в браузер или и то и другое</h2>
<p>Данные настройки можно задать в личном кабинете пользователя. Часть личного кабинета можно увидеть в представлении <code>views/user/profile.php</code></p>
<h2>При добавлении новости на сайт, оповещать зарегистрированных пользователей по e-mail и всплывающим окном в браузере.</h2>
<p>Данная возможность реализуется следующим образом.</p>
<p>В моделе <code>models/Post.php</code> объявляется событие <code>EVENT_NEW_POST</code>, которое отвечает за отправку email тем, пользователям, которые в настройках профиля активировали опцию получения уведомлений на почту.</p>

<pre>
	
...


    const EVENT_NEW_POST = 'new-post';
    
    public function sendMail($event){
        
        $users = User::find()
            ->select(['username', 'email'])
            ->where(['notif_mail' => 1])
            ->all();
            
        $send_to = [];
        foreach ($users as $user) {
            $send_to[$user->email] = $user->username;
        }
        
        return Yii::$app->mailer->compose(
                ['html' => 'new_post_mail_notification'],
                ['post' => $this]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($send_to)
            ->setSubject('New post')
            ->send();
    }
    
    public function init(){
        $this->on(self::EVENT_NEW_POST, [$this, 'sendMail']);
    } 

...

</pre>
<p>
	А в контроллере <code>controllers/PostController.php</code> событие вызывается:
</p>

<pre>
	
...

	$model->trigger(\app\models\Post::EVENT_NEW_POST);

...

</pre>

<p>Что качается уведомлении на сайте, то они реализованы через событие <code>EVENT_BEFORE_REQUEST</code> следующим образом: </p>
<code>components/CheckIfLoggedIn.php</code>
<pre>

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
	
</pre>

<h3>Постраничный вывод превью новостей на главной странице с дальнейшим полным просмотром</h3>
<pre>
	
    public function actionIndex()
    {
        $query = \app\models\Post::find();
        
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

</pre>

<h3>Автоматическая авторизация на сайте при активации профиля</h3>
<p>Она происходит с помощью вызова следующего метода в <code>models/EmailConfirmForm.php</code> после активации профиля:</p>
<pre>
	...

	public function autoLogin () {
		return Yii::$app->user->login($this->_user, 3600*24*30);
	}

	....
</pre>

<h3>Оповещение администратора о регистрации нового пользователя</h3>
<p>Эта функция также реализуется через события</p>
<p><code>models/User.php</code></p>
<pre>
	
...
    const EVENT_NEW_USER = 'new-user';
    
    public function sendMail($event){
        Yii::$app->mailer->compose('newUser', ['user' => $this])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('New user')
            ->send();
    }
	
    public function init(){
    
      $this->on(self::EVENT_NEW_USER, [$this, 'sendMail']);

    } 
...

</pre>

<h3>Оповещение пользователя при создании нового пользователя или редактирования пользователя администратором</h3>
<p>Реализуется с помощью вызова метода <code>emailConfirmation</code> после создания или редактирования пользователя. Пользователь получает письмо с ссылкой для активации профиля:</p>
<pre>

...
	
	function emailConfirmation($model) {
		Yii::$app->mailer->compose('emailConfirm', ['user' => $model])
			->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
			->setTo($model->email)
			->setSubject('Email confirmation for ' . Yii::$app->name)
			->send();			
	}

...

</pre>

<h3>Сброс пароля администратором</h3>
<p> Администратор может сбросить пароль пользователя, после чего пользователь получает письмо с ссылкой для ввода нового пароля. Для этого вызывается метод <code>sendEmail()</code> модели <code>models/PasswordResetRequestForm.php</code> в экшене <code>actionResetPassword</code>:</p>
<pre>
	...

	public function actionResetPassword($email) {
		$model = new \app\models\PasswordResetRequestForm();
		$model->email = $email;
		$model->sendEmail();
		Yii::$app->getSession()->setFlash('success', 'The user is sent an email with a link to the password recovery.');
		return $this->redirect(['index']);
	}

	...
</pre>
