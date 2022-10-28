<?php

if (is_post()) {

    //var_dump($_POST);

	// Verifica la existencia de los datos enviados
	if (isset($_POST['nombre']) && isset($_POST['telefono']) && isset($_POST['descripcion'])) {

        // Importa la libreria para subir la imagen
        require_once libraries . '/upload-class/class.upload.php';

        // Define la ruta
        $ruta = files . '/tiendas/';

        $data = get_object_vars(json_decode($_POST['data']));
        // Obtiene los datos del cliente
        $nombres = trim($_POST['nombre']);
        $nombres_factura = trim($_POST['nombre_factura']);
        $ci = trim($_POST['ci']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);
        $tipo = trim($_POST['tipo']);
        $descripcion = trim($_POST['descripcion']);
        $coordenadas = trim($_POST['atencion']);
        $id_grupo = trim($_POST['id_grupo']);
        
        if($_POST['$id_dia']!=''){
            $id_dia = $_POST['id_dia'];
            }else{
            $id_dia=0;
            }
        
        $ciudad_id=trim($_POST['ciudad']);
        // $empleado_id=trim($_POST['empleado']);
        $empleado_id=$_user['persona_id'];

        if($_POST['id_cliente']!=0){
            $id = $_POST['id_cliente'];

            // $codigo='';
            // $ceros=5-strlen($id);
            // for($i=0;$i<$ceros;$i++):
            //     $codigo.='0';
            // endfor;
            // $departamento=$db->query("SELECT abreviacion
            //                         FROM inv_departamentos AS d
            //                         LEFT JOIN inv_ciudades AS c ON d.id_departamento=c.departamento_id
            //                         WHERE id_ciudad='{$ciudad_id}'")->fetch_first()['abreviacion'];
            // $codigo.=$id.$departamento;
            // $DatosU=['codigo'=>$codigo];


            if(isset($_FILES['imagen'])){
                $imagen = $_FILES['imagen'];

                list($ancho, $alto) = getimagesize($imagen['tmp_name']);

                $ancho = $ancho * $data['scale'];
                $alto = $alto * $data['scale'];

                // Define la extension de la imagen
                $extension = 'jpg';

                $imagen_final = md5(secret . random_string() . $nombres);

                // Instancia la imagen
                $imagen = new upload($imagen);

                if ($imagen->uploaded) {
                    // Define los parametros de salida
                    $imagen->file_new_name_body = $imagen_final;
                    $imagen->image_resize = true;
                    $imagen->image_ratio_crop = true;

                    // Procesa la imagen
                    @$imagen->process($ruta);
                }
                if($coordenadas == ''){
                    $cliente = array(
                        'cliente' => $nombres,
                        'imagen' => $imagen_final . '.' . $extension,
                        'nombre_factura' => $nombres_factura,
                        'nit' => $ci,
                        'tipo' => $tipo,
                        'cliente_grupo_id'=> $id_grupo,
                        'direccion' => $direccion,
                        'descripcion' => $descripcion,
                        'telefono' => $telefono,
                        'estado' => 'si',
                        'ciudad_id'=>$ciudad_id,
                        'empleado_id' => $empleado_id,
                        'dia'=>$id_dia
                    );
                }else{
                    $cliente = array(
                        'cliente' => $nombres,
                        'imagen' => $imagen_final . '.' . $extension,
                        'nombre_factura' => $nombres_factura,
                        'nit' => $ci,
                        'tipo' => $tipo,
                        'cliente_grupo_id'=> $id_grupo,
                        'direccion' => $direccion,
                        'descripcion' => $descripcion,
                        'ubicacion' => $coordenadas,
                        'telefono' => $telefono,
                        'estado' => 'si',
                        'ciudad_id'=>$ciudad_id,
                        'empleado_id' => $empleado_id,
                        'dia'=>$id_dia
                    );
                }
                // $cliente=array_merge($cliente,$DatosU);
                $db->where('id_cliente',$id)->update('inv_clientes', $cliente);
            }else{
                if($coordenadas == ''){
                    $cliente = array(
                        'cliente' => $nombres,
                        'nombre_factura' => $nombres_factura,
                        'nit' => $ci,
                        'tipo' => $tipo,
                        'cliente_grupo_id'=> $id_grupo,
                        'direccion' => $direccion,
                        'descripcion' => $descripcion,
                        'telefono' => $telefono,
                        'estado' => 'si',
                        'ciudad_id'=>$ciudad_id,
                        'empleado_id' => $empleado_id,
                        'dia'=>$id_dia
                    );
                }else{
                    $cliente = array(
                        'cliente' => $nombres,
                        'nombre_factura' => $nombres_factura,
                        'nit' => $ci,
                        'tipo' => $tipo,
                        'cliente_grupo_id'=> $id_grupo,
                        'direccion' => $direccion,
                        'descripcion' => $descripcion,
                        'ubicacion' => $coordenadas,
                        'telefono' => $telefono,
                        'estado' => 'si',
                        'ciudad_id'=>$ciudad_id,
                        'empleado_id' => $empleado_id,
                        'dia'=>$id_dia
                    );
                }
                // $cliente=array_merge($cliente,$DatosU);
                $db->where('id_cliente',$id)->update('inv_clientes', $cliente);
            }
            echo json_encode(array('estado' => 's'));
        }else{

            $bus = $db->select('*')->from('inv_clientes')->where(array('cliente' => $nombres, 'nit' => $ci,'direccion'=>$direccion))->fetch_first();

            if($bus){
                echo json_encode(array('estado' => 'y'));
            }else{
                $departamento = $db->select('*')->from('inv_ciudades c')->join('inv_departamentos d','d.id_departamento = c.departamento_id')->where('c.id_ciudad',$ciudad_id)->fetch_first();
                $id_anterior = $db->select('*')->from('inv_clientes')->like('codigo',$departamento['abreviacion'])->order_by('id_cliente','desc')->limit(1)->fetch_first();
                $codigo = ($id_anterior['codigo'] != null) ? $id_anterior['codigo'] : 1;
                
                if($codigo != 1){
                    $codigo3 = explode('-' , $codigo);
                    $codigo1 = $codigo3[1] + 1;
                }else{
                    $codigo1 = 1;
                }
                
                if($codigo1 > 9){
                    $codigo2 = $departamento['abreviacion'].'-000'.$codigo1;
                }elseif($codigo1 > 99){
                    $codigo2 = $departamento['abreviacion'].'-00'.$codigo1;
                }elseif($codigo1 > 999){
                    $codigo2 = $departamento['abreviacion'].'-0'.$codigo1;
                }elseif($codigo1 > 9999){
                    $codigo2 = $departamento['abreviacion'].'-'.$codigo1;
                }else{
                    $codigo2 = $departamento['abreviacion'].'-0000'.$codigo1;
                }
                if(isset($_FILES['imagen'])){
                    $imagen = $_FILES['imagen'];

                    list($ancho, $alto) = getimagesize($imagen['tmp_name']);

                    $ancho = $ancho * $data['scale'];
                    $alto = $alto * $data['scale'];

                    // Define la extension de la imagen
                    $extension = 'jpg';

                    $imagen_final = md5(secret . random_string() . $nombres);

                    // Instancia la imagen
                    $imagen = new upload($imagen);

                    if ($imagen->uploaded) {
                        // Define los parametros de salida
                        $imagen->file_new_name_body = $imagen_final;
                        $imagen->image_resize = true;
                        $imagen->image_ratio_crop = true;

                        // Procesa la imagen
                        @$imagen->process($ruta);
                    }
                    $cliente = array(
                        'cliente' => $nombres,
                        'nombre_factura' => $nombres_factura,
                        'nit' => $ci,
                        'tipo' => $tipo,
                        'cliente_grupo_id'=> $id_grupo,
                        'direccion' => $direccion,
                        'descripcion' => $descripcion,
                        'ubicacion' => $coordenadas,
                        'imagen' => $imagen_final . '.' . $extension,
                        'telefono' => $telefono,
                        'estado' => 'si',
                        'codigo' => $codigo2,
                        'ciudad_id'=>$ciudad_id,
                        'fecha_creacion'=>date('Y-m-d H:i:s'),
                        'empleado_id' => $empleado_id,
                        'dia'=>$id_dia
                    );
                }else{
                    $cliente = array(
                        'cliente' => $nombres,
                        'nombre_factura' => $nombres_factura,
                        'nit' => $ci,
                        'tipo' => $tipo,
                        'cliente_grupo_id'=> $id_grupo,
                        'direccion' => $direccion,
                        'descripcion' => $descripcion,
                        'ubicacion' => $coordenadas,
                        'imagen' => '',
                        'telefono' => $telefono,
                        'estado' => 'si',
                        'ciudad_id'=>$ciudad_id,
                        'fecha_creacion'=>date('Y-m-d H:i:s'),
                        'empleado_id' => $empleado_id,
                        'dia'=>$id_dia
                    );
                }

                $id=$db->insert('inv_clientes', $cliente);
                // $Datos=$db->query("SELECT c.id_cliente,d.abreviacion
                //                 FROM inv_clientes AS c
                //                 LEFT JOIN inv_ciudades AS ci ON c.ciudad_id=ci.id_ciudad
                //                 LEFT JOIN inv_departamentos AS d ON ci.departamento_id=d.id_departamento
                //                 WHERE c.id_cliente='{$id}'")->fetch_first();
                // $codigo='';
                // $ceros=5-strlen($Datos['id_cliente']);
                // for($i=0;$i<$ceros;$i++):
                //     $codigo.='0';
                // endfor;
                // $codigo.=$Datos['id_cliente'].$Datos['abreviacion'];
                // $datos=['codigo'=>$codigo];
                // $condicion = array('id_cliente' => $id);
                // $db->where($condicion)->update('inv_clientes', $datos);

                echo json_encode(array('estado' => 's'));
            }
        }
		// Redirecciona a la pagina principal

	} else {
		// Error 401
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}
?>