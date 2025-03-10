<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Solicitudes de Vacaciones</title>
    <!-- Tailwind CSS vía CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <!-- Encabezado -->
    <header class="bg-red-700 text-white p-4">
        <h1 class="text-xl font-bold text-center uppercase">Gestión de Solicitudes de Vacaciones</h1>
    </header>

    <!-- Contenedor principal -->
    <div class="max-w-7xl mx-auto p-4">

        <!-- Formulario de filtros -->
        <div class="bg-white p-4 rounded shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                <!-- Empresa -->
                <div>
                    <label for="empresa" class="block mb-1 font-medium text-gray-700">SOLICITANTE</label>
                    <select id="empresa" class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500">
                        <option>Seleccione</option>
                        <!-- Opciones -->
                    </select>
                </div>

                <!-- Fecha Inicio de Vacaciones -->
                <div>
                    <label for="fecha-inicio" class="block mb-1 font-medium text-gray-700">FECHA INGRESO</label>
                    <input
                        type="date"
                        id="fecha-inicio"
                        class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500" />
                </div>

                <!-- Sección -->
                <div>
                    <label for="seccion" class="block mb-1 font-medium text-gray-700">CONDICIÓN</label>
                    <select id="seccion" class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500">
                        <option>Seleccione</option>
                        <!-- Opciones -->
                    </select>
                </div>



                <!-- Genérico -->
                <div>
                    <label for="generico" class="block mb-1 font-medium text-gray-700">CANTIDAD DÍAS:</label>
                    <input
                        type="text"
                        id="fecha-inicio"
                        class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500" />
                </div>

                <!-- Fecha Inicio de Vacaciones -->
                <div>
                    <label for="fecha-inicio" class="block mb-1 font-medium text-gray-700">FECHA INICIO</label>
                    <input
                        type="date"
                        id="fecha-inicio"
                        class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500" />
                </div>

                <!-- Fecha Inicio de Vacaciones -->
                <div>
                    <label for="fecha-inicio" class="block mb-1 font-medium text-gray-700">FECHA FIN</label>
                    <input
                        type="date"
                        id="fecha-inicio"
                        class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500" />
                </div>
            </div>
        </div>

        <!-- Tabla de solicitudes -->
        <div class="bg-white p-4 rounded shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="border-b bg-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">TRUNCAS</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">PENDIENTES</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">VENCIDAS</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">PROGRAMADAS</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">POR PROGRAMAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fila sin datos -->
                        <tr class="border-b">
                            <td colspan="9" class="px-4 py-4 text-center text-gray-500">
                                No hay datos disponibles
                            </td>
                        </tr>
                        <!-- En caso de tener datos, iterar aquí con <tr> ... </tr> -->
                    </tbody>
                </table>
                <!-- Botones -->
                <div class="flex items-end space-x-2">
                    <button
                        type="button"
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition w-1/2"
                        style="width: 10rem;">
                        <a href="./modelado1.php">Cerrar</a>
                    </button>

                    <button
                        type="button"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition w-1/2"
                        style="width: 20rem;">
                        Generar solicitud de vacaciones
                    </button>
                </div>
            </div>
        </div>

    </div>

</body>

</html>