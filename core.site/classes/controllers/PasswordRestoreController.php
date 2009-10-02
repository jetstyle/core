<?php
Finder::useClass("controllers/Controller");
class PasswordRestoreController extends Controller {
    protected $params_map = array(
        array('thank', array('thank'=>'thank')),
        array('change_thank',
            array(
                'change'=>'change',
                'thank'=>'thank',
            )
        ),
        array('change',
            array(
                'change' => 'change',
                'key' => '[a-fA-F0-9]{32}',
            ),
        ),
        array('default', array(NULL)),
    );

    protected function handle_default() {
        Finder::useClass("forms/EasyForm");
        $config = array();
        $config['success_url'] = RequestInfo::$baseUrl.$this->path.'/thank';
        $config['on_after_event'] = array(array(&$this, 'restoreAfterEvent'));
        $form =  new EasyForm('password_restore', $config);
        Locator::get('tpl')->set('Form', $form->handle());

        $this->siteMap = 'password_restore';
    }

    protected function handle_thank($config) {
        $tpl = &Locator::get('tpl');
        $tpl->set('thank', true);
        $this->siteMap = "password_restore";
    }

    public function handle_change($config) {
        $db = &Locator::get('db');
        $error = false;

        $user = clone Locator::get('principal')->getStorageModel();
        $user->loadOne('{key} = '.$db->quote($config['key']));
        if ($user->getId()) {
            Finder::useClass("forms/EasyForm");
            $user->removeField('key');
            $config = array(
                'id' => $user->getId(),
                'success_url' => RequestInfo::$baseUrl.$this->path.'/change/thank',
                'db_model' => $user,
                'on_after_event' => array(array(&$this, 'changeAfterEvent')),
                'fields' => array(
                    'key' => array(
                        'extends_from' => 'system',
                        'model_default' => $this->generateKey(),
                    )
                )
            );
            $form = new EasyForm('password_change', $config);
            Locator::get('tpl')->set('Form', $form->handle());
        }
        else $error = true;

        Locator::get('tpl')->set('error', $error);

        $this->siteMap = 'password_restore/change';
    }

    public function handle_change_thank($config) {
        Locator::get('tpl')->set('thank', true);
        $this->siteMap = 'password_restore/change';
    }

    public function restoreAfterEvent($event, $form) {
        $login = $form->getFieldByName('login')->model->model_data;

        $model = clone Locator::get('principal')->getStorageModel();
        $model->loadByEmail($login);
        if (!$model->getId())
        {
            $model->loadByLogin($login);
        }

        $this->sendPasswordMail($model);
    }

    public function changeAfterEvent($event, $form) {
        $userModel =  $form->config['db_model'];
        Locator::get('principal')->login(
            $userModel['login'],
            $form->getFieldByName('password')->model->model_data
        );
    }

    private function sendPasswordMail($user) {
        $tpl = &Locator::get('tpl');

        $tpl->set('site_name', Config::get('project_title'));
        $changeLink = $this->url_to('change', array('change' => 'change', 'key' => $user['key']));
        $tpl->set('change_link', RequestInfo::$baseUrl.$changeLink);
        $tpl->set('restore_user', $user);
        $emailText = $tpl->parse('users/email_password_restore.html');

        Finder::useClass('SimpleEmailer');
        $emailer = new SimpleEmailer();
        $emailer->sendEmail(
            $user['email'],
            Config::get('project_title').' <'.Config::get('admin_email').'>',
            '—сылка на изменение парол€',
            $emailText
        );
    }

    private function generateKey() {
        return md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].time().rand());
    }
}
?>