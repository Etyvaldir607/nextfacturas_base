<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Inicializa las sessiones
session_start();

// Verifica si la variable de session existe
if (!isset($_SESSION[user])){
	// Redirecciona al modulo index
	redirect(index_public);
}
