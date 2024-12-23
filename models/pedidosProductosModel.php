<?php 
    // Esta clase nos permitira realzar la relación entre pedidos y productos
    class PedidosProductosModel extends Model { 
        
        // creamos los atrbutos de la clase
        private $id_pedido;
        private $id_producto;
        private $cantidad;
        private $precio;
        private $notas_producto;
        private $estado_producto;


        // creamos el constructor para inicializar los atributos
        public function __construct()
        {

            parent::__construct();

            // inicializar los atributos
            $this->id_pedido = 0;
            $this->id_producto = 0;
            $this->cantidad = 0;
            $this->precio = 0;
            $this->notas_producto = 0;
            $this->estado_producto = "";
            
        }

        // esta funcion nos permitira insertar data en la tabla pedidosProductos
        public function crear() { 
            error_log("PedidosProductos::crear -> Crear un producto desde PedidosProductos");
            // utilizamos try catch para evalucar la consulta ya que vamos a interactuar con la bd
            try {
                $query = $this->prepare("INSERT INTO pedido_producto(id_pedido, id_producto, cantidad, precio, notas_producto, estado_producto) VALUES (:id_pedido, :id_producto, :cantidad, :precio, :notas_producto, :estado_producto)");

                // asignamos la data a los placeholder y ejecutamos la query
                $query->execute([
                    ":id_pedido" => $this->id_pedido,
                    ":id_producto" => $this->id_producto, 
                    ":cantidad" => $this->cantidad, 
                    ":precio" => $this->precio, 
                    ":notas_producto" => $this->notas_producto,
                    ":estado_producto" => $this->estado_producto
                ]);

                // retornamos true para salirnos de la funcion
                return true;
            }catch(PDOException $e) {
                error_log('PredidosProducotsModel::crear->PDOException' . $e);
                // salimos de la funcion
                return false;
            }
        }


        // esta funcion nos permitira actualizar dat en la tabla de pedidosProductos
        public function actualizar($id) { 
            error_log("PedidosProductos::actualizar -> actualizar un producto desde PedidosProducots");

             // utilizamos try catch para evalucar la consulta ya que vamos a interactuar con la bd
            try {
                $query = $this->prepare("UPDATE pedido_producto SET id_pedido = :id_pedido, id_producto = :id_producto, cantidad = :cantidad, precio = :precio, notas_producto = :notas_producto, estado_producto = :estado_producto WHERE id_pedido = :id");

                // asignamos la data a los placeholder y ejecutamos la query
                $query->execute([
                    ":id" => $id,
                    ":id_producto" => $this->id_producto, 
                    ":cantidad" => $this->cantidad, 
                    ":precio" => $this->precio, 
                    ":notas_producto" => $this->notas_producto,
                    ":estado_producto" => $this->estado_producto
                ]);

                // retornamos true para salirnos de la funcion
                return true;
            }catch(PDOException $e) {
                error_log('PredidosProducotsModel::crear->PDOException' . $e);
                // salimos de la funcion
                return false;
            }
        }


        // getters y setters

        public function getIdPedido() { return $this->id_pedido;}
        public function getIdProducto() { return $this->id_producto;}
        public function getCantidad() { return $this->cantidad;}
        public function getPrecio() { return $this->precio;}
        public function getNotasProducto() { return $this->notas_producto;}
        public function getEstadoProducto() { return $this->estado_producto;}

        public function setIdPedido($idPedido) { $this->id_pedido = $idPedido;}
        public function setIdProducto($idProducto) { return $this->id_producto = $idProducto;}
        public function setCantidad($cantidad) { return $this->cantidad = $cantidad;}
        public function setPrecio($precio) { return $this->precio = $precio;}
        public function setNotasProducto($notas) { return $this->notas_producto = $notas;}
        public function setEstadoProducto($estado_producto) { return $this->estado_producto = $estado_producto;}
    }
?>