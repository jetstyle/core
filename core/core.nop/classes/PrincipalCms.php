<?php
//системные роли
define("ROLE_GUEST",0);
define("ROLE_GOD",1);
define("ROLE_ADMIN",2);
define("ROLE_USER",3);

// states
define ("PRINCIPAL_UNKNOWN", 0);
define ("PRINCIPAL_WRONG_LOGIN", 1);
define ("PRINCIPAL_WRONG_PWD", 2);
define ("PRINCIPAL_NOT_ALIVE", 3);
define ("PRINCIPAL_AUTH", 4);
define ("PRINCIPAL_RESTORED", 5);
define ("PRINCIPAL_ACL_NEGATIVE", 6);
define ("PRINCIPAL_ACL_GRANTED", 7);
define ("PRINCIPAL_ACL_NOT_FOUND", 8);


class PrincipalCms
{
	public $acl_default = true; //флаг инициализации ACL
	public $user = array(); //хэш текущего пользователя
	public $is_granted_default = false; //есть ли доступ по умолчанию?

	//массив записей прав доступа [локация]=>[массив ролей]
	public $ACL = array(
      '*' => array( ROLE_GUEST ),
	);

	public $ROLES = array(
	ROLE_GUEST => 'гость',
	ROLE_GOD => 'бог',
	ROLE_ADMIN => 'администратор',
	ROLE_USER => 'пользователь',
	);


	protected $rh; //ссылка на $rh

	protected $state; //константа состояния авторизовации
	protected $granted_state; //константа состояния проверки доступа


	protected $input_name_login = 'login';
	protected $input_name_password = 'password';

	protected $id_field="user_id";
	protected $SELECT_FIELDS = array("user_id","roles","login","password");


	protected $users_table = '??users';
	protected $sessions_table = '??users_sessions';
	protected $users_where = '_state=0';

	protected $cookie_prefix = '';
	protected $session = 0; //хэщ сессии

	protected $USERS = array(); //массив юзерей изначально пуст

	//человеческие названия ролей


	protected $ROLES_REVERT = array(
        '' => ROLE_GUEST,
        'user' => ROLE_USER,
        'admin'=>ROLE_ADMIN,
        'admin'=>ROLE_GOD,
	);
	protected $ADMIN_ROLES = array( ROLE_GOD, ROLE_ADMIN, ROLE_USER);

	public function __construct(&$rh)
	{
		$this->rh =& $rh;
		$this->state = PRINCIPAL_UNKNOWN;

		$this->cookie_prefix = ($rh->cookie_prefix ? $rh->cookie_prefix : $rh->db_prefix).'_';
		$this->users_table = $rh->db_prefix.$this->users_table;
		$this->sessions_table = $rh->db_prefix.$this->sessions_table;
	}

	//сохраняем в сессию
	protected function sessionStore()
	{
		Debug::trace("PrincipalCms::sessionStore()");
		$this->_session();
		$this->rh->db->execute('UPDATE '.$this->sessions_table." SET user_id='".$this->user[$this->id_field]."' WHERE id=".$this->rh->db->quote($this->session['id'])."");
		$this->session['user_id'] = $this->user['id'];
	}

	//восстанавливаем из сессии
	protected function sessionRestore()
	{
		Debug::trace("PrincipalCms::sessionRestore()", 'prp');
		$this->_session();
		if($this->session['user_id'] > 0)
		{
			$this->user = $this->getById($this->session['user_id']);
		}

		return $this->user;
	}

	//убиваем сессию
	protected function sessionDestroy()
	{
		Debug::trace("PrincipalCms::SessionDestroy()", 'prp');
		$this->rh->db->execute('DELETE FROM '.$this->sessions_table." WHERE id=".$this->rh->db->quote($this->session['id'])."");
		$this->session = array();
		setcookie( $this->cookie_prefix.'_sessid', "", 0, $this->rh->front_end->path_rel ? $this->rh->front_end->path_rel : $this->rh->base_url );
	}

	/*** работа с сессиями ***/

	protected function _session()
	{
		//    Debug::trace("PrincipalCms::_Session() - ...", 'prp');
		$db =& $this->rh->db;
		if( !$this->session['id'] )
		{
			Debug::trace("PrincipalCms::_Session() - сессии пока нет", 'prp');
			//прибиваем старые сессии - брошенные на час и больше
			$db->execute('DELETE FROM '.$this->sessions_table.' WHERE time<'.(time()-3600));
			//пытаемся загрузить сессию
			$session = $db->queryOne('SELECT * FROM '.$this->sessions_table.' WHERE id='.((integer)$_COOKIE[$this->cookie_prefix.'_sessid']));

			if( !empty($session) )
			{
				//помечаем текущую сессию как используемую
				$db->execute('UPDATE '.$this->sessions_table.' SET time='.time()." WHERE id=".$this->rh->db->quote($session['id'])."");
				Debug::trace("PrincipalDB::_Session() - восстановлена через куки [".$session['id']."]", 'prp');
			}
			else
			{
				//нужна новая сессия
				//генерим sessid
				do
				{
					$sessid = rand(1,1000000);
					$rs = $db->queryOne('SELECT id FROM '.$this->sessions_table.' WHERE id='.$sessid);
				}while($rs['id']);
				//вставляем запись
				$ip = ($_SERVER["HTTP_X_FORWARDED_FOR"]!="") ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
				$db->execute('INSERT DELAYED INTO '.$this->sessions_table.'(id,ip,time) VALUES('.$sessid.',\''.$ip.'\','.time().')');
				//грузим новую запись
				$session = $db->queryOne('SELECT * FROM '.$this->sessions_table.' WHERE id='.$sessid);
				Debug::trace("PrincipalDB::_Session() - вставили новую [".$sessid."]", 'prp');
			}
			//сохраняем sessid
			$this->session = $session;
			setcookie($this->cookie_prefix.'_sessid',$session['id'], 0, $this->rh->front_end->path_rel ? $this->rh->front_end->path_rel : $this->rh->base_url);
		}
	}

	//восстанавливаемся из сессии
	//или пытаемся залогиниться
	public public function authorise()
	{
		Debug::trace("Principal::Authorise() - ...", 'prp');

		//пытаемемся восстановить из сессии
		if( $this->sessionRestore() )
		{
			$this->state = PRINCIPAL_RESTORED;
			return true;
		}
		//пытаемся авторизоваться как в первый раз
		return $this->login( $_POST[$this->input_name_login], $_POST[$this->input_name_password] );
	}

	/**
	 * принципал билдера не хотел логинить с md5
	 */
	public function login( $login, $password )
	{
		if(!$login || !$password)
		{
			$this->state = PRINCIPAL_WRONG_LOGIN;
			return false;
		}
		//пытаемся загрузить пользователя по логину
		if( !($this->user = $this->getByLogin($login)) ){
			//          $this->user = $this->getByID(0);
			$this->state = PRINCIPAL_WRONG_LOGIN;
			Debug::trace("<font color='red'>Principal::Login('$login','$password') - неверный логин</font> ", 'prp');
			return false;
		}

		//проверяем пароль
		if( $this->user['password']!=md5($password) )
		{
			//          $this->user = $this->getByID(0);
			$this->state = PRINCIPAL_WRONG_PWD;
			Debug::trace("<font color='red'>Principal::Login('$login','$password') - неверный пароль</font>", 'prp');
			return false;
		}


		//сохраняем пользователя в сессии
		Debug::trace("<font color='green'>Principal::Login('$login','$password') - OK</font>", 'prp');
		$this->sessionStore();
		$this->state = PRINCIPAL_AUTH;

		return true;
	}

	public function logout( $redirect='' ){
		$this->sessionDestroy();
		if( $redirect )
		$this->rh->redirect($redirect);
	}

	/*
	 * берем id роли юзера из текстового поля в БД
	 */
	public function getUserRole()
	{
		return ($this->ROLES_REVERT[$this->user['roles']]);
	}

	public function getUserData()
	{
		return $this->user;
	}

	/*** изымание юзера из БД ***/

	protected function _getBy($where)
	{
		Debug::trace("PrincipalDB::_GetBy() - [$where] ...", 'prp');
		$db =& $this->rh->db;
		$user = $db->queryOne('SELECT '.implode(",",$this->SELECT_FIELDS).' FROM '.$this->users_table.' WHERE '.$this->users_where.' AND '.$where);

		if( $user[$this->id_field] )
		{
			Debug::trace("PrincipalDB::_GetBy() - OK", 'prp');
			$this->USERS[ $user[$this->id_field] ] = $user;
			return $user;
		}
		else
		{
			Debug::trace("PrincipalDB::_GetBy() - не найден", 'prp');
			return false;
		}
	}

	protected function getById($id)
	{
		return $this->_getBy($this->id_field.'='.((integer)$id));
	}

	protected function getByLogin($login){
		return $this->_getBy("login=".$this->rh->db->quote($login)."");
	}

	public function isGrantedTo( $location )
	{
		$ACL =& $this->ACL;
		$N = count($A);

		Debug::trace("Principal::IsGrantedTo() - location = [$location] ...", 'prp');

		//бежим по всему ACL
		$granted = false;

		/* способ каким берется роль пользователя теперь можно перегружать */
		$str = $this->getUserRole();
		$str1 = '!'.$this->getUserRole();

		Debug::trace("Principal::IsGrantedTo() - ищем [$str] [$str1]", 'prp');


		foreach($ACL as $loc=>$roles)
		{
			//наша строка?

			$OK = ($loc=='*' || $loc==$location);
			if( !$OK ){
				$n = strlen($loc)-1;
				$OK = ($loc[$n]=='*' && substr($location,0,$n)==substr($loc,0,$n));
			}
			//проверяем права
			if( $OK )
			{
				//        $this->rh->debug->Trace("Principal::IsGrantedTo() - строка $loc => [".implode(',',$roles)."]");
				//нашли строку с запретом?
				if( in_array($str1,$roles,true) )
				{

					$this->granted_state = PRINCIPAL_ACL_NEGATIVE;
					Debug::trace("Principal::IsGrantedTo() - <font color='red'>denied</font>", 'prp');
					return false;
				}

				//нашли строку, которая позволяет?
				if( in_array($str,$roles,true) )
				{

					$granted = true;
					Debug::trace("Principal::IsGrantedTo() - <font color='green'>granted</font>", 'prp');
				}
			}else
			Debug::trace("<font color='grey'>Principal::IsGrantedTo() - строка loc = $loc</font>", 'prp');
			//      $this->rh->debug->Trace("Principal::IsGrantedTo() - ***");
		}
		//в конце концов, нашли строку, которая позволяет?
		if( $granted ){
			$this->granted_state = PRINCIPAL_ACL_GRANTED;
			Debug::trace("Principal::IsGrantedTo() - <font color='green'>granted</font>", 'prp');
			return true;
		}

		//ничего не нашли
		$this->granted_state = PRINCIPAL_ACL_NOT_FOUND;
		Debug::trace("Principal::IsGrantedTo() - <font color='red'>not found</font>", 'prp');
		return $this->is_granted_default;
	}

	public function isAuth()
	{
		return $this->state==PRINCIPAL_AUTH || $this->state==PRINCIPAL_RESTORED;
	}

}
?>