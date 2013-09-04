limesurvey-saml  (version 0.1)
==============================

SAML Authentication plugin for limesurvey (based on simpleSAMLphp)

The limesurvey-saml-0.1 works with limesurvey 2.0.5


Installation and configuration
------------------------------

This plugin requires a [http://simplesamlphp.org/docs/stable/simplesamlphp-sp](simmpleSAMLphp instance configured as an SP)

After that you will be enable to deploy your SAML plugin over your limesurvey environment.


Copy the AuthSAML folder inside core/plugins/

Access to the Limesurvey platform, enable the SAML plugin and configure the following parameters:

 * simplesamlphp_path: Path to the SimpleSAMLphp folder  (Ex. /var/www/simplesamlphp )

 * saml_authsource: SAML authentication source. (Review what value you set in your simpleSAMLphp SP instance (config/authsource.php)

 * saml_uid_mapping: SAML attributed used as username

 * saml_mail_mapping: SAML attributed used as email

 * saml_name_mapping: SAML attributed used as name

 * authtype_base: Authtype base. Use 'Authdb' if you do not know what value use.

 * storage_base: Storage base, Use 'DbStorage' if you do not know what value use.

 * auto_create_users: Let the SAML auth plugin create new users on limesurvey.

 * auto_update_users: Let the SAML auth plugin update users on limesurvey.

 * force_saml_login: Enable it when you want to only allow SAML authentication on limesurvey (no login form will be showed)



Author
------

Author: Sixto Martin Garcia <sixto.martin.garcia.@gmail.com>

http://es.linkedin.com/in/sixtomartin/en

