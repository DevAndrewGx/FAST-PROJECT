<?php
    // Esta clase nos permitira crear la logica para la gestion de los pedidos y demasf
    class Pedidos extends SessionController {

        private $user;

        function __construct()
        {
            // llamamos al constructor del padre
            parent::__construct();
            // asingamos la data del usuario logeado
            $this->user = $this->getDatosUsuarioSession();
        }

        

        // creamos la funcion para crear un nuevo pedido
        function crearPedido() {
            // guardamos la fecha y la hora en formato ya que la vamos a necesitar en tipo datee para interactuar con ella en la bd
            date_default_timezone_set('America/Bogota');
            // decodificamos la data que viene del formulario para manipularla en el contralador
            $pedido = json_decode($_POST['pedido'], true);

            // Verifica que los campos clave existan en el array $pedido
            if (!$this->existKeys($pedido, ['codigoPedido',
                'fechaHora',
                'numeroMesa',
                'idMesero',
                'numeroPersonas',
                'notasPedido',
                'total'
            ])) {
                echo json_encode(['status' => false, 'message' => "Faltan datos obligatorios en el pedido."]);
                return;
            }

            if ($this->user == NULL) {
                error_log('Pedidos::crearPedio -> El usuario de la sesion esta vacio');
                // enviamos la respuesta al front para que muestre una alerta con el mensaje
                echo json_encode(['status' => false, 'message' => ErrorsMessages::ERROR_ADMIN_NEWDATAUSER]);
                return;
            }

            // si no entra a niguna validacion, significa que la data y el usuario estan correctos
            error_log('Pedidos::CrearPedido -> Es posible crear un nuevo pedido');

            $pedidoObj = new PedidosModel();


            $pedidoObj->setCodigoPedido($pedido["codigoPedido"]);
            $pedidoObj->setIdMesa($pedido["numeroMesa"]);
            $pedidoObj->setIdMesero($pedido["idMesero"]);
            $pedidoObj->setPersonas($pedido["numeroPersonas"]);
            $pedidoObj->setNotasPedido($pedido["notasPedido"]);
            $pedidoObj->setEstadoPedido("PENDIENTE");
            $pedidoObj->setFechaHora($pedido["fechaHora"]);
            $pedidoObj->setFecha(date('Y-m-d H:i:s'));
            $pedidoObj->setTotal($pedido["total"]);

            // creamos un objeto de mesas ya que necesitamos cambiar el estado de la mesa cuando se ocupa
            $mesaObj = new MesasModel();
            // guardamos el objeto que retorna al momento de la consulta para devolverlo a la vista.
            $mesaObj->consultar($pedido['numeroMesa']);
            $mesaObj->setEstado("EN VENTA");
            error_log("Pedidos::CrearPedido -> numero mesa -> ".$pedido['numeroMesa']);
            // actualizamos el estado de la mesa cuando se crea un pedido para no mostrarla en la vista del mesero
    
            if($mesaObj->actualizarEstado($pedido['numeroMesa'])) {
                error_log("Pedidos::crearPedio -> Se actualizo el estado de la mesa correctamente");
                // ejecutamos la consulta y guardamos el id del pedido insertado en una variable 
                if ($idPedido = $pedidoObj->crear()) {

                    // cuando creamos un nuevo pedido se debe guardar la data de ese pedido en la tabla ventas
                    $ventaObj = new VentasModel();
                    // seteamos la data en el objeto de ventas para poder insertala en la tabla
                    $ventaObj->setIdPedido($idPedido);
                    $ventaObj->setTotal($pedido["total"]);
                    
                    $ventaObj->setFecha(date('Y-m-d H:i:s'));
                    // ponemos el estado de la venta en pendiente ya que aun no ha sido pagada
                    $ventaObj->setEstado("PENDIENTE");
                    
                    // ejecutamos la consutal para guardar una venta en la tabla ventas cada vez que se crea un pedido en la tabla pedidos
                    if($ventaObj->crear()) {
                        error_log('Pedidos::crearPedido -> Se guardo la data correctamente en la tabla ventas que emocionnnhn');
                    }else {
                        error_log('Pedidos::crearPedido -> Se guardo la data no correctamente en la tabla ventas ashhhhhhhhhhhhhh');
                    }
                    error_log('Pedidos::crearPedido -> Se creo el pedido correctamente');
                    // despues de realizar la validación ejecutamos un for para recorrer los productos del pedido y insertarlos en la bd
                    // creamos un array para guardar los productos del pedido
                    $productos = $pedido['pedidoProductos'];
                    foreach ($productos as $producto) {
                        $this->guardarProductoPedido($idPedido, $producto['id_producto'], $producto['cantidad'], $producto['precio'], $producto['notas_producto']);
                    }
                    error_log('Pedidos::crearPedido -> Se guardo el producto correctamente en la bd');
                    echo json_encode(['status' => true, 'message' => "Pedido creado Exitosamente!"]);
                    return;
                } else {
                    error_log('Pedidos::crearPedido -> No se guardo el pedido, hay algo raro en la consulta bro');
                    echo json_encode(['status' => false, 'message' => "Intentelo nuevamente, error 500!"]);
                    return;
                }
            } 
        }


        // creamos la funcion para actualizar un pedido

        function actualizarPedido() { 
            // decodificamos la data que viene del formulario para manipularla en el contralador
            $pedido = json_decode($_POST['pedido'], true);

            // Verifica que los campos clave existan en el array $pedido
            if (!$this->existKeys($pedido, ['codigoPedido','notasPedido','numeroMesaAntigua','numeroPersonas','notasPedido','total'])) {
                echo json_encode(['status' => false, 'message' => "Faltan datos obligatorios en el pedido."]);
                return;
            }

            if ($this->user == NULL) {
                error_log('Pedidos::crearPedio -> El usuario de la sesion esta vacio');
                // enviamos la respuesta al front para que muestre una alerta con el mensaje
                echo json_encode(['status' => false, 'message' => ErrorsMessages::ERROR_ADMIN_NEWDATAUSER]);
                return;
            }


             // si no entra a niguna validacion, significa que la data y el usuario estan correctos
            error_log('Pedidos::actualizarPedido -> Es posible actualizar un pedido');

            $pedidoObj = new PedidosModel();
            // asignamos los valores a los atributos del arreglo
            $pedidoObj->setCodigoPedido($pedido["codigoPedido"]);
            $pedidoObj->setIdMesero($pedido["idMesero"]);
            $pedidoObj->setPersonas($pedido["numeroPersonas"]);
            $pedidoObj->setNotasPedido($pedido["notasPedido"]);
            $pedidoObj->setEstadoPedido("PENDIENTE");
            $pedidoObj->setTotal($pedido["total"]);

            // hacemos una validacion de mesas ya que necesitamos desacernos de la mesa antigua y tomar una nueva mesa
            if($pedido["numeroMesaAntigua"] !== null && $pedido["numeroMesa"]  === null) {
                error_log("Pedidos::ActualizarPedido -> Caso 1 donde la mesa antigua aun existe y la mesa nueva no fue selccionada");
                $pedidoObj->setIdMesa($pedido["numeroMesaAntigua"]);

                 // creamos 2 objetos de tipo mesa ya que necesitamos cambiar el estado tanto de la mesa antigua como de la mesa nueva
                $mesaObj1 = new MesasModel();
                

            }

            if($pedido["numeroMesaAntigua"] !== null && $pedido["numeroMesa"] !== null) {

                error_log("Pedidos::ActualizarPedido -> Caso 2 donde la mesa nueva fue seleccionada por lo cual debemos eliminar la antigua");
                $pedidoObj->setIdMesa($pedido["numeroMesa"]);

                // creamos 2 objetos de tipo mesa ya que necesitamos cambiar el estado tanto de la mesa antigua como de la mesa nueva
                $mesaObj2 = new MesasModel();

                // 1. primero tenemos que consultar la mesa y traer su data
                $mesaObj2->consultar($pedido["numeroMesaAntigua"]);

                // 2. debemos setear el nuevo estado de la mesa ya que no va ser mas parte del pedido
                $mesaObj2->setEstado("DISPONIBLE");

                // 3. ahora debemos actualizar el estado de la mesa
                if($mesaObj2->actualizarEstado($pedido["numeroMesaAntigua"])) {
                    error_log("Pedidos::ActualizarPedido -> Se actualizo la mesa antgua y se seteo la nueva mesa, asi que es posible actualizar un producto correctamete");

                    $mesaObj1 = new MesasModel();
                    // 1. primero tenemos que consultar la mesa y traer su data
                    $mesaObj1->consultar($pedido["numeroMesa"]);

                    // 2. debemos setear el nuevo estado de la mesa ya que no va ser mas parte del pedido
                    $mesaObj1->setEstado("EN VENTA");

                    if($mesaObj1->actualizarEstado($pedido["numeroMesa"])) {
                        // ahora podemos actualizar un pedido con el nuevo numero de mesa
                        if ($pedidoObj->actualizar($pedido["codigoPedido"])) {
                            $idPedido = $pedido["idPedido"];
                            error_log('Pedidos::actualizarPedido -> Se actualzo el pedido correctamente');
                            $productos = $pedido['pedidoProductos'];
                            foreach ($productos as $producto) {
                                $this->actualizarProductoPedido($idPedido, $producto['id_producto'], $producto['cantidad'], $producto['precio'], $producto['notas_producto'], $producto["estados_productos"]);
                            }

                            error_log('Pedidos::actualizarPedido -> Se guardo actualizo correctamente en la bd');
                            echo json_encode(['status' => true, 'message' => "Pedido actualizado Exitosamente!"]);
                            return;
                        } else {
                            error_log('Pedidos::actualizarPedido -> No se actualizo el pedido, hay algo raro en la consulta bro');
                            echo json_encode(['status' => false, 'message' => "Intentelo nuevamente, error 500!"]);
                            return;
                        }
                    }   

                   

                }else {
                    error_log("Pedidos::ActualizarPedido -> No se pudo actualizar nada, tienes que revisar bien jeje");
                }
            } 

        }

        // esta funcion nos permitira actualizar los productos de los pedidos
        function actualizarProductoPedido($pedidoId, $productoId, $cantidad, $precio, $notas, $estado) {
            // validamos que la data que venga del formulario exista
            error_log('Pedidos::crearPedido -> Funcion para crear nuevos pedidos');

            if (!isset($pedidoId) || !isset($productoId) || !isset($cantidad) || !isset($precio) || !isset($notas) || !isset($estado)) {
                error_log('Pedidos::crearPedido -> Hay algun error en los parametros recibiidos');

                // enviamos la respuesta al front para que muestre una alerta con el mensaje
                echo json_encode(['status' => false, 'message' => "Los datos que vienen del formulario estan vacios"]);
                return;
            }

            // creamos un nuevo objeto de pedidosProductos
            $productosPedidoObj = new PedidosProductosModel();

            // asignamos los datos al objeto
            $productosPedidoObj->setIdPedido($pedidoId);
            $productosPedidoObj->setIdProducto($productoId);
            $productosPedidoObj->setCantidad($cantidad);
            $productosPedidoObj->setPrecio($precio);
            $productosPedidoObj->setNotasProducto($notas);
            $productosPedidoObj->setEstadoProducto($estado ?? "PENDIENTE");

            if ($productosPedidoObj->actualizar($pedidoId)) {
                error_log('Pedidos::actualizar -> se ACTUALIZO el producto en la tabla pedidosProductos OMGG!!!!!!');
                return true;
            } else {
                error_log('Pedidos::actualizar -> No se ACTUALIZO el producto en la tabla pedidosProductos OMGG!!!!!!');
                return false;
            }
        }

        // creamos una funcion aparte para guardar la data relacionada de productos y pedidos
        function guardarProductoPedido($pedidoId, $productoId, $cantidad, $precio, $notas) {
            // validamos que la data que venga del formulario exista
            error_log('Pedidos::crearPedido -> Funcion para crear nuevos pedidos');

            if (!isset($pedidoId) || !isset($productoId) || !isset($cantidad) || !isset($precio) || !isset($notas)) {
                error_log('Pedidos::crearPedido -> Hay algun error en los parametros recibiidos');

                // enviamos la respuesta al front para que muestre una alerta con el mensaje
                echo json_encode(['status' => false, 'message' => "Los datos que vienen del formulario estan vacios"]);
            return;
            }

            // creamos un nuevo objeto de pedidosProductos
            $productosPedidoObj = new PedidosProductosModel();

            // asignamos los datos al objeto
            $productosPedidoObj->setIdPedido($pedidoId);
            $productosPedidoObj->setIdProducto($productoId);
            $productosPedidoObj->setCantidad($cantidad);
            $productosPedidoObj->setPrecio($precio);
            $productosPedidoObj->setNotasProducto($notas);
            $productosPedidoObj->setEstadoProducto("PENDIENTE");

            if($productosPedidoObj->crear()) {
                error_log('Pedidos::crearPedido -> se guardo el producto en la tabla pedidosProductos OMGG!!!!!!');
                return true;
            }else {
                error_log('Pedidos::crearPedido -> No se guardo el producto en la tabla pedidosProductos OMGG!!!!!!');
                return false;
            }
        }

        // creamos esta funcion para consultar todos los productos
        function cargarDatosPedidos() { 
            // utilizamos para capturar cualquier exepcion y no parar la ejecución del codigo
            try {
                // Obtener los parámetros enviados por DataTables
                $draw = intval($_GET['draw']);
                $start = intval($_GET['start']);
                $length = intval($_GET['length']);
                $search = $_GET['search']['value'];
                $orderColumnIndex = intval($_GET['order'][0]['column']);
                $orderDir = $_GET['order'][0]['dir'];
                $columns = $_GET['columns'];
                $orderColumnName = $columns[$orderColumnIndex]['data'];

                // creamos un objeto del model pedidosJoinModel ya que necesitamos traer la data de ahi porque nos permitir hacer un JOIN con las 
                // tablas principales que conforman a pedidos

                $pedidoObj = new PedidosJoinModel();

                // $obtenemos los datos del modelo para mosrtarlos en el datatable
                $pedidosData = $pedidoObj->cargarDatosPedidos($length, $start, $orderColumnIndex, $orderDir, $search, $orderColumnName);
                // obtenemos el total de los producto filtrados para devolverselo al datatable para mostrarlo
                $totalRegistroFiltrados = $pedidoObj->totalRegistrosFiltrados($search);
                
                $totalRegistros = $pedidoObj->totalRegistros();


                $arrayDataPedidos = json_decode(json_encode($pedidosData, JSON_UNESCAPED_UNICODE), true);

                // Iterar sobre el arreglo y agregar 'options' a cada usuario
                for ($i = 0; $i < count($arrayDataPedidos); $i++) {
                    $arrayDataPedidos[$i]['checkmarks'] = '<label class="checkboxs"><input type="checkbox"><span class="checkmarks"></span></label>';
                    $arrayDataPedidos[$i]['options'] = '
                    <a class="me-3 visualizar-pedido-eye" href="#" data-id="' . $arrayDataPedidos[$i]['id_pedido'] . '" data-codigo="'. $arrayDataPedidos[$i]['codigoPedido'].'" >
                        <img src="' . constant("URL") . '/public/imgs/icons/eye.svg" alt="eye">
                    </a>
                    <a class="me-3 botonActualizar" href="#" data-id="' . $arrayDataPedidos[$i]['id_pedido'] . '" data-codigoPedido = "'. $arrayDataPedidos[$i]['codigo_pedido'].'">
                        <img src="' . constant("URL") . '/public/imgs/icons/edit.svg" alt="eye">
                    </a>
                    <a class="me-3 confirm-text botonEliminar" href="#" data-id="' . $arrayDataPedidos[$i]['id_pedido'] . '" data-idMesa="' . $arrayDataPedidos[$i]['id_mesa'] . '" data-codigoPedido = "' . $arrayDataPedidos[$i]['codigo_pedido'] . '">
                        <img src="' . constant("URL") . '/public/imgs/icons/trash.svg" alt="trash">
                    </a>
                ';
                }

                // retornamos la data en un arreglo asociativo conconsultarPedido la data filtrada y asociada
                $response = [
                    "draw" => $draw,
                    "recordsTotal" => $totalRegistros,
                    "recordsFiltered" =>$totalRegistroFiltrados,
                    "data" => $arrayDataPedidos,
                    "status" => true
                ];
                // devolvemos la data y terminamos el proceso
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }catch(Exception $e) { 
                error_log("Pedidos::cargarDatosPedidos -> Error en trear los datos - cargarDatosPedidos ".$e->getMessage());
            }
        }


        


        // esta funcion nos permitira consultar un producto en especifico
        function consultarPedido() {
            // validamos si existe el id enviado desde la petición
            if (!$this->existPOST(['codigoPedido'])) {
                error_log('Pedidos::consultarPedido -> No se obtuvo el codigo del pedido correctamente');
                echo json_encode(['status' => false, 'message' => "Algunos parametros enviados estan vacios, intente nuevamente!"]);
                return false;
            }

            if ($this->user == NULL) {
                error_log('Pedidos::consultarPedido -> El usuario de la session esta vacio');
                // enviamos la respuesta al front para que muestre una alerta con el mensaje
                echo json_encode(['status' => false, 'message' => ErrorsMessages::ERROR_ADMIN_NEWDATAUSER]);
                return;
            }
            // creamos un nuevo objeto de productos
            $pedidoObj = new PedidosJoinModel();
            $res = $pedidoObj->consultar($this->getPost('codigoPedido'));

            $arrayData =  json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), true);

            if ($arrayData) {
                $response = [
                    "data" => $arrayData,
                    "status" => true,
                    "message" => "Se obtuvo la data correctamente"
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            } else {
                error_log('Users::borrarUser -> No se pudo obtener el pedido correctamente');
                echo json_encode(['status' => false, 'message' => "No se pudo obtener el pedido!"]);
                return false;
            }
        }

        // funcion para traer la data de un producto en un pedido
        function consultarProductoPedido() {
            // validamos si existe el id enviado desde la petición
            if (!$this->existPOST(['id_producto'])) {
                error_log('Pedidos::consultarProducto -> No se obtuvo el id del producto correctamente');
                echo json_encode(['status' => false, 'message' => "Algunos parametros enviados estan vacios, intente nuevamente!"]);
                return false;
            }

            if ($this->user == NULL) {
                error_log('Pedidos::consultarProductos -> El usuario de la session esta vacio');
                // enviamos la respuesta al front para que muestre una alerta con el mensaje
                echo json_encode(['status' => false, 'message' => ErrorsMessages::ERROR_ADMIN_NEWDATAUSER]);
                return;
            }
            // creamos un nuevo objeto de productos
            $productoObj = new ProductosJoinModel();
            $res = $productoObj->consultar($this->getPost('id_producto'));

            $arrayData =  json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), true);

            if ($arrayData) {
                error_log('Pedidos::consultarProductos -> El producto se obtuvo correctamente-> ' . $res);
                $response = [
                    "data" => $arrayData,
                    "status" => true,
                    "message" => "Se obtuvo la data correctamente"
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            } else {
                error_log('Users::borrarUser -> No se pudo obtener el Producto correctamente');
                echo json_encode(['status' => false, 'message' => "No se pudo obtener el Producto!"]);
                return false;
            }
        }
        // funcion para borrar un pedido
        function borrarPedido() {
            // validamos si el id enviado desde la peticion existe
            if (!$this->existPOST(['idPedido'])) {
                error_log('Pedidos::borrarPedido -> No se obtuvo el id de la pedido correctamente');
                echo json_encode(['status' => false, 'message' => "No se pudo eliminar el pedido, intente nuevamente!"]);
                return false;
            }

            if ($this->user == NULL) {
                error_log('Pedidos::borrarPedido  -> El usuario de la session esta vacio');
                // enviamos la respuesta al front para que muestre una alerta con el mensaje
                echo json_encode(['status' => false, 'message' => "El usuario de la sessión esta vacio"]);
                return;
            }

            // necestiamos actualizar primero estado ya que nesecitamos cambiar el estado de una mesa cuando ya esta disponible
            $mesaObj = new MesasModel();
            $mesaObj->setEstado("DISPONIBLE");
            $mesaRes = $mesaObj->actualizarEstado($this->getPost('idMesa'));

            // validamos que la data de la mesa se haya actualizado
            if($mesaRes) {
                error_log("Pedidos::borrarPedido -> Se actualizo el estado de la mesa correctamente");
            }else {
                error_log("Pedidos::borrarPedido -> No se actualizo el estado de la mesa correctamente :( ");
            }
            $pedidoObj = new PedidosModel();
            // guardamos el resultado de la consuta en una variable
            $res = $pedidoObj->borrar($this->getPost('idPedido'));

            if ($res) {
                error_log("Pedidos::borrarPedido -> Se elimino un pedido correctamente");
                echo json_encode(['status' => true, 'message' => "El pedido fue eliminado exitosamente!"]);
                return true;
            } else {
                error_log('Pedidos::borrarPedido  -> No se pudo eliminar el pedido, intente nuevamente');
                echo json_encode(['status' => false, 'message' => "No se pudo eliminar el pedido, intente nuevamente!"]);
                return false;
            }
        }


        
  

        // function para realizar el filtro para consultar los productos asociados a una categoria
        function getProductsByCategory()
        {
            // Verificamos si existe el POST 'categoria'
            if ($this->existPOST('categoria')) {
                error_log('categoria' . $this->getPost('categoria'));
                // Creamos un nuevo objeto de la clase CategoriasModel
                $productoObj = new ProductosModel();

                // Obtenemos los productos relacionadas a la categoría recibida
                $productos = $productoObj->getProductsByCategory($this->getPost('categoria'));
                // Devolvemos el resultado en formato JSON
                echo json_encode(["data" => $productos]);
                return;
            } else {
                // Devolvemos una respuesta en caso de que no exista 'categoria' en el POST
                echo json_encode(['error' => 'Categoría no proporcionada']);
                return;
            }
        }

        // funcion para la creación de codigos dinamicos para los pedidos
        function crearCodigoPedido() {

            //    validamos que sea metodo GET y crearCodigoPedido
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {

                //Llamamos al metodo data para crear la fecha en la que se va crear el pedido, ademas establecemos la zona horaria porque el servidor esta  configurado de otra forma, la cual no no muestra la hora que es.

                date_default_timezone_set('Etc/GMT+5');
                $fechaHora = date('d/m/Y h:i:s A'); 

                // creamos un objeto de pedidos
                $pedidoObj = new PedidosModel();
                $codigo = $pedidoObj->generarCodigoPedido();
                // devolvemos el codigo generado en formato JSON para utilizarlo y mostrarlo en el frontend
                echo json_encode(['codigo' => $codigo, 'fecha'=>$fechaHora]);
            }
        }
    }
?>