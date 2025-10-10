<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Activos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
        }
        .header p {
            font-size: 12px;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Activos</h1>
        <p>Fecha de generación: {{ $fecha }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Ubicación</th>
                <th>Responsable</th>
                <th>Estado</th>
                <th>Número de Serie</th>
                <th>Costo</th>
                <th>Fecha de Adquisición</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activos as $activo)
            <tr>
                <td>{{ $activo->code }}</td>
                <td>{{ $activo->nombre }}</td>
                <td>{{ $activo->categoria->nombre }}</td>
                <td>{{ $activo->ubicacion->nombre }}</td>
                <td>{{ $activo->responsable->nombre }}</td>
                <td>{{ $activo->estado }}</td>
                <td>{{ $activo->numero_serie }}</td>
                <td>{{ number_format($activo->costo, 2) }}</td>
                <td>{{ $activo->fecha_adquisicion->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema de inventario.</p>
    </div>
</body>
</html> 