<?php

/**
 * SimplePHP - Simple Framework PHP
 */

// Configuracion encabezados no-cache
header('Cache-Control: private, no-store, no-cache, must-revalidate, post-check = 0, pre-check = 0');
header('Expires: -1000000');
header('Pragma: no-cache');
//ini_set('display_errors', 1);error_reporting(E_ALL);
// Configuracion de la zona horaria
date_default_timezone_set('America/La_Paz');

// Ambiente de trabajo production/development
define('environment', 'development');

// Informacion del desarrollador
define('name_autor', 'ds');
define('email_autor', 'ds@gmail.com');
define('site_autor', 'https://www.checkcode.bo');
define('phone_autor', '591-xxxxxxxx');
define('credits', '&copy; 2022 www.checkcode.bo');

// Informacion del proyecto
define('name_app', 'sistema-app');
define('name_project', 'sistema');

// Rutas globales
define('ip_server', 'http://localhost/nextfacturas');
define('path_app', ip_server . '/' . name_app);
define('path_project', ip_server . '/' . name_project);
define('ip_local', ip_server . ':9000/');

// Directorios principales
define('app', '../' . name_app);
define('app2', '/' . name_app);
define('project', '../' . name_project);
define('project2', '/' . name_project);

// Directorios privados de la aplicacion
define('config', app . '/config');
define('files', app . '/files');
define('files2', app2 . '/files');
define('libraries', app . '/libraries');
define('modules', app . '/modules');
define('start', app . '/start');
define('storage', app . '/storage');
define('templates', app . '/templates');
define('profiles', files . '/profiles');
define('profiles2', files2 . '/profiles');
define('institucion', files . '/institucion');
define('productos', files . '/productos');
define('productos2', app2. '/files' . '/productos');
define('tiendas', files2 . '/tiendas');

// Directorios publicos de la aplicacion
define('css', project . '/css');
define('imgs', project . '/imgs');
define('imgs2', project2 . '/imgs');
define('js', project . '/js');
define('media', project . '/media');
define('themes', project . '/themes');
define('url1', 'https://nextcorpbol.com/z');

// Paginas principales
define('home', 'home');
define('site', 'site');
define('tools', 'tools');
define('index_private', '?/' . home . '/index');
define('index_public', '?/' . site . '/login');

// Variables de sesiones
define('user', 'user-sistema');
define('locale', 'locale-sistema');
define('temporary', 'temporary-sistema');

// Variables para cookies
define('remember', 'remember-sistema');

// Variables de seguridad
define('prefix', '@w1N');

// Variables de base de datos
define('host', 'localhost');
define('username', 'root');//
define('password', '');//

define('database', 'nextcorp_facturas');//
//define('database', 'nextcorp_sistema');//
//define('database', 'nextcorp_auxiliar');//
// define('database', 'nextcorp_distribucion');//
define('port', '3306');
