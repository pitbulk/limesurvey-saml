
Warning!
========
This SAML plugin only works for very old Limesurvey instances. (2.X)

I implemented comercial SAML plugin based on simpleSAMLphp as well as a version based on
php-saml which allows you adding the IdP settings on the LimeSurvey SAML configuration section.
It is compatible with Limesurvey3 and Limesurvey4 and has multiple features:

- Based on simpleSAMLphp or php-saml (configuration directly done on Limesurvey settings, instead simpleSAMLphp that manages it via filesystem).
- Supports multiple Identity providers
- Just-in-time provisioning (auto-crate users) assigning the permissions you set on the settings.
- Auto-update user data after SSO
- User Attribute Mapping
- Support for groups (crating them or just assign to the user if already exists)
- Hooks to extend functionality
- Configurable SAML Link showed on the login form.
- Ability to force SAML login on Limesurvey Login form.
- Avoid accounts created by SAML to login using normal login

Soon will be available at the LimeStore with a price of 150€, but if you need it now, contact me at sixto.martin.garcia+limesurvey@gmail.com

I also offer commercial support for 50€/h payable via Paypal or Wire-Transfer.


limesurvey-saml  (version 0.1)
==============================

SAML Authentication plugin for limesurvey (based on simpleSAMLphp)

The limesurvey-saml-0.1 works with limesurvey 2.0.5


This plugin allow you enable different workflows:

1. SAML ONLY

    Allow to only let saml authentication login.

    When the user access to the login page, he will be redirected to the IdP. After authenticate:

      a) If is the first time, the user account will be created (stored in the file)

      b) If the account already exists, the user account will be updated (stored in the file)


    HOW get that mode?

    Easy, check the param 'force_saml_login' of the plugin configuration


2. INTERNAL AUTH BACKEND + SAML

    Enable SAML as an extra authentication backend. (Keep 'force_saml_login' disabled)



Author
------

Author: Sixto Martin Garcia <sixto.martin.garcia.@gmail.com>

http://es.linkedin.com/in/sixtomartin/en


License
-------

GPL2 http://www.gnu.org/licenses/gpl.html


Documentation index:
====================

* How install and configure simpleSAMLphp as SP
* How install and enable the SAML plugin


How install and configure simpleSAMLphp as SP
=============================================

Install simpleSAMLphp
---------------------

First of all install the [simpleSAMLphp library dependences](http://simplesamlphp.org/docs/stable/simplesamlphp-install#section_3):

    CentOS --> yum install php5 php-ldap php-mcrypt php-mbstring php-xml mod_ssl openssl
    Debian --> apt-get install php5 php5-mcrypt php5-mhash php5-mysql openssl


Now we can download the [latest version of simpleSAMLphp](http://code.google.com/p/simplesamlphp/downloads/list>), now is the 1.11: ::

 Directly --> http://simplesamlphp.googlecode.com/files/simplesamlphp-1.11.0.tar.gz
 From subversion repository --> svn co http://simplesamlphp.googlecode.com/svn/tags/simplesamlphp-1.11.0

Put the simplesamlphp directory at ``/var/www/simplesamlphp``


The SSL cert
------------

simpleSAMLphp requires a SSL cert. We can buy one or create it.

Create a self-signed cert
.........................

In order to generate a self-signed cert you need openssl:

    Centos --> yum install openssl
    Debian --> apt-get install openssl

Using OpenSSL we will generate a self-signed certificate in 3 steps.

* Generate private key:

    openssl genrsa -out server.pem 1024

* Generate CSR: (In the "Common Name" set the domain of your instance)

    openssl req -new -key server.pem -out server.csr

* Generate Self Signed Key:

    openssl x509 -req -days 365 -in server.csr -signkey server.pem -out server.crt

Override the certs of the ``/var/www/simplesamlphp/cert`` folder with the  generated certs.


Configure simpleSAMLphp
-----------------------

Copy the default config file from the template directory:

    cp /var/www/simplesamlphp/config-templates/config.php /var/www/simplesamlphp/config/config.php


And configure some values:

    'auth.adminpassword' => 'secret'      # Set a new password for admin web interface

    'enable.saml20-idp' => true,          # Enable ssp as IdP

    'secretsalt' => 'secret',             # Set a Salt, in the config file there is documentation to generate it

    'technicalcontact_name' => 'Admin name',          # Set admin data
    'technicalcontact_email' => 'xxxx@example.com',

    'session.cookie.domain' => '.example.com',        # Set the global domain, to share cookie with the rest of componnets

In production environment set also those values:

    'admin.protectindexpage'        => true,    # To protect the index page of simpleSAMLphp
    'debug'                 =>      FALSE,
    'showerrors'            =>      FALSE,      # To hide error-trace


Change the permission for some directories, execute the following command at the simpleSAMLphp folder:

    chown -R apache:apache cert log data metadata



Copy the default authsource file from the template directory:

    cp /var/www/simplesamlphp/config-templates/authsources.php /var/www/simplesamlphp/config/authsources.php

And set admin and an sp source. Something like:

    <?php

        $config = array(

                'admin' => array(
                        'core:AdminPassword',
                ),

                'limesurvey' => array(
                    'saml:SP',

                    'entityID' => 'https://sp.example.com/simplesaml/module.php/saml/sp/metadata.php/limesurvey',

                    'idp' => 'https://idp.example.com/simplesaml/saml2/idp/metadata.php', # Set the entityID of the IdP you gonna use

                    'privatekey' => 'server.pem',
                    'certificate' => 'server.crt',
                ),
        );
    ?>


And now paste the metadata of your IdP in simpleSAMLphp format at ``/var/www/simplesamlphp/metadata/saml20-idp-remote.php``


Apache configuration
--------------------

The apache configuration may look like this:

 <VirtualHost *:80>
        ServerName sp.example.com
        DocumentRoot /var/www/simplesamlphp/www

        Alias /simplesaml /var/simplesamlphp/www
 </VirtualHost>

 <VirtualHost *:443>
        ServerName sp.example.com
        DocumentRoot /var/www/simplesamlphp/www

        Alias /simplesaml /var/simplesamlphp/www

        SSLEngine on
        SSLCertificateFile /var/www/simplesamlphp/cert/server.crt
        SSLCertificateKeyFile /var/www/simplesamlphp/cert/server.pem
 </VirtualHost>



Make sure that your IdP server runs HTTPS (SSL)



NTP server
----------

To get Saml2 run correctly we need have sure that all machine's clock are synced.

Install ntp:

    Centos --> yum install ntp
    Debian --> apt-get install ntp

Configure the ntp service `/etc/ntp.conf`:

    server 0.uk.pool.ntp.org
    server 1.uk.pool.ntp.org
    server 2.uk.pool.ntp.org
    server 3.uk.pool.ntp.org

`Check the` [ntp server list](http://www.pool.ntp.org/use.html) `and use the server that is near from your server.`

Enable the server and put it on the system boot

    Centos --> service ntpd start
               chkconfig ntpd on

    Debian -> /etc/init.d/ntp start
              update-rc.d ntp defaults



More info at [http://simplesamlphp.org/docs/stable/simplesamlphp-sp](http://simplesamlphp.org/docs/stable/simplesamlphp-sp)



How install and enable the SAML plugin
--------------------------------------

This plugin requires a [simpleSAMLphp instance configured as an SP](http://simplesamlphp.org/docs/stable/simplesamlphp-sp)

If your SP is ready, you will be able to enable your SAML plugin in order to add SAML support to your Limesurvey instance.


Copy the AuthSAML folder inside the folder plugins

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
