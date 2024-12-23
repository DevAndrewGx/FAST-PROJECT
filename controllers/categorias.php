<?php

class Categorias extends SessionController
{


    private $user;

    function __construct()
    {
        parent::__construct();

        $this->user = $this->getDatosUsuarioSession();
        error_log('Categorias::construct -> Controlador categorias');
    }


    function render()
    {
        error_log('Categorias::render -> Carga la pagina principal de categorias');
        $this->view->render('admin/gestionCategorias', [
            'user' => $this->user
        ]);
    }

    // Funcion para crear una nueva categoria

    function crearCategoria()
    {
        // primero validamos si la data viene correctamente desde el formulario
        error_log('Categorias::createCategory -> Funcion para crear nuevas categorias');

        if (!$this->existPOST(['nombreCategoria', 'tipoCategoria'])) {
            error_log('Categorias::createCategoria -> Hay algun error en los parametros enviados en el formulario');

            // enviamos la respuesta al front para que muestre una alerta con el mensaje
            echo json_encode(['status' => false, 'message' => "Los datos que vienen del formulario estan vacios"]);
            return;
        }


        if ($this->user == NULL) {
            error_log('Users::createUser -> El usuario de la session esta vacio');
            // enviamos la respuesta al front para que muestre una alerta con el mensaje
            echo json_encode(['status' => false, 'message' => ErrorsMessages::ERROR_ADMIN_NEWDATAUSER]);
            return;
        }

        // si no entra a niguna validacion, significa que la data y el usuario estan correctos
        error_log('createCategory:: -> Es posible crear una nueva categoria');

        // creamos un nuevo objeto de categorias
        $categoriaObj = new CategoriasModel();
        // seteamos los valores que vienen del formulario
        $categoriaObj->setNombreCategoria($this->getPost('nombreCategoria'));
        $categoriaObj->setTipoCategoria($this->getPost('tipoCategoria'));
        // validamos si la consulta fue correcta



        // Si no existe la subcategoria insertamosla categoria sin ningun paso adicional mas
        if (!$categoriaObj->existCategory($this->getPost('nombreCategoria')) && !$this->existPOST(['subCategoriaNombre'])) {
            error_log('Categorias::createCategory -> No existe la subcategoria, se inserta data de la categoria');
            if ($categoriaObj->crearCategory()) {
                $idCategoria = $categoriaObj->getIdCategoria();
                $categoriaObj->setIdCategoria($idCategoria);
                error_log('Categorias::createCategory -> Se guardo la categoria exitosamente');
                echo json_encode(['status' => true, 'message' => "La categoria fue creada exitosamente! CASO 1"]);
                return;
            }
        } else if ($categoriaObj->existCategory($this->getPost("nombreCategoria")) && $this->existPOST(['subCategoriaNombre'])) {

            // error_log('Existe la categoria? '. $categoriaObj->existCategory($this->getPost("nombreCategoria")));
            // error_log('La subcategoria a insertar es: '. $this->existPOST(['subCategoriaNombre']));
            if (!$categoriaObj->existCategory($this->getPost("nombreCategoria"))) {
                return;
            } else {
                $categoriaObj->get($this->getPost('nombreCategoria'));

                $categoriaObj->setNombreSubCategoria($this->getPost('subCategoriaNombre'));
                // obtenemos los datos de la categoria con get para asginarlo al objeto y asi poder insertar la data correctamente

                // hacemos la inserción de la subcategoria con el id de la categoria para realizar la asociación
                if (!$categoriaObj->existSubCategory($this->getPost('subCategoriaNombre'))) {
                    if ($categoriaObj->crearSubCategory()) {
                        error_log('Categorias::createCategory -> Se guardo la categoria y subcategoria exitosamente');
                        echo json_encode(['status' => true, 'message' => "La categoria y subcategoria fueron creadas exitosamente! CASO 2"]);
                        return;
                    } else {
                        echo json_encode(['status' => false, 'message' => "No se pudo guardar la data correctamente, intentelo nuevamente"]);
                        return;
                    }
                } else {
                    echo json_encode(['status' => false, 'message' => "La subcategoria ya existe en el sistema, intentelo nuevamente"]);
                    return;
                }
                if ($categoriaObj->crearSubCategory()) {
                    error_log('Categorias::createCategory -> Se guardo la categoria y subcategoria exitosamente');
                    echo json_encode(['status' => true, 'message' => "La categoria y subcategoria fueron creadas exitosamente! CASO 2"]);
                    return;
                } else {
                    echo json_encode(['status' => false, 'message' => "La subcategoria ya existe, intentelo nuevamente"]);
                    return;
                }
            }

        } else if (!$categoriaObj->existCategory($this->getPost("nombreCategoria")) && $this->existPOST(['subCategoriaNombre'])) {
            // Si no existe la categoria aun, la crea con la subcategoria
            if ($categoriaObj->crearCategory()) {
                error_log('Categorias::createCategory -> Se guardó la categoria correctamente');
                $idCategoria = $categoriaObj->getIdCategoria();
                $categoriaObj->setIdCategoria($idCategoria);
                // hacemos la inserción de la subcategoria con el id de la categoria para realizar la asociación
                $categoriaObj->setNombreSubCategoria($this->getPost('subCategoriaNombre'));

                if ($categoriaObj->crearSubCategory()) {
                    echo json_encode(['status' => true, 'message' => "La categoria y subcategoria fueron creadas exitosamente CASO 3"]);
                    return;
                } else {
                    echo json_encode(['status' => true, 'message' => "No se guardo la data correctamente en subcategorias"]);
                    return;
                }
            }
        } else {
            echo json_encode(['status' => false, 'message' => "La categoria ya se encuentra registrada en el sistema intentelo nuevamente! CASO 4"]);
            return;
        }

    }


    // Funcion para mostrar recuperar los datos en datatables
    public function getCategories()
    {

        try {
            // Obtener los parámetros enviados por DataTables
            $draw = intval($_GET['draw']);
            $start = intval($_GET['start']);
            $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
            $search = $_GET['search']['value'];
            $orderColumnIndex = intval($_GET['order'][0]['column']);
            $orderDir = $_GET['order'][0]['dir'];
            $columns = $_GET['columns'];
            $orderColumnName = $columns[$orderColumnIndex]['data'];

            //creamos un objeto del modelocategorias

            $categoriaObj = new CategoriasModel();

            //Obtenemos los datos filtrados
            $categoriasData = $categoriaObj->cargarDatosCategorias($length, $start, $orderColumnIndex, $orderDir, $search, $orderColumnName);

            $totalFiltered = $categoriaObj->totalRegistrosFiltrados($search);

            $totalRecords = $categoriaObj->totalRegistros();

            $arrayDataCategories = json_decode(json_encode($categoriasData, JSON_UNESCAPED_UNICODE), true);
            // print_r($arrayDataCategories);
            // error_log("Array: ".print_r($categoriasData));

            // Iterar sobre el arreglo y agregar 'options' a cada usuario
            for ($i = 0; $i < count($arrayDataCategories); $i++) {
                $arrayDataCategories[$i]['checkmarks'] = '<label class="checkboxs"><input type="checkbox"><span class="checkmarks"></span></label>';
                $arrayDataCategories[$i]['options'] = '
                <a class="me-3 confirm-text" href="#" data-id="' . $arrayDataCategories[$i]['id_categoria'] . '"  data-id-s="' . $arrayDataCategories[$i]['id_sub_categoria'] . '" >
                    <img src="' . constant("URL") . '/public/imgs/icons/eye.svg" alt="eye">
                </a>
                <a class="me-3 botonActualizar" data-nombre="' . $arrayDataCategories[$i]['nombre_categoria'] . '" data-id="' . $arrayDataCategories[$i]['id_categoria'] . '"  data-id-s="' . $arrayDataCategories[$i]['id_sub_categoria'] . '" href="#">
                    <img src="' . constant("URL") . '/public/imgs/icons/edit.svg" alt="eye">
                </a>
                <a class="me-3 confirm-text botonEliminar" data-id="' . $arrayDataCategories[$i]['id_categoria'] . '"  data-id-s="' . $arrayDataCategories[$i]['id_sub_categoria'] . '" href="#">
                    <img src="' . constant("URL") . '/public/imgs/icons/trash.svg" alt="trash">
                </a>
            ';
            }

            // retornamos la data en un arreglo asociativo con la data filtrada y asociada
            $response = [
                "draw" => $draw,
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $totalFiltered,
                "data" => $arrayDataCategories,
                "status" => true
            ];
            // devolvemos la data y terminamos el proceso
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            die();
        } catch (Exception $e) {
            error_log('Categorias::getCategories -> Error en traer los datos - getCategories' . $e->getMessage());
        }
    }


    // funcion para trear solamente una categoria
    public function consultarCategoria()
    {
        $data = json_decode(file_get_contents("php://input"), true);


        // validamos si la data de la solicitud existe
        if (isset($data['nombre'])) {

            $idCategoria = $data['nombre'];

            // traemos la data de la categoria
            $res = $this->model->get($idCategoria);

            // decodificamos la data que viene del backend en un JSON
            $arrayData = json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), true);

            // verificamos si la data es correcta
            if ($arrayData) {

                // devolvemos un arreglo asociativo y devolvemos la data al front
                $response = [
                    "data" => $arrayData,
                    "status" => true,
                    "message" => "Se obtuvo la data correctamente"
                ];

                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                // terminamos el proceso
                die();
            } else {
                error_log("Categories::getCategory -> No se puedo obtener la categoria correctamente");
                echo json_encode(["status" => false, "message" => "No se pudo obtener la categoria"]);
                return false;
            }


        }
    }

    public function consultarSubCategoria()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Validamos si la data de la solicitud existe
        if (isset($data['idSubCategoria'])) {
            $idSubCategoria = $data['idSubCategoria'];

            // Traemos la data de la subcategoría y las categorías
            $res = $this->model->getSubCategory($idSubCategoria);

            // Verificamos si la data es correcta
            if ($res) {
                // Devolvemos un arreglo asociativo y la data al front
                $response = [
                    "data" => $res,  // Ya es un array, no es necesario json_decode
                    "status" => true,
                    "message" => "Se obtuvo la data correctamente"
                ];

                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                // Terminamos el proceso
                die();
            } else {
                error_log("Categories::getSubCategory -> No se pudo obtener la subCategoria correctamente");
                echo json_encode(["status" => false, "message" => "No se pudo obtener la subCategoria"]);
                return false;
            }
        }
    }


    // funcion para verificar y borrar usuarios
    function borrar()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        // Verificar si los datos fueron recibidos correctamente
        if (isset($data['id_categoria'])) {

            $idCategoria = $data['id_categoria'];
            $res = $this->model->borrar($idCategoria);
            if ($res) {
                error_log('Users::borrar -> Se eliminó la categoria correctamente');
                echo json_encode(['status' => true, 'message' => "La categoria fue eliminada exitosamente!"]);
                return true;
            } else {
                error_log('Categorias::borrar -> No se pudo eliminar la categoria, intente nuevamente');
                echo json_encode(['status' => false, 'message' => "No se pudo eliminar la categoria, intente nuevamente!"]);
                return false;
            }
        }
    }


    // funcion para eliminar una subcategoria de una categoria ya existente
    function borrarSubCategoria()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        // Verificar si los datos fueron recibidos correctamente
        if (isset($data['idSubCategoria'])) {

            $idSubCategoria = $data['idSubCategoria'];
            $res = $this->model->borrarSubCategoria($idSubCategoria);
            if ($res) {
                error_log('Users::borrarSubCategoria -> Se eliminó una subCategoria correctamente');
                echo json_encode(['status' => true, 'message' => "La subcategoria fue eliminada exitosamente!"]);
                return true;
            } else {
                error_log('Categorias::borrarSubCategoria -> No se pudo eliminar la subcategoria, intente nuevamente');
                echo json_encode(['status' => false, 'message' => "No se pudo eliminar la subcategoria, intente nuevamente!"]);
                return false;
            }
        }
    }


    // funcion para con la logica para actualizar la categoria

    public function actualizarCategoria()
    {
        error_log('Categorias::actualizarCategory -> Funcion para actualizar una categoria');

        // validamos que los datos que vienen del formulario no este vacios
        if (!$this->existPOST(['nombreCategoria', 'tipoCategoria', 'id_categoria'])) {

            error_log('Categorias::actualizarCategory -> Hay algunos parametros vacios enviados en el formulario');

            echo json_encode(['status' => false, 'message' => "Algunos datos enviados del formulario estan vacios"]);

            return;
        }
        // validamos que el usuario de la sesión no este vacio
        if ($this->user == NULL) {

            error_log('Categorias::actualizarCategory -> El usuario de la sesión esta vacio');

            echo json_encode(['status' => false, 'message' => "El usuario de la sesión intenlo nuevamente"]);
            return;
        }

        // si no entra a niguna validacion, significa que la data y el usuarioi estan correctos
        error_log('Categorias::actualizarCategory -> Es posible actualizar una categoria');

        $objCategory = new CategoriasModel();

        $objCategory->setNombreCategoria($this->getPost('nombreCategoria'));
        $objCategory->setTipoCategoria($this->getPost('tipoCategoria'));

        // ejecutamos la query para actualizar una categoria
        $res = $objCategory->actualizarCategory($this->getPost('id_categoria'));

        // validamos si la consulta se ejecuto correctamente

        if ($res) {
            error_log('Category::actualizarCategory -> Se actualizo la categoria correctamente');
            echo json_encode(['status' => true, 'message' => "La categoria fue actualizada exitosamente!"]);
            return;
        } else {
            error_log('Category::actualizarCategory -> Error en la consulta del Back');
            echo json_encode(['status' => false, 'message' => "Error 500, nose actualizo la data!"]);
            return;
        }
    }


    // funcion para actualizar la subcategoria

    public function actualizarSubCategoria()
    {
        error_log('Categorias::actualizarCategory -> Funcion para actualizar una subCategoria');

        // validamos que los datos que vienen del formulario no este vacios
        if (!$this->existPOST(['subCategoriaNombre', 'categoriaAsociada', 'idSubcategoria'])) {

            error_log('Categorias::actualizarSubCategory -> Hay algunos parametros vacios enviados en el formulario');

            echo json_encode(['status' => false, 'message' => "Algunos datos enviados del formulario estan vacios"]);

            return;
        }
        // validamos que el usuario de la sesión no este vacio
        if ($this->user == NULL) {

            error_log('Categorias::actualizarCategory -> El usuario de la sesión esta vacio');

            echo json_encode(['status' => false, 'message' => "El usuario de la sesión intenlo nuevamente"]);
            return;
        }

        // si no entra a niguna validacion, significa que la data y el usuarioi estan correctos
        error_log('Categorias::actualizarSubCategroy -> Es posible actualizar una subCategoria');

        $objCategory = new CategoriasModel();

        $objCategory->setNombreSubCategoria($this->getPost('subCategoriaNombre'));
        $objCategory->setIdCategoria($this->getPost('categoriaAsociada'));


        // ejecutamos la query para actualizar una categoria
        $res = $objCategory->actualizarSubCategory($this->getPost('idSubcategoria'));

        // validamos si la consulta se ejecuto correctamente
        if ($res) {
            error_log('Category::actualizarCategory -> Se actualizo la subCategoria correctamente');
            echo json_encode(['status' => true, 'message' => "La subCategoria fue actualizada exitosamente!"]);
            return;
        } else {
            error_log('Category::actualizarCategory -> Error en la consulta del Back');
            echo json_encode(['status' => false, 'message' => "Error 500, nose actualizo la data!"]);
            return;
        }
    }
}
