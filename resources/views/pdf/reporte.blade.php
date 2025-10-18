<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FORMATO DE FICHA DE LEVANTAMIENTO DE INFORMACIÓN</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        @page {
            margin: 80px 40px 40px 40px; /* top, right, bottom, left */
        }
        .headerleft {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            text-align: left;
            line-height: 35px;
            font-size: 8px;
        }
        .headerright {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            text-align: right;
            line-height: 35px;
            font-size: 12px;
        }
        .page-number:before {
            content: counter(page);
        }
        .page-title {
            font-size: 8px;
        }
        .main-header { text-align: center; margin-bottom: 20px; }
        .logo { width: 120px; margin-bottom: 10px; }
        .titulo { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
        .subtitulo { font-size: 14px; margin-bottom: 20px; }
        .datos { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background: #f0f0f0; }
        .text-center { text-align: center; }
        .firma { 
            padding-top: 5px;
            text-align: center;
            line-height: 1.2;
            margin-top: 50px;
            
        }
        .signature-table td, .signature-table tr {
            border: none !important;
            vertical-align: top;
        }
        .info-table {
            border: none;
        }
    </style>
</head>
<body>
    <header>
        <p class="page-title headerleft" style="font-size: 10px;">UNIVERSIDAD NACIONAL DEL ALTIPLANO <br><span style="margin-top: -26px; display: inline-block;">Oficina de control de patrimonio</span></p>
        <span class="page-number headerright"></span>
    </header>

    <main>
    <div class="main-header">
        {{-- Logo: coloca tu logo en public/logo.png --}}
        <!-- <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo"> -->
        <div class="titulo">FORMATO DE FICHA DE LEVANTAMIENTO DE INFORMACIÓN</div>
        <div class="subtitulo">INVENTARIO PATRIMONIAL 2025</div>
    </div>
    <div class="datos">
        <table class="info-table">
            <tr class="info-table">
                <td class="info-table"><strong>CENTRO DE COSTO:</strong>{{$area->oficina->denominacion}}<br>
                    <strong>UBICACIÓN:</strong>{{$area->aula}}<br>
                    <strong>RESPONSABLE:</strong> {{ $activos[0]->r_dni }} - {{ $activos[0]->r_name }}
                </td>
                <td class="info-table">Grupo: {{$inventariador->grupo}}<br>Fecha: {{ $activos[0]->fecha_registro }}<br>
                    TIPO DE VERIFICACIÓN: FÍSICA(X) DIGITAL( )
                </td>
            </tr>
        </table>
    </div>
    <table>
        <thead>
            <tr>
                <th width="2%">N°</th>
                <th width="4%">Código</th>
                <th width="30%">Denominación</th>
                <th width="4%">Marca</th>
                <th width="4%">Modelo</th>
                <th width="3%">Tipo</th>
                <!-- <th width="4%">Color</th> -->
                <th width="4%">Serie/Dimensiones</th>
                <!-- <th width="2%">Sit</th> -->
                <th width="3%">Estado</th>
                <th width="3%">ID codigo</th>
                <th width="2%">item</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activos as $activo)
                <tr>
                    <td class="text-center">{{ $total+$loop->iteration }}</td>
                    <td>{{ $activo->codigo }}</td>
                    <td>{{ $activo->denominacion }}</td>
                    <td>{{ $activo->marca }}</td>
                    <td>{{ $activo->modelo }}</td>
                    <td>{{ $activo->tipo }}</td>
                    <!-- <td>{{ $activo->color }}</td> -->
                    <td>{{ $activo->numero_serie ? $activo->numero_serie : $activo->dimension }}</td>
                    <td>{{ $activo->condicion }}</td>
                    <!-- <td>{{ $activo->estado == 'A' ? 'U' : 'D' }}</td> -->
                     <td>{{ mt_rand(10000000, 99999999) }}</td>
                    <td>{{ $activo->aux_id }}</td>
                    <td>{{ $activo->descripcion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Inventariador(re): <br> {{ $inventariador->dni }} - {{ $inventariador->name }}</p>
    <div style="margin: 10px 0; border-top: 1px solid #333; font-size: 11px; line-height: 1.4;">
        <p style="margin-bottom: 10px;"><strong>DECLARACIÓN DE RESPONSABILIDAD:</strong></p>
        
        <ul style="margin: 0 0 15px 0; padding-left: 20px; line-height: 1.4; font-size: 11px;">
            <li style="margin-bottom: 8px;">
                El usuario declara haber mostrado todos los bienes muebles que se encuentran bajo su responsabilidad y no contar con más bienes muebles materia de inventario.
            </li>
            <li style="margin-bottom: 8px;">
                El usuario es responsable de la permanencia y conservación de cada uno de los bienes muebles descritos; se recomienda tomar las precauciones del caso para evitar sustracciones, deterioros, etc.
            </li>
            <li style="margin-bottom: 8px;">
                Cualquier necesidad de traslado del bien mueble dentro o fuera del local de la Entidad u organización de la Entidad, debe ser previamente comunicado al encargado de la OCP.
            </li>
        </ul>
    </div>
    <table class="signature-table">
        <tr>
            <td>
                <div class="firma">
                    ______________________________<br>
                    RESPONSABLE DE LOS BIENES<br>
                </div>
            </td>
            <td>
                <div class="firma">
                    ______________________________<br>
                    INVENTARIADOR(ES)<br>
                </div>
            </td>
        </tr>
    </table>
    </main>
</body>
</html> 