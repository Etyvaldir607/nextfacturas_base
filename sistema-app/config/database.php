<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Importa la libreria para el manejo de la base de datos
require_once libraries . '/mysqli-database-class/class.database.php';

// Instancia la base de datos $db = palabra reservada
$db = new Database(host, username, password, database, port);