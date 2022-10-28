<?php

// Obtiene el id_user
$id_user = (sizeof($params) > 0) ? $params[0] : 0;

if ($id_user == 0) {
	// Obtiene los users
	$users = $db->select("u.*, r.rol, ifnull(e.paterno, '') as paterno, ifnull(e.materno, '') as materno, ifnull(e.nombres, '') as nombres")
				->from('sys_users u')
				->join('sys_roles r', 'u.rol_id = r.id_rol', 'left')
				->join('sys_empleados e', 'u.persona_id = e.id_empleado', 'left')
				->fetch();
} else {
	// Obtiene los permisos
	$permisos = explode(',', permits);
	
	// Almacena los permisos en variables
	$permiso_ver = in_array('ver', $permisos);
	
	// Obtiene el user
	$user = $db->query("SELECT u.*, r.rol, emp.*, u.id_user, emp.id_empleado
                    FROM sys_users as u
                    LEFT JOIN sys_empleados as emp ON u.persona_id = emp.id_empleado
                    LEFT JOIN sys_roles as r ON r.id_rol = u.rol_id                    
                    WHERE u.id_user = '$id_user'")
			   ->fetch_first();
	
	// Verifica si existe el user
	if (!$user) {
		// Error 404
		require_once not_found();
		exit;
	} elseif (!$permiso_ver) {
		// Error 401
		require_once bad_request();
		exit;
	}
}

// Importa la libreria para el generado del pdf
require_once libraries . '/tcpdf/tcpdf.php';

// Define variables globales
define('NOMBRE', escape($_institution['nombre']));
define('IMAGEN', escape($_institution['imagen_encabezado']));
define('PROPIETARIO', escape($_institution['propietario']));
define('PIE', escape($_institution['pie_pagina']));
define('FECHA', date(escape($_institution['formato'])) . ' ' . date('H:i:s'));

// Extiende la clase TCPDF para crear Header y Footer
class MYPDF extends TCPDF {
	public function Header() {
		$this->Ln(5);
		$this->SetFont(PDF_FONT_NAME_HEAD, 'I', PDF_FONT_SIZE_HEAD);
		$this->Cell(0, 5, NOMBRE, 0, true, 'R', false, '', 0, false, 'T', 'M');
		$this->Cell(0, 5, PROPIETARIO, 0, true, 'R', false, '', 0, false, 'T', 'M');
		$this->Cell(0, 5, FECHA, 'B', true, 'R', false, '', 0, false, 'T', 'M');
		$imagen = (IMAGEN != '') ? institucion . '/' . IMAGEN : imgs . '/empty.jpg' ;
		$this->Image($imagen, PDF_MARGIN_LEFT, 5, '', 14, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}
	
	public function Footer() {
		$this->SetY(-10);
		$this->SetFont(PDF_FONT_NAME_HEAD, 'I', PDF_FONT_SIZE_HEAD);
		$length = ($this->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT) / 2;
		$number = $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
		$this->Cell($length, 5, $number, 'T', false, 'L', false, '', 0, false, 'T', 'M');
		$this->Cell($length, 5, PIE, 'T', true, 'R', false, '', 0, false, 'T', 'M');
	}
}

// Instancia el documento PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Asigna la informacion al documento
$pdf->SetCreator(name_autor);
$pdf->SetAuthor(name_autor);
$pdf->SetTitle($_institution['nombre']);
$pdf->SetSubject($_institution['propietario']);
$pdf->SetKeywords($_institution['sigla']);

// Asignamos margenes
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// ------------------------------------------------------------

if ($id_user == 0) {
	// Documento general -----------------------------------------------------

	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');
	
	// Adiciona la pagina
	$pdf->AddPage();
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'LISTA DE USUARIOS', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', 7.5);
	
	// Estructura la tabla
	$body = '';
	foreach ($users as $nro => $user) {
		$body .= '<tr>';
		$body .= '<td>' . ($nro + 1) . '</td>';
		$body .= '<td>' . escape($user['username']) . '</td>';
		$body .= '<td>' . escape($user['email']) . '</td>';
		$body .= '<td>' . escape($user['rol']) . '</td>';
		$body .= '<td>' . ((escape($user['active']) == 1) ? 'Activado' : 'Bloqueado') . '</td>';
		$body .= '<td>' . escape($user['nombres'] . ' ' . $user['paterno'] . ' ' . $user['materno']) . '</td>';
		$body .= '</tr>';
	}
	
	$body = ($body == '') ? '<tr><td colspan="6" align="center">No existen users registrados en la base de datos</td></tr>' : $body;
	
	// Formateamos la tabla
	$tabla = <<<EOD
	<style>
	th {
		background-color: #eee;
		border: 1px solid #444;
		font-weight: bold;
	}
	td {
		border-left: 1px solid #444;
		border-right: 1px solid #444;
	}
	table {
		border-bottom: 1px solid #444;
	}
	</style>
	<table cellpadding="5">
		<tr>
			<th width="6%">#</th>
			<th width="18%">Usuario</th>
			<th width="25%">Correo</th>
			<th width="16%">Rol</th>
			<th width="10%">Estado</th>
			<th width="25%">Empleado</th>
		</tr>
		$body
	</table>
EOD;
	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'users_' . date('Y-m-d_H-i-s') . '.pdf';
} else {
	// Documento individual --------------------------------------------------
	
	// Asigna la orientacion de la pagina
	$pdf->SetPageOrientation('P');
	
	// Adiciona la pagina
	$pdf->AddPage();
	
	// Establece la fuente del titulo
	$pdf->SetFont(PDF_FONT_NAME_MAIN, 'BU', PDF_FONT_SIZE_MAIN);
	
	// Titulo del documento
	$pdf->Cell(0, 10, 'DETALLE DE USUARIO', 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Establece la fuente del titulo
	$pdf->SetFont('helvetica', 'I', 10);
	
	// Subtitulo del documento
	$pdf->Cell(0, 5, 'ID: ' . $id_user, 0, true, 'C', false, '', 0, false, 'T', 'M');
	
	// Salto de linea
	$pdf->Ln(5);
	
	// Establece la fuente del contenido
	$pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);
	
	// Define las variables
	$valor_avatar = ($user['avatar'] == '') ? imgs . '/avatar.jpg' : profiles . '/' . escape($user['avatar']);
	$valor_username = escape($user['username']);
	$valor_email = escape($user['email']);
	$valor_rol = escape($user['rol']);
	$valor_empleado = escape($user['nombres'] . ' ' . $user['paterno'] . ' ' . $user['materno']);
	$codigo = escape($user['codigo']) == '' ? 'No registrado' : escape($user['codigo']);
	$genero = escape($user['genero']) == '' ? 'No registrado' : escape($user['genero']);
	$telefono = escape($user['telefono']) == '' ? 'No registrado' : escape($user['telefono']);
	$fecha_nacimiento = escape($user['fecha_nacimiento']) == '0000-00-00' ? 'No registrado' : escape($user['fecha_nacimiento']);
	$valor_active = (escape($user['active']) == 1) ? 'Activado' : 'Bloqueado';
	
	// Formateamos la tabla
	$tabla = <<<EOD
	<style>
	th {
		background-color: #eee;
		font-weight: bold;
		text-align: right;
		border-right: 1px solid #444;
	}
	img {
		border: 1px solid #444;
		width: 150px;
	}
	</style>
	<table cellpadding="1">
		<tr>
			<td width="10%"></td>
			<td width="80%" align="center">
				<img src="$valor_avatar">
			</td>
		</tr>
		<tr>
			<td width="10%"></td>
			<td width="80%" style="border: 1px solid #444;">
				<table cellpadding="5">
					<tr>
						<td colspan="2" style="border-bottom: 1px solid #444;"><b>Datos de usuario</b></td>
					</tr>
					<tr>
						<th width="40%">Usuario:</th>
						<td width="60%">$valor_username</td>
					</tr>
					<tr>
						<th width="40%">Correo:</th>
						<td width="60%">$valor_email</td>
					</tr>
					<tr>
						<th width="40%">Rol:</th>
						<td width="60%">$valor_rol</td>
					</tr>
					<tr>
						<th width="40%">Estado:</th>
						<td width="60%">$valor_active</td>
					</tr>
				</table>
			</td>
		</tr>
		<br>
		<tr>
			<td width="10%"></td>
			<td width="80%" style="border: 1px solid #444;">
				<table cellpadding="5">
					<tr>
						<td colspan="2" style="border-bottom: 1px solid #444;"><b>Datos del empleado</b></td>
					</tr>
					<tr>
						<th width="40%">Empleado:</th>
						<td width="60%">$valor_empleado</td>
					</tr>
					<tr>
						<th width="40%">Código:</th>
						<td width="60%">$codigo</td>
					</tr>
					<tr>
						<th width="40%">Genero:</th>
						<td width="60%">$genero</td>
					</tr>
					<tr>
						<th width="40%">Telefono:</th>
						<td width="60%">$telefono</td>
					</tr>
					<tr>
						<th width="40%">Fecha de nacimiento:</th>
						<td width="60%">$fecha_nacimiento</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
EOD;

	
	// Imprime la tabla
	$pdf->writeHTML($tabla, true, false, false, false, '');
	
	// Genera el nombre del archivo
	$nombre = 'user_' . $id_user . '_' . date('Y-m-d_H-i-s') . '.pdf';
}

// ------------------------------------------------------------

// Cierra y devuelve el fichero pdf
$pdf->Output($nombre, 'I');

?>
