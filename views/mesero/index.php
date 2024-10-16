<?php
$user = $this->d['user'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo constant('URL'); ?>">
    <title>FAST | DASHBOARD</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables styles -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="<?php echo constant('URL'); ?>public/css/styles.css">
</head>

<body>
    <!-- ASIDE CONTAINER -->
    <div class="main-wrapper">
        <?php require_once('views/header.php') ?>
        <aside class="left-section">
            <div class="sidebar">
                <div class="logo">
                    <button class="menu-btn" id="menu-close"><i class='bx bx-log-out-circle'></i></button>
                    <a href="#"><img src="<?php echo constant('URL'); ?>public/imgs/LOGOf.png" alt="Logo"></a>
                    <i class='bx bxs-chevron-left-circle'></i>
                </div>
                <div class="item" id="ordenes">
                    <i class='bx bx-grid-alt'></i>
                    <a href="#">Ordenes</a>
                </div>
                <div class="item">
                    <i class='bx bx-cog'></i>
                    <a href="#">Settings</a>
                </div>
            </div>
            <div class="log-out sidebar">
                <div class="item">
                    <i class='bx bx-log-out'></i>
                    <a href="../../sing-up/login.html">Log-out</a>
                </div>
            </div>
        </aside>
        <!-- MAIN CONTENT -->
        <main class="page-wrapper" style="min-height: 995px;">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>Gestión de Pedidos</h1>
                        <nav class="nav-main">
                            <a href="homeAdmin.php">Admin</a>
                            <a href="adminUsu.php" id="actual" data-navegation="#ordenes"> / Gestión de Pedidos </a>
                        </nav>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Enviar Nuevo Pedido</h5>
                        <button class="btn btn-primary" id="openOrderForm" data-bs-toggle="modal" data-bs-target="#orderModal">Ingresar Orden</button>

                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Historial de Pedidos</h5>
                        <div class="table-responsive">
                            <table id="ordersTable" class="table table-responsive datanew">
                                <thead>
                                    <tr>
                                        <th class="sorting">Mesa</th>
                                        <th class="sorting">Mesero</th>
                                        <th class="sorting">Plato</th>
                                        <th class="sorting">Cantidad</th>
                                        <th class="sorting">Fecha</th>
                                        <th class="sorting">Estado</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>5</td>
                                        <td>Juan Pérez</td>
                                        <td>Pizza Margarita</td>
                                        <td>2</td>
                                        <td>19 Nov 2022</td>
                                        <td><span class="badges bg-lightgreen">Entregada</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Acción
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">Detalles del Pedido</a></li>
                                                    <li><a class="dropdown-item" href="#">Editar Pedido</a></li>
                                                    <li><a class="dropdown-item" href="#">Descargar PDF</a></li>
                                                    <li><a class="dropdown-item" href="#">Eliminar Pedido</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Nuevo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="orderForm">
                        <div class="mb-3">
                            <label for="mesas" class="form-label">Mesas</label>
                            <div class="table-responsive">
                                <table class="table" id="mesasTable">
                                    <thead>
                                        <tr>
                                            <th>Número de Mesa</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <div class="input-group mb-3">
                                <input type="number" class="form-control" id="mesaInput" min="1" placeholder="Número de mesa" aria-label="Número de mesa">
                                <button type="button" class="btn btn-outline-secondary" id="addMesa">Agregar Mesa</button>
                            </div>
                            <div id="mesasError" class="invalid-feedback" style="display:none;">Por favor, ingresa un número de mesa válido.</div>
                        </div>

                        <!-- Nueva sección para Categorías y Subcategorías -->
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoriaSelect">
                                <option value="" disabled selected>Seleccionar Categoría</option>
                                <?php
                                foreach ($categories as $cat) {
                                ?>
                                    <option value="<?php echo $cat->getIdCategoria() ?>"><?php echo $cat->getNombreCategoria() ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <label for="producto" class="form-label">Productos</label>
                        <select class="producto" id="productoSelect">
                            <option value="" disabled selected>Seleccionar Producto</option>



                        </select>

                        <!-- Elementos del pedido (agregados por el usuario) -->
                        <div class="mb-3">
                            <label for="elementos" class="form-label">Elementos del Pedido</label>
                            <div class="table-responsive">
                                <table class="table" id="elementosTable">
                                    <thead>
                                        <tr>
                                            <th>Nombre del Elemento</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unitario</th>
                                            <th>Subtotal</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <div class="input-group mb-3">
                                <select class="form-select" id="elementoSelect">
                                    <option value="" disabled selected>Seleccionar Elemento</option>

                                </select>
                                <input type="number" class="form-control" id="elementoCantidad" min="1" placeholder="Cantidad" aria-label="Cantidad">
                                <button type="button" class="btn btn-outline-secondary" id="addElemento">Agregar Elemento</button>
                            </div>
                            <div id="elementosError" class="invalid-feedback" style="display:none;">Por favor, selecciona un elemento y cantidad válidos.</div>
                        </div>

                        <div class="mb-3">
                            <label for="total" class="form-label">Total</label>
                            <input type="text" class="form-control" id="total" readonly>
                        </div>
                        <input type="hidden" id="fecha" name="fecha">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="submitOrder">Enviar Pedido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <!-- Popper.js para Bootstrap (necesario para los modales) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.7/umd/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/2.0.3/js/dataTables.min.js"></script>
    <!-- DataTables Bootstrap 5 integration -->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

    <!-- SWEETALERT2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script type="module" src="<?php echo constant('URL'); ?>public/js/alertas.js"></script>
    <!-- APP JS -->
    <script src="<?php echo constant('URL'); ?>public/js/app.js"></script>

</body>


</html>