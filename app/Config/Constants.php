<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code



//Idioma
define('LOCAIS_SUPORTADOS', ['pt', 'en']);

// Nome da aplicação
define('APP_NOME', 'Oficina CI4');
define('APP_VERSAO', 'v.1.0.0');

//Conexao com o banco de dados
define('DATABASE_HOST', 'localhost'); // 127.0.0.1
define('DATABASE_NOME', 'gestao_escolar');
define('DATABASE_USUARIO', 'root');
define('DATABASE_PASSWORD', '');


// encrypt
define('CHAVE_ENCRIPTACAO', 'uqj3w6phQ2Uj4C0YO1LQGezSqBENzc4+ya8iYnMo4z8=');

define('API_ACTIVE',        true);
define('API_VERSION',       '1.0.0');
define('API_NAME',          'SIGEST');
define('API_AUTHOR',        'SIGEST');
define('API_LICENSE',       'MIT');
define('API_DOCUMENTATION', 'https://api.example.com/docs');
define('API_CONTACT',        'contact@example.com');
define('API_DEBUG_LEVEL',     1); // 0 = Don't send error | 1 send error
define('API_USERNAME', 'SIGEST');
define('API_PASSWORD', 'tX23hiM298G3XrHhmrKXrZOmPYpDrt9UjadfFSjYaEU=');
define('API_PROJECT_2026001', "9344c37d384bc089e9714eefd470cbb1dc49a8689ee914ab2cc9ff6253f4be47");
define('API_PROJECT_2026002', "077b1e992162b27d332ca26f39a53e19369fbc9811256b945d7fb3ebd8e3f0f0");
define('API_BASE_URL', 'http://localhost/sigest/');

// http basic auth credentials
define('JWT_SECRET_DEV', "ujzpGSJT4LFg2Ke+SvWI5ct3HiMVrNBjOZsBDd+gBo0=");
define('JWT_SECRET_PROD', "tX23hiM298G3XrHhmrKXrZOmPYpDrt9UjadfFSjYaEU=");
define('JWT_EXPIRY', 7200);
define('APP_HTTPS', true);


// email constants
define('EMAIL_PROTOCOL', 'smtp');
define('EMAIL_SMTP_HOST', 'smtp.gmail.com');
define('EMAIL_SMTP_PORT', 587);
define('EMAIL_SMTP_CRYPTO', 'tls');
define('EMAIL_SMTP_USER', 'seuemail@gmail.com');
define('EMAIL_SMTP_PASS', 'sua_senha_de_app');
define('EMAIL_FROM', 'noreply@seudominio.com');
define('EMAIL_FROM_NAME', 'Suporte');
