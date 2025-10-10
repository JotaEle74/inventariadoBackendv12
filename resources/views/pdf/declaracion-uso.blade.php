<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DECLARACIÓN DE BIENES PATRIMONIALES</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        @page {
            margin: 80px 40px 40px 40px; /* top, right, bottom, left */
        }
        header {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: right;
            line-height: 35px;
            font-size: 12px;
        }
        .page-number:before {
            content: counter(page);
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
    </style>
</head>
<body>
    <header>
        <span class="page-number"></span> / {{ $declaracion->numero_folios }}
    </header>

    <main>
    <div class="main-header">
        {{-- Logo: coloca tu logo en public/logo.png --}}
        <!-- <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo"> -->
        <div class="titulo">DECLARACIÓN DE USO BIENES PATRIMONIALES</div>
        <div class="subtitulo">Documento de aceptación de responsabilidad</div>
    </div>
    <div class="datos">
        <strong>ENTIDAD:</strong> UNIVERSIDAD NACIONAL DEL ALTIPLANO<br>
        <strong>RESPONSABLE:</strong> {{ $declaracion->user->name }}<br>
        <strong>DNI:</strong> {{ $declaracion->user->dni }}<br>
        <strong>Fecha de declaración:</strong> {{ $declaracion->fecha_declaracion ? \Carbon\Carbon::parse($declaracion->fecha_declaracion)->format('d/m/Y') : '---' }}<br>
        <strong>CÓDIGO:</strong> {{ $declaracion->codigo }}<br>
    </div>
    <table>
        <thead>
            <tr>
                <th width="2%">N°</th>
                <th>Código</th>
                <th>Denominación</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Serie</th>
                <th>Estado</th>
                <th>Condición</th>
                <th>Ubicación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($declaracion->activos as $activo)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $activo->codigo }}</td>
                    <td>{{ $activo->catalogo->denominacion }}</td>
                    <td>{{ $activo->marca }}</td>
                    <td>{{ $activo->modelo }}</td>
                    <td>{{ $activo->numero_serie }}</td>
                    <td>{{ $activo->pivot->estado ?? $activo->estado }}</td>
                    <td>{{ $activo->pivot->condicion ?? $activo->condicion }}</td>
                    <td>{{ $activo->pivot->ubicacion ?? ($activo->area ? $activo->area->edificio . ' - ' . $activo->area->piso . ' - ' . $activo->area->aula : '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div>
        <!-- <strong>Observaciones:</strong> {{ $declaracion->observaciones ?? '---' }} -->
    </div>
    <div style="margin: 20px 0; font-size: 11px; line-height: 1.4;">
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
                    {{ $declaracion->user->name }}<br>
                    {{ $declaracion->user->dni }}
                </div>
            </td>
            <td>
                <div class="firma">
                    ______________________________<br>
                    PATRIMONIO<br>
                </div>
            </td>
        </tr>
    </table>
    </main>
</body>
</html> 