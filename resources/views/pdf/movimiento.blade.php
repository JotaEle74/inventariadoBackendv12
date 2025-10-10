<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de Transferencia</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            margin: 0;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .info-table {
            width: 100%;
            border: none;
            margin-bottom: 15px;
        }
        .info-table td {
            border: none;
            padding: 3px 0;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .dual-column-table {
            width: 100%;
            border: none;
            margin: 10px 0;
        }
        .dual-column-table td {
            border: none;
            padding: 5px;
            vertical-align: top;
            width: 50%;
        }
        .column-title {
            font-weight: bold;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            padding-bottom: 3px;
        }
        .signature-table {
            width: 100%;
            border: none;
            margin-top: 40px;
        }
        .signature-table td {
            border: none;
            padding: 5px;
            width: 50%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
        .ubicacion-info {
            line-height: 1.2;
        }
        .ubicacion-edificio {
            font-weight: bold;
            color: #333;
        }
        .ubicacion-detalle {
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ACTA DE TRANSFERENCIA DE BIENES</h1>
        <p>Universidad Nacional del Altiplano Puno</p>
        <p>Oficina de Inventario y Control Patrimonial</p>
    </div>

    <div class="section">
        <div class="section-title">Información del Movimiento</div>
        <table class="info-table">
            <tr>
                <td>Código: {{ $movimiento->codigo }}</td>
                <td>Fecha: {{ $movimiento->fecha_movimiento->format('d/m/Y') }}</td>
                <td>Estado: {{ ucfirst($movimiento->estado) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="dual-column-table">
            <tr>
                <td>
                    <div class="column-title">QUIEN ENTREGA</div>
                    <table class="info-table">
                        <tr>
                            <td><span class="info-label">Nombre: </span>{{ $movimiento->usuario->name }}</td>
                        </tr>
                        <tr>
                            <td><span class="info-label">DNI: </span>{{ $movimiento->usuario->dni }}</td>
                        </tr>
                        <tr>
                            <td><span class="info-label">Oficina: </span>{{ $movimiento->ubicacionOrigen ? $movimiento->ubicacionOrigen->denominacion : 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><span class="info-label">Entidad: </span>{{ $movimiento->ubicacionOrigen && $movimiento->ubicacionOrigen->entidad ? $movimiento->ubicacionOrigen->entidad->denominacion : 'N/A' }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <div class="column-title">RECEPTOR</div>
                    <table class="info-table">
                        <tr>
                            <td><span class="info-label">Nombre: </span>{{ $movimiento->receptor->name }}</td>
                        </tr>
                        <tr>
                            <td><span class="info-label">DNI: </span>{{ $movimiento->receptor->dni }}</td>
                        </tr>
                        <tr>
                            <td><span class="info-label">Oficina: </span>{{ $movimiento->ubicacionDestino ? $movimiento->ubicacionDestino->denominacion : 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><span class="info-label">Entidad: </span>{{ $movimiento->ubicacionDestino && $movimiento->ubicacionDestino->entidad ? $movimiento->ubicacionDestino->entidad->denominacion : 'N/A' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Activos Movilizados</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">Código</th>
                    <th width="20%">Denominación</th>
                    <th width="12%">Marca</th>
                    <th width="12%">Modelo</th>
                    <th width="12%">Serie</th>
                    <th width="17%">Ubicación Origen</th>
                    <th width="17%">Ubicación Destino</th>
                    <th width="10%">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimiento->movimientosActivos as $movimientoActivo)
                <tr>
                    <td>{{ $movimientoActivo->activo->codigo }}</td>
                    <td>{{ $movimientoActivo->activo->catalogo->denominacion }}</td>
                    <td>{{ $movimientoActivo->activo->marca }}</td>
                    <td>{{ $movimientoActivo->activo->modelo }}</td>
                    <td>{{ $movimientoActivo->activo->numero_serie }}</td>
                    <td>
                        @if($movimientoActivo->ubicacionOrigen)
                            <div class="ubicacion-info">
                                <div class="ubicacion-edificio">{{ $movimientoActivo->ubicacionOrigen->edificio }}</div>
                                <div class="ubicacion-detalle">
                                    Piso: {{ $movimientoActivo->ubicacionOrigen->piso }}, 
                                    Aula: {{ $movimientoActivo->ubicacionOrigen->aula }}
                                </div>
                            </div>
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if($movimientoActivo->ubicacionDestino)
                            <div class="ubicacion-info">
                                <div class="ubicacion-edificio">{{ $movimientoActivo->ubicacionDestino->edificio }}</div>
                                <div class="ubicacion-detalle">
                                    Piso: {{ $movimientoActivo->ubicacionDestino->piso }}, 
                                    Aula: {{ $movimientoActivo->ubicacionDestino->aula }}
                                </div>
                            </div>
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $movimientoActivo->observaciones ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($movimiento->observaciones_entrega)
    <div class="section">
        <div class="section-title">Observaciones de Entrega</div>
        <p>{{ $movimiento->observaciones_entrega }}</p>
    </div>
    @endif

    @if($movimiento->observaciones_recepcion)
    <div class="section">
        <div class="section-title">Observaciones de Recepción</div>
        <p>{{ $movimiento->observaciones_recepcion }}</p>
    </div>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line">
                    {{ $movimiento->usuario->name }}<br>
                    RESPONSABLE DE ENTREGA
                </div>
            </td>
            <td>
                <div class="signature-line">
                    {{ $movimiento->receptor->name }}<br>
                    RESPONSABLE DE RECEPCIÓN
                </div>
            </td>
        </tr>
    </table>

    <div class="section" style="margin-top: 20px;">
        <div class="section-title">Autorización</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Autorizado por:</td>
                <td>{{ $movimiento->autorizadoPor->name }}</td>
                <td class="info-label">Fecha de autorización:</td>
                <td>{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>