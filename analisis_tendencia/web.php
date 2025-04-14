<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Modulo\Tesoreria\Procesos\ProgramaciondepagosController;
use App\Http\Controllers\Modulo\Proveedor\Procesos\OrdencompracomexController;
use App\Http\Controllers\Modulo\Produccion\Procesos\GuiafabricacionController;
use App\Http\Controllers\Modulo\Comercial\Procesos\InstitucionesController;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/migra_master', function () {
    $mst = new GuiafabricacionController();
    $rpta = $mst->generaMigra(203);
    dd($rpta);
});

Route::get('/xxx_mail', function () {
    $tesoreria = new ProgramaciondepagosController();
    $rpta_mail = $tesoreria->correoAlertaCXP();
    dd($rpta_mail);
});

Route::get('/xxx_comex', function () {
    $comex = new OrdencompracomexController();
    $rpta = $comex->procesarMatrizComex();
    dd($rpta);
});

Route::get('/xxx_mail_comex', function () {
    $comex = new OrdencompracomexController();
    $rpta_mail = $comex->estadoCMP_RFC(1, 'TI', '', '');
    dd($rpta_mail);
});

Route::get('/xxx_actualiza_prov', function () {
    $comex = new OrdencompracomexController();
    $rpta = $comex->actualizarMaestroProv();
    dd($rpta);
});

Route::get('/xxx_migra_ctf', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->migracionMasiva();
    dd($rpta);
});

Route::get('/xxx_total', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->getTOTAL(env('NRO_PROCESO_PRUEBA', ''));
    dd($rpta);
});

Route::get('/xxx_renueva', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->evaluarRenovacionAuto();
    dd($rpta);
});

Route::get('/xxx_cxc', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->cuentas_x_cobrar();
    dd($rpta);
});

Route::get('/xxx_wsp', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->enviarMensajeWSP('991364235', '');
    dd($rpta);
});

Route::get('/xxx_correo_reg', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->enviarMailFinal(15);
    dd($rpta);
});

Route::get('/xxx_correo_vcto', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->enviarMailCronogramaVcto();
    dd($rpta);
});

Route::get('/xxx_correo_comercial_vcto', function () {
    $ctf = new InstitucionesController();
    $rpta = $ctf->enviarMailComercial();
    dd($rpta);
});

Route::get('/refresh-csrf', function () {
    session()->regenerateToken();
    return response()->json(['csrf_token' => csrf_token()]);
});

/*---------------------------------------------------------------------- RUTAS PRINCIPALES -----------------------------------------------------------------------*/
// LOGIN
Route::controller(App\Http\Controllers\LoginController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/login', 'index')->name('login');
});

// HOME
Route::controller(App\Http\Controllers\HomeController::class)->middleware('jwt.verify')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
});

// USUARIO
Route::controller(App\Http\Controllers\Modulo\Usuario\UsuarioController::class)->middleware('jwt.verify')->group(function () {
    Route::get('/usuario/ver', [App\Http\Controllers\Modulo\Usuario\UsuarioController::class, 'ver'])->name('ver');
    Route::match(['get', 'post'], '/usuario/consultar', [App\Http\Controllers\Modulo\Usuario\UsuarioController::class, 'consultar'])->name('consultar');
    Route::post('/usuario/mantenimiento', [App\Http\Controllers\Modulo\Usuario\UsuarioController::class, 'mantenimiento'])->name('mantenimiento');
});

// PRODUCCION
Route::controller(App\Http\Controllers\Modulo\Produccion\Procesos\GuiafabricacionController::class)->middleware('jwt.verify')->group(function () {
    Route::get('produccion/procesos/guiafabricacion/ver', [App\Http\Controllers\Modulo\Produccion\Procesos\GuiafabricacionController::class, 'ver'])->name('guiafabricacion.ver');
    Route::match(['get', 'post'], 'produccion/procesos/guiafabricacion/consultar', [App\Http\Controllers\Modulo\Produccion\Procesos\GuiafabricacionController::class, 'consultar'])->name('guiafabricacion.consultar');
    Route::post('produccion/procesos/guiafabricacion/mantenimiento', [App\Http\Controllers\Modulo\Produccion\Procesos\GuiafabricacionController::class, 'mantenimiento'])->name('guiafabricacion.mantenimiento');
    Route::get('produccion/procesos/guiafabricacion/ver/imagen/{filename}', [App\Http\Controllers\Modulo\Produccion\Procesos\GuiafabricacionController::class, 'getImagen'])->name('guiafabricacion.urlImagen');

    Route::get('produccion/procesos/guiafabricacion/pdf/{codigo}', [App\Http\Controllers\Modulo\Produccion\Procesos\PdfmasterController::class, 'inicio'])->name('guiafabricacion.docpdf');
    Route::get('produccion/procesos/guiafabricacion/pdf-grid/{codigo}', [App\Http\Controllers\Modulo\Produccion\Procesos\ReportController::class, 'pdf'])->name('guiafabricacion.report.pdf');

    Route::get('produccion/procesos/guiafabricacion/testuser', [App\Http\Controllers\Modulo\Produccion\Procesos\GuiafabricacionController::class, 'testuser'])->name('guiafabricacion.testuser');
});

// MAESTRO DE ESTRUCTURAS
Route::controller(App\Http\Controllers\Modulo\Produccion\Maestros\EstructurasController::class)->middleware('jwt.verify')->group(function () {
    Route::get('produccion/maestros/estructuras/ver', [App\Http\Controllers\Modulo\Produccion\Maestros\EstructurasController::class, 'ver'])->name('estructuras.ver');
    Route::match(['get', 'post'], 'produccion/maestros/estructuras/consultar', [App\Http\Controllers\Modulo\Produccion\Maestros\EstructurasController::class, 'consultar'])->name('estructuras.consultar');
    Route::post('produccion/maestros/estructuras/mantenimiento', [App\Http\Controllers\Modulo\Produccion\Maestros\EstructurasController::class, 'mantenimiento'])->name('estructuras.mantenimiento');
});

// GENERALES
Route::controller(App\Http\Controllers\Modulo\Produccion\Maestros\GeneralController::class)->middleware('jwt.verify')->group(function () {
    Route::get('produccion/maestros/generales/ver', [App\Http\Controllers\Modulo\Produccion\Maestros\GeneralController::class, 'ver'])->name('generales.ver');
    Route::match(['get', 'post'], 'produccion/maestros/generales/consultar', [App\Http\Controllers\Modulo\Produccion\Maestros\GeneralController::class, 'consultar'])->name('generales.consultar');
    Route::post('produccion/maestros/generales/mantenimiento', [App\Http\Controllers\Modulo\Produccion\Maestros\GeneralController::class, 'mantenimiento'])->name('generales.mantenimiento');
});

// TESORERIA
Route::controller(App\Http\Controllers\Modulo\Tesoreria\Procesos\ProgramaciondepagosController::class)->middleware('jwt.verify')->group(function () {
    Route::get('tesoreria/procesos/programaciondepagos/ver', [App\Http\Controllers\Modulo\Tesoreria\Procesos\ProgramaciondepagosController::class, 'ver'])->name('programaciondepagos.ver');
    Route::match(['get', 'post'], 'tesoreria/procesos/programaciondepagos/consultar', [App\Http\Controllers\Modulo\Tesoreria\Procesos\ProgramaciondepagosController::class, 'consultar'])->name('programaciondepagos.consultar');
    Route::post('tesoreria/procesos/programaciondepagos/mantenimiento', [App\Http\Controllers\Modulo\Tesoreria\Procesos\ProgramaciondepagosController::class, 'mantenimiento'])->name('programaciondepagos.mantenimiento');
});

// PROVEEDOR
Route::controller(App\Http\Controllers\Modulo\Proveedor\Procesos\OrdencompracomexController::class)->middleware('jwt.verify')->group(function () {
    Route::get('proveedor/procesos/ordencompracomex/ver', [App\Http\Controllers\Modulo\Proveedor\Procesos\OrdencompracomexController::class, 'ver'])->name('ordencompracomex.ver');
    Route::match(['get', 'post'], 'proveedor/procesos/ordencompracomex/consultar', [App\Http\Controllers\Modulo\Proveedor\Procesos\OrdencompracomexController::class, 'consultar'])->name('ordencompracomex.consultar');
    Route::post('proveedor/procesos/ordencompracomex/mantenimiento', [App\Http\Controllers\Modulo\Proveedor\Procesos\OrdencompracomexController::class, 'mantenimiento'])->name('ordencompracomex.mantenimiento');
});

// COMERCIAL
Route::controller(App\Http\Controllers\Modulo\Comercial\Procesos\InstitucionesController::class)->middleware('jwt.verify')->group(function () {
    Route::get('comercial/procesos/instituciones/ver', [App\Http\Controllers\Modulo\Comercial\Procesos\InstitucionesController::class, 'ver'])->name('instituciones.ver');
    Route::match(['get', 'post'], 'comercial/procesos/instituciones/consultar', [App\Http\Controllers\Modulo\Comercial\Procesos\InstitucionesController::class, 'consultar'])->name('instituciones.consultar');
    Route::post('comercial/procesos/instituciones/mantenimiento', [App\Http\Controllers\Modulo\Comercial\Procesos\InstitucionesController::class, 'mantenimiento'])->name('instituciones.mantenimiento');
});

// PROTOCOLO ANALISIS
Route::controller(App\Http\Controllers\Modulo\Controlcalidad\Procesos\ProtocoloanalisisController::class)->middleware('jwt.verify')->group(function () {
    Route::get('controlcalidad/procesos/protocoloanalisis/ver', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\ProtocoloanalisisController::class, 'ver'])->name('protocoloanalisis.ver');
    Route::match(['get', 'post'], 'controlcalidad/procesos/protocoloanalisis/consultar', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\ProtocoloanalisisController::class, 'consultar'])->name('protocoloanalisis.consultar');
    Route::post('controlcalidad/procesos/protocoloanalisis/mantenimiento', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\ProtocoloanalisisController::class, 'mantenimiento'])->name('protocoloanalisis.mantenimiento');
    Route::get('controlcalidad/procesos/protocoloanalisis/generardocumentoprotocolo', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\ProtocoloanalisisController::class, 'generardocumentoprotocolo'])->name('generarDocProto');
    Route::get('controlcalidad/procesos/protocoloanalisis/leerdocumento', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\ProtocoloanalisisController::class, 'leerdocumento'])->name('leerDoc');
    Route::get('controlcalidad/procesos/protocoloanalisis/generardocumentoespecificacion', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\ProtocoloanalisisController::class, 'generardocumentoespecificacion'])->name('generarDocEsp');

    //Route::post('controlcalidad/procesos/protocoloanalisis/swagger/signin', [AcsignerController::class, 'signin'])->name('swaggerSignin');
    //Route::post('controlcalidad/procesos/protocoloanalisis/swagger/upload-pdf', [AcsignerController::class, 'uploadPdf'])->name('swaggerUpload');


});

// PROTOCOLO ANALISIS
Route::controller(App\Http\Controllers\Modulo\Controlcalidad\Procesos\AnalisistendenciaController::class)->middleware('jwt.verify')->group(function () {
    Route::get('controlcalidad/procesos/analisistendencia/ver', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\AnalisistendenciaController::class, 'ver'])->name('protocoloanalisis.ver');
    Route::match(['get', 'post'], 'controlcalidad/procesos/analisistendencia/consultar', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\AnalisistendenciaController::class, 'consultar'])->name('protocoloanalisis.consultar');
    Route::post('controlcalidad/procesos/analisistendencia/mantenimiento', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\AnalisistendenciaController::class, 'mantenimiento'])->name('protocoloanalisis.mantenimiento');
    Route::get('controlcalidad/procesos/analisistendencia/generardocumentoprotocolo', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\AnalisistendenciaController::class, 'generardocumentoprotocolo'])->name('generarDocProto');
    Route::get('controlcalidad/procesos/analisistendencia/leerdocumento', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\AnalisistendenciaController::class, 'leerdocumento'])->name('leerDoc');
    Route::get('controlcalidad/procesos/analisistendencia/generardocumentoespecificacion', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\AnalisistendenciaController::class, 'generardocumentoespecificacion'])->name('generarDocEsp');

    //Route::post('controlcalidad/procesos/protocoloanalisis/swagger/signin', [AcsignerController::class, 'signin'])->name('swaggerSignin');
    //Route::post('controlcalidad/procesos/protocoloanalisis/swagger/upload-pdf', [AcsignerController::class, 'uploadPdf'])->name('swaggerUpload');


});

//ESPECIFICACION
Route::controller(App\Http\Controllers\Modulo\Controlcalidad\Procesos\EspecificacionesController::class)->middleware('jwt.verify')
    ->name('especificaciones.')
->group(function () {
    Route::get('controlcalidad/procesos/especificaciontecnica/ver', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\EspecificacionesController::class, 'ver'])->name('especificaciones.ver');
    Route::match(['get', 'post'], 'controlcalidad/procesos/especificaciontecnica/consultar', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\EspecificacionesController::class, 'consultar'])->name('consultar');
    Route::post('controlcalidad/procesos/especificaciontecnica/mantenimiento', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\EspecificacionesController::class, 'mantenimiento'])->name('mantenimiento');
    Route::get('controlcalidad/procesos/especificaciontecnica/generardocumento', [App\Http\Controllers\Modulo\Controlcalidad\Procesos\EspecificacionesController::class, 'generardocumento'])->name('generarDocEsp');
});

//REVISORES
Route::controller(App\Http\Controllers\Modulo\Controlcalidad\Procesos\RevisoresController::class)
    ->name('revisores.')
    ->middleware('jwt.verify')
    ->group(function () {
        Route::get(
            'controlcalidad/procesos/revisores/ver',
            [App\Http\Controllers\Modulo\Controlcalidad\Procesos\RevisoresController::class, 'ver']
        )
            ->name('revisores.ver');
        Route::match(
            ['get', 'post'],
            'controlcalidad/procesos/revisores/consultar',
            [App\Http\Controllers\Modulo\Controlcalidad\Procesos\RevisoresController::class, 'consultar']
        )
            ->name('revisores.consultar');
    });

Route::get('/refresh-csrf', function () {
    session()->regenerateToken();
    return response()->json(['csrf_token' => csrf_token()]);
});
