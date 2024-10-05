$(document).ready(function () {
    const baseUrl = $('meta[name="base-url"]').attr("content");
    const submitButton = $(this).find('button[type="submit"]');

    let globalIdCategoria;
    let globalNombreCategoria;
    let endPoint;


    // creamos la variable para iniciar la datatable para mostrar los datos
    let dataTableCategorias = $("#data-categorias").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        pageLength: 10, // Muestra 10 registros por página
        language: {
            lengthMenu: "Mostrar _MENU_ Registros",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            infoEmpty:
                "Mostrando registros del 0 al 0 de un total de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            search: "Buscar:",
            processing: "Procesando...",
        },
        ajax: {
            url: baseUrl + "categorias/getCategories",
            type: "GET",
            dataType: "json",
            dataSrc: function (json) {
                // Crear un nuevo array para almacenar categorías únicas
                let uniqueCategories = [];
                // Usar un objeto temporal para verificar duplicados
                let temp = {};

                json.data.forEach(function (item) {
                    // Usar el nombre de la categoría como clave para verificar duplicados
                    if (!temp[item.nombre_categoria]) {
                        temp[item.nombre_categoria] = true;
                        uniqueCategories.push(item);
                    }
                });

                // Devolver las categorías únicas
                return uniqueCategories;
            },
        },
        columns: [
            { data: "checkmarks" },
            { data: "nombre_categoria" },
            { data: "tipo_categoria" },
            { data: "options" },
        ],
        order: [[1, "asc"]], // Ordenar por la columna nombre_categoria (segunda columna, índice 1)
        columnDefs: [
            {
                targets: [0, 3],
                orderable: false,
            },
        ],
    });
   let dataTableSubcategorias = $("#data-categorias-subcategorias").DataTable({
       responsive: true,
       processing: true,
       serverSide: true,
       pageLength: 10, // Muestra 10 registros por página
       language: {
           lengthMenu: "Mostrar _MENU_ Registros",
           zeroRecords: "No se encontraron subcategorias asociadas",
           info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
           infoEmpty:
               "Mostrando registros del 0 al 0 de un total de 0 registros",
           infoFiltered: "(filtrado de un total de _MAX_ registros)",
           search: "Buscar:",
           processing: "Procesando...",
       },
       ajax: {
           url: baseUrl + "categorias/getCategories",
           type: "GET",
           dataType: "json",
           dataSrc: function (json) {
               // Filtrar las filas que tienen `nombre_subcategoria` null o vacío
               return json.data.filter(function (item) {
                   return (
                       item.nombre_subcategoria !== null &&
                       item.nombre_subcategoria !== ""
                   );
               });
           },
       },
       columns: [
           { data: "checkmarks" },
           { data: "nombre_subcategoria" },
           { data: "nombre_categoria" },
           { data: "options" },
       ],
       columnDefs: [
           {
               targets: [0, 3],
               orderable: false,
           },
       ],
   });

    //funciones para simplificar el codigo y la esctructura en general
    function showError(inputId, errorId, mostrar) { 
        
        if(mostrar) {
            $(`#${inputId}`).addClass('is-invalid');
            $(`#${errorId}`).show();
            
        }else { 
            $(`#${inputId}`).removeClass("is-invalid");
            $(`#${errorId}`).hide();
        }
    }

    function validateFormCategories() { 
        
        const categoryName = $("#nombreCategoria").val().trim();
        const categoryType = $("#tipoCategoria").val().trim();

        let isValid = true;

        showError("nombreCategoria", "categoryNameError", categoryName === '');
        showError("tipoCategoria", "typeCategoryNameError", categoryType === "");

        if (categoryName === '' || categoryType === '') { 
            isValid = false;
        }

        return isValid;
    }

    
    function validateFormSubCategories() { 

        const subcategoryName = $("#subCategoriaNombre").val().trim();

        let isValid = true;

        showError("subCateegoriaNombre", "subcategoryNameError", subcategoryName === '');

        if(subcategoryName === "") { 
            isValid = false;
        }

        return isValid;
    }

    function sendForm(form, formData, url, successMessage) { 

      
        $.ajax({
            url: url,
            type: "POST",
            processData: false,
            contentType: false,
            data: formData,
            success: function (response) {
                let data = JSON.parse(response);
                
                if (data.status) {
                    Swal.fire({
                        icon: "success",
                        title: successMessage,
                        text: data.message,
                        showConfirmButton: true,
                        allowOutsideClick: false,
                        confirmButtonText: "Ok"
                    }).then(function () {
                        $(form).closest(".modal").modal("hide");
                        form.reset();
                        dataTableCategorias.ajax.reload(null, false);
                        dataTableSubcategorias.ajax.reload(null, false);
                        // Habilitar el botón nuevamente
                        $(form).find('button[type="submit"]').prop("disabled", false);
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: data.message,
                        showConfirmButton: true,
                        allowOutsideClick: false,
                        confirmButtonText: "Ok"
                    }).then(function () { 
                        $(form).closest(".modal").modal("hide");
                        form.reset();

                        $(form).find('button[type="submit"]').prop("disabled", false);
                    });
                }
            }
        });
    }

$("#formCategories").on("submit", function(e) {
        e.preventDefault();
        // mostramos la opcion de subCategoria cuando se agregue una nueva categoria
        $("#subCategoryOption").show();

        // si no esta en modo editar, el endpoint creara una categoria
        endPoint = baseUrl + "categorias/createCategory";
        if (validateFormCategories()) {
            const form = this;
            const categoryFormData = new FormData(form);

            // verificamos si la clase cambia para hacer la validación de actualización
            let editar = $(".modal-header").hasClass("headerUpdate")
                ? true
                : false;
            console.log(editar);

            // agregamos el campo id de categoria para realizar la actualización y ademas se agrega la URL construida

            if (editar) {
                categoryFormData.append("id_categoria", globalIdCategoria);
                console.log(globalIdCategoria);
                endPoint = baseUrl + "categorias/updateCategory";
            }

            if ($("#hasSubcategory").is(":checked")) {

                $("#modalFormCategories").modal("hide");
                $("#subcategoryModal").modal("show");

                // Validación y envío del formulario de subcategoría
                $("#formSubcategory").off("submit").on("submit", function (e) {

                    e.preventDefault();
                    const submitSubcategoryButton = $(this).find('button[type="submit"]');
                    submitSubcategoryButton.prop("disabled", true);

                        if (validateFormSubCategories()) {
                            // Crear una nueva instancia de FormData para el formulario de subcategoría
                            let subcategoryFormData = new FormData(this); // 'this' hace referencia al formulario de subcategoría

                            // Agregar los datos de categoría a la FormData de subcategoría
                            subcategoryFormData.append(
                                "nombreCategoria",
                                $("#nombreCategoria").val().trim()
                            );
                            subcategoryFormData.append(
                                "tipoCategoria",
                                $("#tipoCategoria").val().trim()
                            );

                            sendForm(
                                this,
                                subcategoryFormData,
                                baseUrl + "categorias/createCategory",
                                "Categoría y subcategoría creadas"
                            );
                        } else {
                            submitSubcategoryButton.prop("disabled", false);
                        }
                });
            } else {
                // Envío solo de la categoría
                sendForm(
                    form,
                    categoryFormData,
                    endPoint,
                    editar ? " Categoría actualiza" : "Categoria creada"
                );
            }
        }
    });
    
    // funcion para eliminar una categoria
    $("#data-categorias").on("click", ".botonEliminar", function (e) {
        e.preventDefault();
        $(this).prop("disabled", true); // Deshabilitar el botón para que el usuario no di click como
        const id_categoria = $(this).data("id");
        Swal.fire({
            title: "¿Estás seguro?",
            text: "Esta acción no se puede deshacer",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
            allowOutsideClick: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + "categorias/delete",
                    type: "POST",
                    processData: false,
                    contentType: "application/json",
                    data: JSON.stringify({
                        id_categoria: id_categoria,
                    }),
                    success: function (response) {
                        let data = JSON.parse(response);
                        if (data.status) {
                            Swal.fire({
                                title: "Éxito",
                                text: data.message,
                                icon: "success",
                                allowOutsideClick: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    dataTableCategorias.ajax.reload(
                                        null,
                                        false
                                    );
                                    dataTableSubcategorias.ajax.reload(
                                        null,
                                        false
                                    );
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.message,
                                icon: "error",
                                allowOutsideClick: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    //  mantener el modal abierto para que el usuario intente de nuevo
                                }
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: "Error",
                            text: "Hubo un problema con la solicitud.",
                            icon: "error",
                            allowOutsideClick: false,
                            confirmButtonText: "Ok",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // mantener el modal abierto para que el usuario intente de nuevo
                            }
                        });
                    },
                });
            }
        });
    });


    // funcion para eliminar una subcategorias
    $("#data-categorias-subcategorias").on("click", ".botonEliminar", function(e) {
        console.log('its inside');
        // cancelamos el evento por default
        e.preventDefault();
        // creamos la variable para almacenar el id que viene del fomulario
        const idSubCategoria = $(this).data("id-s");
        console.log(idSubCategoria);
        // creamos la alerta para la confirmación del usuario
        Swal.fire({
            title: "¿Estás seguro?",
            text: "Esta acción no se puede deshacer",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
            allowOutsideClick: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + "categorias/deleteSubCategoria",
                    type: "POST",
                    processData: false,
                    contentType: "application/json",
                    data: JSON.stringify({
                        idSubCategoria: idSubCategoria,
                    }),
                    success: function (response) {
                        let data = JSON.parse(response);
                        if (data.status) {
                            Swal.fire({
                                title: "Éxito",
                                text: data.message,
                                icon: "success",
                                allowOutsideClick: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    dataTableCategorias.ajax.reload(
                                        null,
                                        false
                                    );
                                    dataTableSubcategorias.ajax.reload(
                                        null,
                                        false
                                    );
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.message,
                                icon: "error",
                                allowOutsideClick: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    //  mantener el modal abierto para que el usuario intente de nuevo
                                }
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: "Error",
                            text: "Hubo un problema con la solicitud.",
                            icon: "error",
                            allowOutsideClick: false,
                            confirmButtonText: "Ok",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // mantener el modal abierto para que el usuario intente de nuevo
                            }
                        });
                    },
                });
            }
        });
    });

    // funcion para actualizar subcategorias y categorias con el modo edición
    $("#data-categorias").on("click", ".botonActualizar", function(e) {
        // prevenimos el efecto por defecto
        e.preventDefault();

        // obtenemos el id de la categoria del formulario
        globalNombreCategoria = $(this).data("nombre");
        globalIdCategoria = $(this).data('id');
        console.log(globalNombreCategoria);

        // cambiamos la clase del title modal para actualizar data
        $("#titleModal").html("Actualizar categoria");
        $(".modal-header")
            .removeClass("headerRegister")
            .addClass("headerUpdate");
        $("#addCategory").text("Actualizar categoria");

        // ocultamos la opcion de subCategoria
        $("#subCategoryOption").hide();

        // Enviamos la peteción para traer la data de la categoria y mostrarla en el formulario

        $.ajax({
            url: baseUrl + "categorias/getCategory",
            type: "POST",
            dataType: "json",
            data: JSON.stringify({ nombre: globalNombreCategoria}),
            // respuesta del servidor
            success: function (response) {
                // validamos si la respuesta del servidor es correcta
                if (response.status) {
                    // verificamos que la data no este nulla
                    if (response.data) {
                        // tomamos los datos del arreglo para setearlos al formulario
                        var categoriaData = response.data;

                        $("#nombreCategoria").val(
                            categoriaData.nombre_categoria
                        );
                        $("#tipoCategoria").val(categoriaData.tipo_categoria);
                    } else {
                        console.log("Data isn't exist or is undefined");
                    }
                } else {
                    console.log("Data no exist");
                }
            },
            error: function (response) {},
            complete: function () {
                $("#modalFormCategories").modal("show");
            },
        });
    });


    // Resetear los formularios al cerrar los modales para evitar que queden datos en ellos Y quede en modo edición
    $("#modalFormCategories, #subcategoryModal").off('hiddden.bs.modal').on('hidden.bs.modal', function() {
        $(this).find(".is-invalid").removeClass("is-invalid");
        $(this).find(".invalid-feedback").hide();
        $(".modal-header").removeClass("headerUpdate");
        $(".modal-header").addClass("headerRegister");
        // mostramos la opcion de subCategoria porque no estamos en edición
        $("#subCategoryOption").show();
    });

});
