<?php
    if(is_post()):
        if(isset($_POST['id_egreso'])&&isset($_POST['descripcion'])):
            $id_egreso=escape(trim($_POST['id_egreso']));
            $descripcion=escape(trim($_POST['descripcion']));
            $id_producto=escape(trim($_POST['id_producto']));
            $cantidad=escape(trim($_POST['cantidad']));
            $unidad=escape(trim($_POST['unidad']));
            $precio=escape(trim($_POST['precio']));

            $id_producto=explode(',',$id_producto);
            $cantidad=explode(',',$cantidad);
            $unidad=explode(',',$unidad);
            $precio=explode(',',$precio);

            $IdUsuario=$_SESSION[user]['id_user'];
            $IdEmpleado=$db->query("SELECT persona_id FROM sys_users WHERE id_user='{$IdUsuario}'")->fetch_first()['persona_id'];

            $total=0;
            for($i=0;$i<count($id_producto);++$i)
                $total=$total+($precio[$i]*$cantidad[$i]);
            //ACTUALIZAR EGRESO
            $Datos=[
                    'descripcion'=>$descripcion,
                    'monto_total'=>$total,
                    'empleado_id'=>$IdEmpleado,
                ];
            $Condicion=[
                    'id_egreso'=>$id_egreso,
                ];
            $db->where($Condicion)->update('inv_egresos',$Datos);
            //ACTUALIZAR DATOS
            $Condicion=[
                'egreso_id'=>$id_egreso,
            ];
            $db->delete()->from('inv_egresos_detalles')->where($Condicion)->execute();
            for($i=0;$i<count($id_producto);++$i):
                $id_unidad=$db->query("SELECT id_unidad FROM inv_unidades WHERE unidad='{$unidad[$i]}'")->fetch_first()['id_unidad'];
                $Datos=[
                        'precio'=>$precio[$i],
                        'unidad_id'=>$id_unidad,
                        'cantidad'=>$cantidad[$i],
                        'descuento'=>0,
                        'producto_id'=>$id_producto[$i],
                        'egreso_id'=>$id_egreso,
                        'promocion_id'=>0,
                        'asignacion_id'=>0,
                        'lote'=>'',
                    ];
                $db->insert('inv_egresos_detalles',$Datos);
            endfor;
            echo json_encode([
                    'ok'=>true,
                    'message'=>[
                        'title'=>'Exitoso',
                        'message'=>'Actualización Realizada Exitosamente',
                        'type'=>'success',
                    ]
                ]);
        else:
            require_once bad_request();
		    die;
        endif;
    else:
        require_once not_found();
	    die;
    endif;