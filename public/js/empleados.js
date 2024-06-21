$(document).ready(function () {
    const baseUrl = $('meta[name="base-url"]').attr("content");

    let dataTable = $("#data-empleados").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        pageLength: 10, // Muestra 10 registros por página
        language: {
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            infoEmpty:
                "Mostrando registros del 0 al 0 de un total de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            search: "Buscar:",
            processing: "Procesando...",
        },
        ajax: {
            url: baseUrl + "users/getUsers",
            type: "GET",
            dataType: "json",
        },
        columns: [
            { data: "checkmarks" },
            {
                data: null,
                render: function (data, type, row) {
                    return (
                        '<img src="' +
                        baseUrl +
                        "public/imgs/uploads/" +
                        row.foto +
                        '" alt="Foto" style="width:45px; height:45px; border-radius:50%;"> ' +
                        row.nombres
                    );
                },
            },
            { data: "apellidos" },
            { data: "documento" },
            { data: "telefono" },
            { data: "correo" },
            { data: "estado" },
            { data: "rol" },
            { data: "fechaCreacion" },
            { data: "options" },
        ],
        columnDefs: [
            {
                targets: [0, 9],
                orderable: false,
            },
        ],
    });

    // Vamos enviar la data con ajax para evitar que se refresque la pagina y asi poder mostrar una alerta
    $("#formUsuario").submit(function (e) {
        e.preventDefault();

        let form = $(this)[0]; // Selecciona el formulario como un elemento DOM
        const formData = new FormData(form);

        $.ajax({
            url: baseUrl + "users/createUser",
            type: "POST",
            processData: false,
            contentType: false,
            data: formData,
            success: function (response) {
                // convertimos la data a un JSON
                let data = JSON.parse(response);

                // verificamos que el status de la respuesta sea true o false
                if (data.status) {
                    Swal.fire({
                        title: "Éxito",
                        text: data.message,
                        icon: "success",
                        allowOutsideClick: false,
                        confirmButtonText: "Ok",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // ahora cerramos el modal si el resultado es confirmado
                            $("#formUsuario").closest(".modal").modal("hide");
                            // Limpiar el formulario
                            $("#formUsuario")[0].reset();
                            // Recargar la tabla
                            dataTable.ajax.reload(null, false);
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
                            // Cerrar el modal
                            $("#formUsuario").closest(".modal").modal("hide");
                            // Limpiar el formulario
                            $("#formUsuario")[0].reset();
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
                        // Cerrar el modal
                        $("#formUsuario").closest(".modal").modal("hide");
                        // Limpiar el formulario
                        $("#formUsuario")[0].reset();
                    }
                });
            },
        });
    });

    // function para borrar un usuario
    $("#data-empleados").on("click", ".botonEliminar", function (e) {
        e.preventDefault();
        const id_usuario = $(this).data("id");
        const id_foto = $(this).data("idfoto");
        console.log(id_usuario);
        console.log(id_foto);

        // const baseUrl = $('meta[name="base-url"]').attr("content");
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
                    url: baseUrl + "users/delete",
                    type: "POST",
                    processData: false,
                    contentType: "application/json",
                    data: JSON.stringify({ id_usuario: id_usuario, id_foto: id_foto }),
                    success: function (response) {
                        // convertimos la data a un JSON
                        let data = JSON.parse(response);

                        if (data.success) {
                            Swal.fire({
                                title: "Exito",
                                text: data.message,
                                icon: "success",
                                allowOutsideClick: false,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Cerrar el modal
                                    $("#formUsuario")
                                        .closest(".modal")
                                        .modal("hide");
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: data.message,
                                icon: "error",
                                allowOutsideClick: false,
                            });
                        }
                    },
                    error: function (response) {
                        // convertimos la data a un JSON
                        let data = JSON.parse(response);

                        Swal.fire({
                            title: "Error",
                            text: data.message,
                            icon: "error",
                            allowOutsideClick: false,
                        });
                    },
                });
            }
        });
    });

    // function para actualizar la data del usuario
    $("#data-empleados").on("click", ".botonActualizar", function (e) {
        e.preventDefault();
        const id_usuario = $(this).data("id");
        window.location.href = `editarEmpleado.php?id_usuario=${id_usuario}`;
    });
});
