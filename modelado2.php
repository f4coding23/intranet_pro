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
        <h1 class="text-xl font-bold text-center uppercase">REGISTRO DE FECHAS ESPECIALES</h1>
    </header>

    <!-- Contenedor principal -->
    <div class="max-w-7xl mx-auto p-4">

        <!-- Formulario de filtros -->
        <div class="bg-white p-4 rounded shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
                <!-- Empresa -->
                <div>
                    <label for="empresa" class="block mb-1 font-medium text-gray-700">EMPRESA</label>
                    <select id="empresa" class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500">
                        <option>AC FARMA</option>
                        <!-- Opciones -->
                    </select>
                </div>

                <!-- Sección -->
                <div>
                    <label for="seccion" class="block mb-1 font-medium text-gray-700">GERENCIA</label>
                    <select id="seccion" class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500">
                        <option>Seleccione</option>
                        <!-- Opciones -->
                    </select>
                </div>

                <!-- Departamento -->
                <div>
                    <label for="departamento" class="block mb-1 font-medium text-gray-700">DEPARTAMENTO</label>
                    <select id="departamento" class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500">
                        <option>Seleccione</option>
                        <!-- Opciones -->
                    </select>
                </div>

                <!-- Genérico -->
                <div>
                    <label for="generico" class="block mb-1 font-medium text-gray-700">ÁREA</label>
                    <select id="generico" class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500">
                        <option>Seleccione</option>
                        <!-- Opciones -->
                    </select>
                </div>

                <!-- Genérico -->
                <div>
                    <label for="generico" class="block mb-1 font-medium text-gray-700">SECCIÓN</label>
                    <select id="generico" class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500">
                        <option>Seleccione</option>
                        <!-- Opciones -->
                    </select>
                </div>

                <!-- Genérico -->
                <div>
                    <label for="generico" class="block mb-1 font-medium text-gray-700">SOLICITANTE</label>
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

                <!-- Hasta -->
                <div>
                    <label for="fecha-fin" class="block mb-1 font-medium text-gray-700">FIN DE FECHA</label>
                    <input
                        type="date"
                        id="fecha-fin"
                        class="w-full border-gray-300 rounded focus:ring-red-500 focus:border-red-500" />
                </div>

                <!-- Botones -->
                <div class="flex items-end space-x-2">
                    <button
                        type="button"
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition w-1/2">
                        Buscar
                    </button>
                    <button
                        type="button"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition w-1/2">
                        Exportar
                    </button>
                    <button
                        type="button"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition w-1/2">
                        <a href="./modelado2.php">Agregar</a>
                    </button>
                </div>

                <br>
                <br>
                
            </div>
        </div>

        <!-- Tabla de solicitudes -->
        <div class="bg-white p-4 rounded shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="border-b bg-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">NRO ÍTEM</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">FECHA INICIO</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">FECHA FIN</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">EMPRESA</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">GERENCIA</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">DEPARTAMENTO</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">ÁREA</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">SECCIÓN</th>
                            <th scope="col" class="px-4 py-2 text-gray-700 uppercase text-sm font-medium">SOLICITANTE</th>
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
            </div>
        </div>

    </div>

</body>

</html>