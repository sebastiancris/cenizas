$(document).ready(function() {
    $.ajax({
        url: 'casosUsuarioAdmin', 
        type: 'POST',
        dataType: 'json',
        data: {_token: $('meta[name="csrf-token"]').attr('content')},
        success: function(response) {
             $('#registros').DataTable({
                language: {
                    url: appConfig.dataTablesLangUrl
                },
                data: response,
                columns: [
                    { data: 'id' },
                    { data: 'asunto' },
                    { data: 'nombre_usuario' },
                    { data: 'fecha_creacion' },
                    { data: 'estado' },
                    { data: 'respuesta' }
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            }
                        }
                    },
                    {
                        extend: 'excel',
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            }
                        },
                        filename: 'Portal Comunidades',
                        title: 'Portal Comunidades'
                    },
                    {
                        extend: 'pdf',
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            }
                        },
                        filename: 'Portal Comunidades',
                        title: 'Portal Comunidades'
                    }
                ],
                paging: true
            });
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar los casos:', error);
        }
    });
});