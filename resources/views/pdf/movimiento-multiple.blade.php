<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de Movimiento de Activos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
        }
        .signature {
            margin-top: 50px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">ACTA DE MOVIMIENTO DE ACTIVOS</div>
        <div>Fecha: {{ $fecha->format('d/m/Y H:i:s') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Información del Usuario</div>
        <table>
            <tr>
                <th>Nombre</th>
                <td>{{ $usuario['nombre'] }}</td>
                <th>DNI</th>
                <td>{{ $usuario['dni'] }}</td>
            </tr>
            <tr>
                <th>Departamento</th>
                <td>{{ $usuario['departamento'] }}</td>
                <th>Facultad</th>
                <td>{{ $usuario['facultad'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Información del Receptor</div>
        <table>
            <tr>
                <th>Nombre</th>
                <td>{{ $receptor['nombre'] }}</td>
                <th>DNI</th>
                <td>{{ $receptor['dni'] }}</td>
            </tr>
            <tr>
                <th>Departamento</th>
                <td>{{ $receptor['departamento'] }}</td>
                <th>Facultad</th>
                <td>{{ $receptor['facultad'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Activos Movilizados</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Denominación</th>
                    <th>Ubicación Origen</th>
                    <th>Ubicación Destino</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos as $movimiento)
                <tr>
                    <td>{{ $movimiento->activo->codigo }}</td>
                    <td>{{ $movimiento->activo->catalogo->denominacion }}</td>
                    <td>{{ $movimiento->ubicacionOrigen->edificio }} - {{ $movimiento->ubicacionOrigen->aula }}</td>
                    <td>{{ $movimiento->ubicacionDestino->edificio }} - {{ $movimiento->ubicacionDestino->aula }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($observaciones)
    <div class="section">
        <div class="section-title">Observaciones</div>
        <p>{{ $observaciones }}</p>
    </div>
    @endif

    <div class="footer">
        <div style="display: flex; justify-content: space-between; margin-top: 50px;">
            <div class="signature">
                <p>Firma Usuario</p>
            </div>
            <div class="signature">
                <p>Firma Receptor</p>
            </div>
        </div>
    </div>
</body>
</html> 