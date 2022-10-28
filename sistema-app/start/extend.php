<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

/*
+--------------------------------------------------------------------------
| Redireciona a una pagina en especifica
+--------------------------------------------------------------------------
*/

function redirect($url) {
	header('Location: ' . $url);
	exit;
}


/*
+--------------------------------------------------------------------------
| Devuelve el texto con los caracteres especiales escapados
+--------------------------------------------------------------------------
*/

function escape($text) {
	$text = htmlspecialchars($text, ENT_QUOTES);
	$text = addslashes($text);
	return $text;
}

/*
+--------------------------------------------------------------------------
| Devuelve el texto con el primer caracter en mayuscula y sin lineas
+--------------------------------------------------------------------------
*/

function strtocapitalize($text) {
	$text = strtoupper(substr($text, 0, 1)) . substr($text, 1);
	$text = str_replace('_', ' ', $text);
	return $text;
}

/*
+--------------------------------------------------------------------------
| Convierte una fecha al formato yyyy-mm-dd
+--------------------------------------------------------------------------
*/

function date_encode($date) {
	if (is_numeric(substr($date, 2, 1))) {
		$day = substr($date, 8, 2);
		$month = substr($date, 5, 2);
		$year = substr($date, 0, 4);
	} else {
		$day = substr($date, 0, 2);
		$month = substr($date, 3, 2);
		$year = substr($date, 6, 4);
	}
	return $year . '-' . $month . '-' . $day;
}

/*
+--------------------------------------------------------------------------
| Convierte una fecha al formato yyyy/mm/dd
+--------------------------------------------------------------------------
*/

function date_decode($date, $format = 'Y-m-d') {
	$format = ($format == '') ? 'Y-m-d' : $format;
	$date = explode('-', $date);
	$format = str_replace('Y', $date[0], $format);
	$format = str_replace('m', $date[1], $format);
	$format = str_replace('d', $date[2], $format);
	return $format;
}

/*
+--------------------------------------------------------------------------
| Verifica si es una fecha
+--------------------------------------------------------------------------
*/

function is_date($date) {
	if (preg_match('/^((1|2)[0-9]{3})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $date) || preg_match('/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-((1|2)[0-9]{3})$/', $date)){
		$date = explode('-', $date);
		if (checkdate($date[1], $date[2], $date[0]) || checkdate($date[1], $date[0], $date[2])) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/*
+--------------------------------------------------------------------------
| Obtiene el formato numeral de una fecha
+--------------------------------------------------------------------------
*/

function get_date_numeral($format = 'Y-m-d') {
	$format = ($format == '') ? 'Y-m-d' : $format;
	$format = str_replace('Y', '9999', $format);
	$format = str_replace('m', '99', $format);
	$format = str_replace('d', '99', $format);
	return $format;
}

/*
+--------------------------------------------------------------------------
| Obtiene el formato textual de una fecha
+--------------------------------------------------------------------------
*/

function get_date_textual($format = 'Y-m-d') {
	$format = ($format == '') ? 'Y-m-d' : $format;
	$format = str_replace('Y', 'yyyy', $format);
	$format = str_replace('m', 'mm', $format);
	$format = str_replace('d', 'dd', $format);
	return $format;
}

/*
|------------------------------------------------------------
| Retorna la fecha actual
|------------------------------------------------------------
*/

function now($format = 'Y-m-d') {
	return date($format);
}

/*
|--------------------------------------------------------------------------
| Retorna el nombre del dia de una fecha
|--------------------------------------------------------------------------
*/

function get_date_literal($date) {
	$days = array(1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo');
	$months = array(1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre');
	$day = $days[date('N', strtotime($date))];
	$date = explode('-', $date);
	return $day . ' ' . intval($date[2]) . ' de ' . $months[intval($date[1])] . ' de ' . intval($date[0]);
}

/*
|------------------------------------------------------------
| Retorna una fecha con la suma de x dias
|------------------------------------------------------------
*/

function add_day($date, $day = 1) { 
	$date = strtotime('+' . $day . ' day', strtotime($date));
	return date('Y-m-d', $date);
}

/*
+--------------------------------------------------------------------------
| Verifica si una peticion es por medio de ajax
+--------------------------------------------------------------------------
*/

function is_ajax() {
	return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/*
+--------------------------------------------------------------------------
| Verifica si una peticion llego por el metodo post
+--------------------------------------------------------------------------
*/

function is_post() {
	return $_SERVER['REQUEST_METHOD'] == 'POST';
}

/*
+--------------------------------------------------------------------------
| Muestra la vista 404
+--------------------------------------------------------------------------
*/

function show_template($template) {
	return templates . '/' . $template . '.php';
}

/*
+--------------------------------------------------------------------------
| Muestra la vista 400 bad request
+--------------------------------------------------------------------------
*/

function bad_request() {
	return show_template('400');
}

/*
+--------------------------------------------------------------------------
| Muestra la vista 401 unauthorized
+--------------------------------------------------------------------------
*/

function unauthorized() {
	return show_template('401');
}

/*
+--------------------------------------------------------------------------
| Muestra la vista 404 not found
+--------------------------------------------------------------------------
*/

function not_found() {
	return show_template('404');
}

/*
+--------------------------------------------------------------------------
| Devuelve la url de la pagina anterior
+--------------------------------------------------------------------------
*/

function back() {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $back = $_SERVER['HTTP_REFERER'];
        $back = explode('?', $back);
        $back = '?' . $back[1];
        return $back;
    } else {
        return index_public;
    }
}

/*
|------------------------------------------------------------
| Crea una notificacion de error
|------------------------------------------------------------
*/

function set_notification($type = 'info', $title = 'title', $content = 'content') {
    $_SESSION[temporary] = array(
        'type' => $type,
        'title' => $title,
        'content' => $content
    );
}

/*
|------------------------------------------------------------
| Elimina y obtiene una notificacion de error
|------------------------------------------------------------
*/

function get_notification() {
    if (isset($_SESSION[temporary])) {
        $notification = $_SESSION[temporary];
        unset($_SESSION[temporary]);
    } else {
        $notification = array();
    }
    return $notification;
}
/*
+--------------------------------------------------------------------------
| Verifica si un menu tiene predecesores
+--------------------------------------------------------------------------
*/

function verificar_submenu($menus, $id) {
	foreach ($menus as $menu) {
		if ($menu['antecesor_id'] == $id) {
			return true;
		}
	}
	return false;
}

/*
+--------------------------------------------------------------------------
| Construye el menu
+--------------------------------------------------------------------------
*/

function construir_menu($menus, $antecesor = 0) {
	$html = '';
	foreach ($menus as $menu) {
		if ($menu['antecesor_id'] != null) {
			if ($menu['antecesor_id'] == $antecesor) {
				if (verificar_submenu($menus, $menu['id_menu'])) {
					if ($antecesor == 0) {
						$html .= '<li class="dropdown"><a href="#" data-toggle="dropdown"><span class="glyphicon glyphicon-' . escape($menu['icono']) . '"></span></span> <span class="hidden-sm">'  . (str_replace('Módulo', '<span class="hidden-md">Módulo</span>', $menu['menu'])) . '</span><span class="glyphicon glyphicon-menu-down visible-xs-inline pull-right"></span></a>';
						$html .= '<ul class="dropdown-menu">';
						$html .= '<li class="dropdown-header visible-sm-block"><span>'  . (str_replace('Módulo', '<span class="hidden-md">Módulo</span>', $menu['menu'])) . '</span></li>';
					} else {
						$html .= '<li class="dropdown-submenu"><a href="#" data-toggle="dropdown"><span class="glyphicon glyphicon-' . escape($menu['icono']) . '"></span> <span>' . (str_replace('Módulo', '<span class="hidden-md">Módulo</span>', $menu['menu'])) . '</span><span class="glyphicon glyphicon-menu-down visible-xs-inline pull-right"></span></a>';
						$html .= '<ul class="dropdown-menu">';
					}
					$html .= construir_menu($menus, $menu['id_menu']);
					$html .= '</ul></li>';
				} else {
					if ($antecesor == 0) {
						$html .= '<li><a href="' . (($menu['ruta'] == '') ? '#' : escape($menu['ruta'])) . '"><span class="glyphicon glyphicon-' . escape($menu['icono']) . '"></span> <span class="hidden-sm">'  . (str_replace('Módulo', '<span class="hidden-md">Módulo</span>', $menu['menu'])) . '</span></a></li>';
					} else {
						$html .= '<li><a href="' . (($menu['ruta'] == '') ? '#' : escape($menu['ruta'])) . '"><span class="glyphicon glyphicon-' . escape($menu['icono']) . '"></span> <span>'  . (str_replace('Módulo', '<span class="hidden-md">Módulo</span>', $menu['menu'])) . '</span></a></li>';
					}
				}
			}
		} else {
			$html = '';
			break;
		}
	}
	return $html;
}

/*
+--------------------------------------------------------------------------
| Devuelve el menu ordenado
+--------------------------------------------------------------------------
*/

function ordenar_menu($menus, $antecesor = 0, $lista = array()) {
	foreach ($menus as $menu) {
		if ($menu['antecesor_id'] == $antecesor) {
			if (verificar_submenu($menus, $menu['id_menu'])) {
				$menu['antecesor'] = 1;
				array_push($lista, $menu);
				$lista = ordenar_menu($menus, $menu['id_menu'], $lista);
			} else {
				$menu['antecesor'] = 0;
				array_push($lista, $menu);
			}
		}
	}
	return $lista;
}

/*
+--------------------------------------------------------------------------
| Devuelve un array con los directorios de una ubicacion
+--------------------------------------------------------------------------
*/

function get_directories($route) {
	if (is_dir($route)) {
		$array_directories = array();
		$directories = opendir($route);
		while ($directory = readdir($directories)) {
			if ($directory != '.' && $directory != '..' && is_dir($route . '/' . $directory)) {
				//$array_directories[] = $directory;
				array_push($array_directories, $directory);
			}
		}
		closedir($directories);
		return $array_directories;
	} else {
		return false;
	}
}

/*
+--------------------------------------------------------------------------
| Devuelve un array con los archivos de un directorio
+--------------------------------------------------------------------------
*/

function get_files($route) {
	if (is_dir($route)) {
		$array_files = array();
		$files = opendir($route);
		while ($file = readdir($files)) {
			if ($file != '.' && $file != '..' && !is_dir($route . '/' . $file)) {
				$extention = substr($file, -4);
				$file = substr($file, 0, -4);
				if ($file != 'index' && $extention == '.php') {
					$array_files[] = $file;
				}
			}
		}
		closedir($files);
		return $array_files;
	} else {
		return false;
	}
}

/*
+--------------------------------------------------------------------------
| Crea un archivo
+--------------------------------------------------------------------------
*/

function file_create($route) {
	if (!file_exists($route)) {
		$file = fopen($route, 'x');
		fclose($file);
	}
}

/*
+--------------------------------------------------------------------------
| Elimina un archivo
+--------------------------------------------------------------------------
*/

function file_delete($route) {
	if (file_exists($route)) {
		unlink($route);
	}
}

/*
|------------------------------------------------------------
| Retorna un texto con los espacios limpios
|------------------------------------------------------------
*/

function clear($text) {
	$text = preg_replace('/\s+/', ' ', $text);
	$text = trim($text);
	$text = addslashes($text);
	return $text;
}

/*
|------------------------------------------------------------
| Retorna un texto convertido en mayusculas
|------------------------------------------------------------
*/

function upper($text) {
	$text = mb_strtoupper($text, 'UTF-8');
	return $text;
}

/*
|------------------------------------------------------------
| Retorna un texto convertido en minusculas
|------------------------------------------------------------
*/

function lower($text) {
	$text = mb_strtolower($text, 'UTF-8');
	return $text;
}

/*
|------------------------------------------------------------
| Retorna un texto convertido en minusculas excepto la primera
|------------------------------------------------------------
*/

function capitalize($text) {
	$text = upper(mb_substr($text, 0, 1, 'UTF-8')) . lower(mb_substr($text, 1, mb_strlen($text), 'UTF-8'));
	return $text;
}

/*
+--------------------------------------------------------------------------
| Devuelve un string con caracteres aleatorios
+--------------------------------------------------------------------------
*/

function random_string($length = 6) {
	$text = '';
	$characters = '0123456789abcdefghijkmnopqrstuvwxyz';
	$nro = 0;
	while ($nro < $length) {
		$caracter = substr($characters, mt_rand(0, strlen($characters)-1), 1);
		$text .= $caracter;
		$nro++;
	}
	return $text;
}

/*
+--------------------------------------------------------------------------
| devuelve la cantidad de unidades de un producto
+--------------------------------------------------------------------------
*/

function cantidad_unidad($db, $id, $unidad){
    $producto = $db->select('unidad_id')->from('inv_productos')->where('id_producto',$id)->fetch_first();
    if($producto['unidad_id']!=$unidad){
        $otra_unidad = $db->select('cantidad_unidad')->from('inv_asignaciones')->where(array('unidad_id' => $unidad, 'producto_id' => $id))->fetch_first();
        return $otra_unidad['cantidad_unidad'];
    }else{
        return 1;
    }
}
/*
+--------------------------------------------------------------------------
| devuelve la unidad de un producto
+--------------------------------------------------------------------------
*/

function nombre_unidad($db, $id_unidad){
    $unidad = $db->select('unidad')->from('inv_unidades')->where('id_unidad',$id_unidad)->fetch_first();
    if($unidad){
        return $unidad['unidad'];
    }else{
        return 'SIN UNIDAD';
    }
}

/*
+--------------------------------------------------------------------------
| devuelve el precio de la unidad del producto
+--------------------------------------------------------------------------
*/

function precio_unidad($db, $id, $unidad){
    $producto = $db->select('unidad_id, precio_actual')->from('inv_productos')->where('id_producto',$id)->fetch_first();
    if($producto['unidad_id']!=$unidad){
        $otra_unidad = $db->select('cantidad_unidad, otro_precio')->from('inv_asignaciones')->where(array('unidad_id' => $unidad, 'producto_id' => $id))->fetch_first();
        return $otra_unidad['otro_precio'];
    }else{
        return $producto['precio_actual'];
    }
}

/*
+--------------------------------------------------------------------------
| devuelve si esta dentro de una coordenada
+--------------------------------------------------------------------------
*/

function pointInPolygon($point, $polygon, $pointOnVertex = true) {
    $this->pointOnVertex = $pointOnVertex;

    // Transformar la cadena de coordenadas en matrices con valores "x" e "y"
    $point = $this->pointStringToCoordinates($point);
    $vertices = array();
    foreach ($polygon as $vertex) {
        $vertices[] = $this->pointStringToCoordinates($vertex);
    }

    // Checar si el punto se encuentra exactamente en un vértice
    if ($this->pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
        return "vertex";
    }

    // Checar si el punto está adentro del poligono o en el borde
    $intersections = 0;
    $vertices_count = count($vertices);

    for ($i=1; $i < $vertices_count; $i++) {
        $vertex1 = $vertices[$i-1];
        $vertex2 = $vertices[$i];
        if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Checar si el punto está en un segmento horizontal
            return "boundary";
        }
        if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) {
            $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];
            if ($xinters == $point['x']) { // Checar si el punto está en un segmento (otro que horizontal)
                return "boundary";
            }
            if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                $intersections++;
            }
        }
    }
    // Si el número de intersecciones es impar, el punto está dentro del poligono.
    if ($intersections % 2 != 0) {
        return "dentro";
    } else {
        return "fuera";
    }
}

function pointOnVertex($point, $vertices) {
    foreach($vertices as $vertex) {
        if ($point == $vertex) {
            return true;
        }
    }

}

function pointStringToCoordinates($pointString) {
    $coordinates = explode(" ", $pointString);
    return array("x" => $coordinates[0], "y" => $coordinates[1]);
}

/*
+--------------------------------------------------------------------------
| devuelve el precio de un producto
+--------------------------------------------------------------------------
*/

function loteProducto($db, $id_producto, $id_almacen) {
    $egreso = $db->query("SELECT d.producto_id, SUM(d.cantidad) AS cantidad_egresos
		FROM inv_egresos e
		LEFT JOIN inv_egresos_detalles d ON e.id_egreso = d.egreso_id
		WHERE e.almacen_id = '$id_almacen' AND d.producto_id = '$id_producto'
		GROUP BY d.producto_id")->fetch_first();
    $ingresos = $db->query("SELECT * FROM inv_ingresos a LEFT JOIN inv_ingresos_detalles b ON a.id_ingreso = b.ingreso_id WHERE a.almacen_id = '$id_almacen' AND b.producto_id = '$id_producto'")->fetch();
    $sum = 0;
    $aux = array();
    foreach($ingresos as $ingreso){
        if($sum < $egreso['cantidad_egresos']){
            $aux = $ingreso;
            $sum = $sum + $egreso['cantidad_egresos'];
        }
    }
    return $aux;
}

/*
+--------------------------------------------------------------------------
| devuelve el numero de movimiento
---------------------------------------------------------------------------
*/
function generarMovimiento($db, $id_empleado, $movimiento, $id_almacen) {
    if ($movimiento == 'CP' || $movimiento == 'DV' || $movimiento == 'IM') {
        //COMPRAS
        $id = $db->query("SELECT MAX(nro_movimiento) AS nro_movimiento FROM inv_ingresos ")->fetch_first();
        if($id['nro_movimiento']){
            // $a = $movimiento.date('y');
            // $aux = explode($a,$id['nro_movimiento']);
            // $c = intval($aux[0]) + 1;
            // $aux1 = date('y') . str_pad($c, 7, "0", STR_PAD_LEFT);
            $aux1 = $id['nro_movimiento'] +1;
        }else{
            $b = str_pad('1', 7, "0", STR_PAD_LEFT);
            $aux1 = date('y') . $b;
        }
        $nro_movimiento = $aux1;
        // $nro_movimiento = $id_empleado.$id_almacen.$movimiento.'-'.$aux1;
        // $nro_movimiento = $id_almacen . $movimiento . $aux1;
    // } elseif ($movimiento == 'TD') {
    //     //TRASPASO DIRECTO
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo = 'Traspaso' and almacen_id = '$id_almacen' ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         // $aux = explode('-',$id['nro_movimiento']);
    //         // $aux1 = $aux[1] + 1;
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         $c = intval($aux[1]) + 1;
    //         $aux1 = date('y') . str_pad($c, 5, "0", STR_PAD_LEFT);
    //     }else{
    //         $b = str_pad('1', 5, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     // $nro_movimiento = $id_empleado.$id_almacen.$movimiento.'-'.$aux1;
    //     $nro_movimiento = $id_almacen . $movimiento . $aux1;
    // } elseif ($movimiento == 'TE') {
    //     //TRASPASO ETAPAS
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo = 'Etapa' and almacen_id = '$id_almacen' ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         $c = intval($aux[1]) + 1;
    //         $aux1 = date('y') . str_pad($c, 5, "0", STR_PAD_LEFT);
    //     }else{
    //         $b = str_pad('1', 5, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     $nro_movimiento = $id_almacen . $movimiento . $aux1;
    // } elseif ($movimiento == 'BJ') {
    //     //BAJA
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo = 'Baja' and almacen_id = '$id_almacen' ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         $c = intval($aux[1]) + 1;
    //         $aux1 = date('y') . str_pad($c, 5, "0", STR_PAD_LEFT);
    //     }else{
    //         $b = str_pad('1', 5, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     $nro_movimiento = $id_almacen . $movimiento . $aux1;
    // } elseif ($movimiento == 'DV') {
    //     //DEVOLUCIONES
    //     $id = $db->query("SELECT id_ingreso, nro_movimiento FROM inv_ingresos WHERE tipo = 'Devolucion' and almacen_id = '$id_almacen' ORDER BY id_ingreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         $c = intval($aux[1]) + 1;
    //         $aux1 = date('y') . str_pad($c, 5, "0", STR_PAD_LEFT);
    //     }else{
    //         $b = str_pad('1', 5, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     $nro_movimiento = $id_almacen . $movimiento . $aux1;
    // } elseif ($movimiento == 'IM') {
    //     //IMPORTACIONES
    //     $id = $db->query("SELECT id_ingreso, nro_movimiento FROM inv_ingresos WHERE tipo = 'Importacion' and almacen_id = '$id_almacen' ORDER BY id_ingreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         $c = intval($aux[1]) + 1;
    //         $aux1 = date('y') . str_pad($c, 5, "0", STR_PAD_LEFT);
    //     }else{
    //         $b = str_pad('1', 5, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     $nro_movimiento = $id_almacen . $movimiento . $aux1;
    // } elseif ($movimiento == 'PV') {
    //     //PREVENTAS
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo IN ('Devolucion','Venta','Preventa') ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
    //     // var_dump($id); die();
    //     if($id['nro_movimiento']){
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         $c = intval($aux[0]) + 1;
    //         $aux1 = date('y') . str_pad($c, 7, "0", STR_PAD_LEFT);
    //     }else{
    //         $b = str_pad('1', 7, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     $nro_movimiento = $aux1;
    } elseif ($movimiento == 'PF') {
        //PROFORMA

    } else {
        $id = $db->query("SELECT MAX(nro_movimiento) AS nro_movimiento FROM inv_egresos ")->fetch_first(); //ORDER BY id_egreso DESC LIMIT 1
        if($id['nro_movimiento']){
            // $a = $movimiento.date('y');
            // $aux = explode($a,$id['nro_movimiento']);
            // $c = intval($aux[0]) + 1;
            // $aux1 = date('y') . str_pad($c, 7, "0", STR_PAD_LEFT);
            $aux1 = $id['nro_movimiento'] +1;
        }else{
            $b = str_pad('1', 7, "0", STR_PAD_LEFT);
            $aux1 = date('y') . $b;
        }
        $nro_movimiento = $aux1;
    }
    // elseif ($movimiento == 'NR') {
    //     //REMISION
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo IN ('Devolucion','Venta','Preventa') ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
        
    //     if($id['nro_movimiento']){
    //         $a = 'NV'.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         // var_dump($aux); die();
    //         $c = intval($aux[0]) + 1;
    //         $aux1 = date('y') . str_pad($c, 7, "0", STR_PAD_LEFT);
    //     }else{
    //         // $aux1 = 1;
    //         $b = str_pad('1', 7, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     // $nro_movimiento = $id_empleado.$id_almacen.$movimiento.'-'.$aux1;
    //     $nro_movimiento = $aux1;
    // } elseif ($movimiento == 'VE') {
    //     //VENTA ELECTRONICA
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo IN ('Devolucion','Venta','Preventa') ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         // $aux = explode('-',$id['nro_movimiento']);
    //         // $aux1 = $aux[1] + 1;
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         // var_dump($aux); die();
    //         $c = intval($aux[0]) + 1;
    //         $aux1 = date('y') . str_pad($c, 7, "0", STR_PAD_LEFT);
    //     }else{
    //         // $aux1 = 1;
    //         $b = str_pad('1', 7, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     // $nro_movimiento = $id_empleado.$id_almacen.$movimiento.'-'.$aux1;
    //     $nro_movimiento = $aux1;
    // } elseif ($movimiento == 'VM') {
    //     //VENTA MANUAL
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo IN ('Devolucion','Venta','Preventa') ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         $a = $movimiento.date('y');
    //         $aux = explode($a,$id['nro_movimiento']);
    //         $c = intval($aux[0]) + 1;
    //         $aux1 = date('y') . str_pad($c, 7, "0", STR_PAD_LEFT);
    //     }else{
    //         $b = str_pad('1', 7, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     $nro_movimiento = $aux1;
    // } elseif ($movimiento == 'RP') {
    //     // DEVOLUCION NOTA DE REMISION
    //     $movimiento = 'DV';
    //     $id = $db->query("SELECT id_egreso, nro_movimiento FROM inv_egresos WHERE tipo IN ('Devolucion','Venta','Preventa') ORDER BY id_egreso DESC LIMIT 1")->fetch_first();
    //     if($id['nro_movimiento']){
    //         // $a = $movimiento.date('y');
    //         // $aux = explode($a,$id['nro_movimiento']);
    //         // $c = intval($aux[0]) + 1;
    //         // $aux1 = date('y') . str_pad($c, 7, "0", STR_PAD_LEFT);
    //         $aux1 = $id['nro_movimiento'] +1;
    //     }else{
    //         $b = str_pad('1', 7, "0", STR_PAD_LEFT);
    //         $aux1 = date('y') . $b;
    //     }
    //     $nro_movimiento = $aux1;
    // }

    return $nro_movimiento;
}

function generaPago($db, $id_empleado, $id_pago)
{
    $id_egreso = $db->query("SELECT p.movimiento_id FROM inv_pagos p WHERE p.id_pago = $id_pago ORDER BY p.movimiento_id DESC LIMIT 1")->fetch_first();
    // echo $id_egreso['movimiento_id'];
    $id = $db->query("SELECT pd.id_pago_detalle, pd.nro_pago FROM inv_pagos_detalles pd WHERE pd.tipo_pago = 'EFECTIVO' AND pd.estado = 1 AND pd.empleado_id = '$id_empleado' ORDER BY pd.nro_pago DESC LIMIT 1")->fetch_first();
    // echo $id['nro_pago'];
    if($id['nro_pago']){
        $aux = explode('-',$id['nro_pago']);
        $aux1 = $aux[1] + 1;
    }else{
        $aux1 = 1;
    }
    $nro_movimiento = $id_empleado.trim($id_egreso['movimiento_id']).'PC'.'-'.$aux1;
    return $nro_movimiento;

}

