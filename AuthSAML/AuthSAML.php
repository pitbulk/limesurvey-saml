<?php
/*
 * SAML Authentication plugin for LimeSurvey
 * Copyright (C) 2013 Sixto Pablo Martin Garcia <sixto.martin.garcia@gmail.com>
 * License: GNU/GPL License v2 http://www.gnu.org/licenses/gpl-2.0.html
 * URL: https://github.com/pitbulk/limesurvey-saml
 * A plugin of LimeSurvey, a free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

class AuthSAML extends AuthPluginBase
{
    protected $storage = 'DbStorage';

    protected $ssp = null;
    
    static protected $description = 'SAML authentication plugin';
    static protected $name = 'SAML';
    
    protected $settings = array(
        'simplesamlphp_path' => array(
            'type' => 'string',
            'label' => 'Path to the SimpleSAMLphp folder',
            'default' => '/var/www/simplesamlphp',
        ),
        'saml_authsource' => array(
            'type' => 'string',
            'label' => 'SAML authentication source',
            'default' => 'limesurvey',
        ),
        'saml_uid_mapping' => array(
            'type' => 'string',
            'label' => 'SAML attributed used as username',
            'default' => 'uid',
        ),
        'saml_mail_mapping' => array(
            'type' => 'string',
            'label' => 'SAML attributed used as email',
            'default' => 'mail',
        ),
        'saml_name_mapping' => array(
            'type' => 'string',
            'label' => 'SAML attributed used as name',
            'default' => 'cn',
        ),
        'authtype_base' => array(
            'type' => 'string',
            'label' => 'Authtype base',
            'default' => 'Authdb',
        ),
        'storage_base' => array(
            'type' => 'string',
            'label' => 'Storage base',
            'default' => 'DbStorage',
        ),
        'auto_create_users' => array(
            'type' => 'checkbox',
            'label' => 'Auto create users',
            'default' => true,
        ),
		'auto_create_labelsets' => array(
            'type' => 'checkbox',
            'label' => '- Permissions: Label Sets',
            'default' => false,
        ),
		'auto_create_participant_panel' => array(
            'type' => 'checkbox',
            'label' => '- Permissions: Participant panel',
            'default' => false,
        ),
		'auto_create_settings_plugins' => array(
            'type' => 'checkbox',
            'label' => '- Permissions: Settings & Plugins',
            'default' => false,
        ),
		'auto_create_surveys' => array(
            'type' => 'checkbox',
            'label' => '- Permissions: Surveys',
            'default' => true,
        ),
		'auto_create_templates' => array(
            'type' => 'checkbox',
            'label' => '- Permissions: Templates',
            'default' => false,
        ),
		'auto_create_user_groups' => array(
            'type' => 'checkbox',
            'label' => '- Permissions: User groups',
            'default' => false,
        ), 
        'auto_update_users' => array(
            'type' => 'checkbox',
            'label' => 'Auto update users',
            'default' => true,
        ),
        'force_saml_login' => array(
            'type' => 'checkbox',
            'label' => 'Force SAML login.', 
        ),
    );
    
    protected function get_saml_instance() {
        if ($this->ssp == null) {

            $simplesamlphp_path = $this->get('simplesamlphp_path', null, null, '/var/www/simplesamlphp');

            // To avoid __autoload conflicts, remove limesurvey autoloads temporarily 
            $autoload_functions = spl_autoload_functions();
            foreach($autoload_functions as $function) {
                spl_autoload_unregister($function);
            }

            require_once($simplesamlphp_path.'/lib/_autoload.php');

            $saml_authsource = $this->get('saml_authsource', null, null, 'limesurvey');
            $this->ssp = new SimpleSAML_Auth_Simple($saml_authsource);

            // To avoid __autoload conflicts, restote the limesurvey autoloads
            foreach($autoload_functions as $function) {
                spl_autoload_register($function);
            }
	    }
        return $this->ssp;
    }
 
    public function __construct(PluginManager $manager, $id) {
        parent::__construct($manager, $id);

        $this->storage = $this->get('storage_base', null, null, 'DbStorage');

        // Here you should handle subscribing to the events your plugin will handle
        $this->subscribe('beforeLogin');
        $this->subscribe('newUserSession');
        $this->subscribe('afterLogout');

        if (!$this->get('force_saml_login', null, null, false)) {
            $this->subscribe('newLoginForm');
        }
    }

    public function beforeLogin()
    {
        $ssp = $this->get_saml_instance();

        if ($this->get('force_saml_login', null, null, false)) {
            $ssp->requireAuth();
        }
        if ($ssp->isAuthenticated()) {
            $this->setAuthPlugin();
            $this->newUserSession();
        }
    }

    public function afterLogout()
    {
        $ssp = $this->get_saml_instance();
        $ssp->logout();
    }

    public function newLoginForm()
    {
        $authtype_base = $this->get('authtype_base', null, null, 'Authdb');

        $ssp = $this->get_saml_instance();
        $this->getEvent()->getContent($authtype_base)->addContent('<li><center>Click on that button to initiate SAML Login<br><a href="'.$ssp->getLoginURL().'" title="SAML Login"><img src="'.Yii::app()->getConfig('imageurl').'/saml_logo.gif"></a></center><br></li>', 'prepend');
    }

    public function getUserName()
    {
        if ($this->_username == null) {
            $ssp = $this->get_saml_instance();
            $attributes = $this->ssp->getAttributes();
            if (!empty($attributes)) {
                $saml_uid_mapping = $this->get('saml_uid_mapping', null, null, 'uid');
                if (array_key_exists($saml_uid_mapping , $attributes) && !empty($attributes[$saml_uid_mapping])) {
                    $username = $attributes[$saml_uid_mapping][0];
                    $this->setUsername($username);
                }
            }
        }
        return $this->_username;
    }

    public function getUserCommonName()
    {
        $name = '';

        $ssp = $this->get_saml_instance();
        $attributes = $this->ssp->getAttributes();

        if (!empty($attributes)) {
            $saml_name_mapping = $this->get('saml_name_mapping', null, null, 'cn');
            if (array_key_exists($saml_name_mapping , $attributes) && !empty($attributes[$saml_name_mapping])) {
                $name = $attributes[$saml_name_mapping][0];
            }
        }
        return $name;
    }


    public function getUserMail()
    {
        $mail = '';

        $ssp = $this->get_saml_instance();
        $attributes = $this->ssp->getAttributes();
        if (!empty($attributes)) {
            $saml_mail_mapping = $this->get('saml_mail_mapping', null, null, 'mail');
            if (array_key_exists($saml_mail_mapping , $attributes) && !empty($attributes[$saml_mail_mapping])) {
                $mail = $attributes[$saml_mail_mapping][0];
            }
        }
        return $mail;
    }

    public function newUserSession()
    {
        $ssp = $this->get_saml_instance();
        if ($ssp->isAuthenticated()) {

            $sUser = $this->getUserName();
            $_SERVER['REMOTE_USER'] = $sUser;

            $password = createPassword();
            $this->setPassword($password);

            $name = $this->getUserCommonName();
            $mail = $this->getUserMail();

            $oUser = $this->api->getUserByName($sUser);
            if (is_null($oUser))
            {
                // Create user
                $auto_create_users = $this->get('auto_create_users', null, null, true);
                if ($auto_create_users) {

                    $iNewUID = User::model()->insertUser($sUser, $password, $name, 1, $mail);

                    if ($iNewUID)
                    {
                        Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => Yii::app()->getConfig("defaulttemplate"),   'entity'=>'template', 'read_p' => 1));
                        
						// Set permissions: Label Sets 
                        $auto_create_labelsets = $this->get('auto_create_labelsets', null, null, true);
                        if ($auto_create_labelsets) {

                 		Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => 'labelsets',   'entity'=>'global', 'create_p' => 1, 'read_p' => 1, 'update_p' => 1, 'delete_p' => 1, 'import_p' => 1, 'export_p' => 1));
 						}
						
						// Set permissions: Particiapnt Panel 
                        $auto_create_participant_panel = $this->get('auto_create_participant_panel', null, null, true);
                        if ($auto_create_participant_panel) {

                 		Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => 'participantpanel',   'entity'=>'global', 'create_p' => 1, 'read_p' => 1, 'update_p' => 1, 'delete_p' => 1, 'export_p' => 1));
 						}
						
						// Set permissions: Settings & Plugins 
                        $auto_create_settings_plugins = $this->get('auto_create_settings_plugins', null, null, true);
                        if ($auto_create_settings_plugins) {

                 		Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => 'settings',   'entity'=>'global', 'create_p' => 0, 'read_p' => 1, 'update_p' => 1, 'delete_p' => 0, 'import_p' => 1, 'export_p' => 0));
 						}
						
						// Set permissions: surveys 
                        $auto_create_surveys = $this->get('auto_create_surveys', null, null, true);
                        if ($auto_create_surveys) {

                 		Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => 'surveys',   'entity'=>'global', 'create_p' => 1, 'read_p' => 1, 'update_p' => 1, 'delete_p' => 1, 'export_p' => 1));
 						}
						
						// Set permissions: Templates 
                        $auto_create_templates = $this->get('auto_create_templates', null, null, true);
                        if ($auto_create_templates) {

                 		Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => 'templates',   'entity'=>'global', 'create_p' => 1, 'read_p' => 1, 'update_p' => 1, 'delete_p' => 1, 'import_p' => 1, 'export_p' => 1));
 						}
					
						// Set permissions: User Groups 
                        $auto_create_user_groups = $this->get('auto_create_user_groups', null, null, true);
                        if ($auto_create_user_groups) {

                 		Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => 'usergroups',   'entity'=>'global', 'create_p' => 1, 'read_p' => 1, 'update_p' => 1, 'delete_p' => 1, 'export_p' => 0));
 						}
						
						
						
                        // read again user from newly created entry
                        $oUser = $this->api->getUserByName($sUser);

                        $this->setAuthSuccess($oUser);
                    }
                    else {
                        $this->setAuthFailure(self::ERROR_USERNAME_INVALID);
                    }
                }
                else {
                    $this->setAuthFailure(self::ERROR_USERNAME_INVALID);
                }
            } else {
                // Update user?
                $auto_update_users = $this->get('auto_update_users', null, null, true);
                if ($auto_update_users) {
                    $changes = array (
                        'full_name' => $name, 
                        'email' => $mail,
                    );

                    User::model()->updateByPk($oUser->uid, $changes);
		   

 $oUser = $this->api->getUserByName($sUser);
                }

                $this->setAuthSuccess($oUser);
            }
        }
    }
}
