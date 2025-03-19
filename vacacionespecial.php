<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Solicitudes de Vacaciones</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Colores exactos */
        .bg-ac-red {
            background-color: #8B2332;
        }
        .bg-form {
            background-color: #F9F2F2;
        }
        .bg-table-header {
            background-color: #F0F0F0;
        }
        .text-ac-red {
            color: #8B2332;
        }
        .border-ac-red {
            border-color: #8B2332;
        }
        body {
            font-family: Arial, sans-serif;
        }
        /* Estilos específicos para replicar el diseño exacto */
        .custom-select {
            background-color: white;
            border: 1px solid #ced4da;
            padding: 5px;
            width: 100%;
            appearance: auto; /* Mantener el dropdown nativo */
        }
        .custom-input {
            background-color: white;
            border: 1px solid #ced4da;
            padding: 5px;
            width: 100%;
        }
        .form-label {
            font-weight: bold;
            color: #8B2332;
            margin-bottom: 3px;
            display: block;
        }
        .btn-search {
            background-color: #666666;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .btn-export {
            background-color: #666666;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .btn-create {
            background-color: white;
            color: #8B2332;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .table-header {
            background-color: #F0F0F0;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        .table-cell {
            padding: 8px 5px;
            border: 1px solid #ddd;
        }
        .icon-user {
            background-color: #666666;
            color: white;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 3px;
        }
        .user-row {
            background-color: #F9F9F9;
        }
        .user-row:nth-child(odd) {
            background-color: white;
        }
    </style>
</head>
<body>
    <div class="container mx-auto">
        <!-- Header -->
        <div class="bg-ac-red text-white py-2 text-center font-bold">
            GESTIÓN DE SOLICITUDES DE VACACIONES
        </div>
        
        <!-- Form Section -->
        <div class="bg-form p-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Empresa:</label>
                    <select class="custom-select">
                        <option>LABORATORIOS AC FARMA</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Gerencia:</label>
                    <select class="custom-select">
                        <option>Seleccione</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Departamento:</label>
                    <select class="custom-select">
                        <option>Seleccione</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Área:</label>
                    <select class="custom-select">
                        <option>Seleccione</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Sección:</label>
                    <select class="custom-select">
                        <option>Seleccione</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Solicitante:</label>
                    <input type="text" class="custom-input">
                </div>
                <div>
                    <label class="form-label">Fecha Inicio de Vacaciones:</label>
                    <div class="flex">
                        <input type="text" class="custom-input">
                        <div class="px-2 py-1 bg-white border border-gray-300 text-center">Hasta</div>
                        <input type="text" class="custom-input">
                    </div>
                </div>
                <div class="flex items-end justify-start">
                    <button class="btn-search mr-2">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                    <button class="btn-export">
                        <i class="fas fa-download mr-1"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="mt-4">
            <div class="bg-ac-red text-white py-1 flex justify-between items-center px-2">
                <div class="font-bold">Listado de Solicitudes de Vacaciones</div>
                <button class="btn-create">
                    <i class="fas fa-plus-circle mr-1"></i> Crear solicitud de vacaciones
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="table-header w-12">Id</th>
                            <th class="table-header w-20">Tipo</th>
                            <th class="table-header">Condición</th>
                            <th class="table-header">solicitante</th>
                            <th class="table-header">Fch. Solicitud</th>
                            <th class="table-header">Fch. Inicio</th>
                            <th class="table-header">Fch. Fin</th>
                            <th class="table-header w-16"># Días</th>
                            <th class="table-header">Estado</th>
                            <th class="table-header w-20">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fila 1 -->
                        <tr class="user-row">
                            <td class="table-cell text-center">
                                <div class="flex justify-center">
                                    <div class="icon-user">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div>204</div>
                            </td>
                            <td class="table-cell">Normal</td>
                            <td class="table-cell">USO DE VACACIONES GANADAS</td>
                            <td class="table-cell">CRISPIN MUÑOZ, JOEL JESUS</td>
                            <td class="table-cell text-center">13/03/2025</td>
                            <td class="table-cell text-center">13/03/2025</td>
                            <td class="table-cell text-center">13/03/2025</td>
                            <td class="table-cell text-center">1</td>
                            <td class="table-cell">Borrador</td>
                            <td class="table-cell text-center">
                                <button class="text-white bg-green-600 p-1 rounded">
                                    <i class="fas fa-check"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Fila 2 -->
                        <tr class="user-row">
                            <td class="table-cell text-center">
                                <div class="flex justify-center">
                                    <div class="icon-user">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div>205</div>
                            </td>
                            <td class="table-cell">Normal</td>
                            <td class="table-cell">USO DE VACACIONES GANADAS</td>
                            <td class="table-cell">FIGUEROA MONTOYA, JONATHAN CARLOS</td>
                            <td class="table-cell text-center">13/03/2025</td>
                            <td class="table-cell text-center">13/03/2025</td>
                            <td class="table-cell text-center">13/03/2025</td>
                            <td class="table-cell text-center">1</td>
                            <td class="table-cell">Borrador</td>
                            <td class="table-cell text-center">
                                <button class="text-white bg-green-600 p-1 rounded">
                                    <i class="fas fa-check"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Fila 3 -->
                        <tr class="user-row">
                            <td class="table-cell text-center">
                                <div class="flex justify-center">
                                    <div class="icon-user">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div>197</div>
                            </td>
                            <td class="table-cell">Normal</td>
                            <td class="table-cell">USO DE VACACIONES GANADAS</td>
                            <td class="table-cell">DULANTO EGUSQUIZA, JOSE LUIS</td>
                            <td class="table-cell text-center">30/12/2022</td>
                            <td class="table-cell text-center">30/12/2022</td>
                            <td class="table-cell text-center">31/12/2022</td>
                            <td class="table-cell text-center">2</td>
                            <td class="table-cell">Borrador</td>
                            <td class="table-cell text-center">
                                <button class="text-white bg-green-600 p-1 rounded">
                                    <i class="fas fa-check"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Fila 4 -->
                        <tr class="user-row">
                            <td class="table-cell text-center">
                                <div class="flex justify-center">
                                    <div class="icon-user">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div>202</div>
                            </td>
                            <td class="table-cell">Normal</td>
                            <td class="table-cell">USO DE VACACIONES GANADAS</td>
                            <td class="table-cell">SALAZAR VEGA, PERCY ANGEL</td>
                            <td class="table-cell text-center">30/12/2022</td>
                            <td class="table-cell text-center">30/12/2022</td>
                            <td class="table-cell text-center">01/01/2023</td>
                            <td class="table-cell text-center">3</td>
                            <td class="table-cell">Pendiente 1ra aprobación</td>
                            <td class="table-cell text-center">
                                <button class="text-white bg-green-600 p-1 rounded">
                                    <i class="fas fa-check"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Fila 5 -->
                        <tr class="user-row">
                            <td class="table-cell text-center">
                                <div class="flex justify-center">
                                    <div class="icon-user">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div>196</div>
                            </td>
                            <td class="table-cell">Extraordinario</td>
                            <td class="table-cell">USO DE VACACIONES GANADAS</td>
                            <td class="table-cell">FARFAN ZEGARRA, KASIN ARE</td>
                            <td class="table-cell text-center">29/12/2022</td>
                            <td class="table-cell text-center">29/12/2022</td>
                            <td class="table-cell text-center">30/12/2022</td>
                            <td class="table-cell text-center">2</td>
                            <td class="table-cell">Pendiente de Goce</td>
                            <td class="table-cell text-center">
                                <button class="text-white bg-gray-600 p-1 rounded">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>