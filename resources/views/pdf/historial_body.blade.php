<table width="100%" border="1" cellspacing="0" cellpadding="2" style="font-size: 10px; border-collapse: collapse;">
    <thead>
        <tr>
            <th>N°</th>
            <th>Código</th>
            <th>Denominación</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Serie/Dimensiones</th>
            <th>Ambiente</th>
            <th>Sit</th>
            <th>Estado</th>
            <th>Item</th>
            <th>Observación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($activos as $i => $activo)
        <tr>
            <td>{{ $activo->item ?? $total+$i+1 }}</td>
            <td>{{ str_contains($activo->codigo, '->') ? explode('->', $activo->codigo)[0] : $activo->codigo }}</td>
            <td>{{ $activo->denominacion }}</td>
            <td>{{ $activo->marca }}</td>
            <td>{{ $activo->modelo }}</td>
            <td>{{ $activo->numero_serie ?: $activo->dimension }}</td>
            <td>{{ $activo->ambiente }}</td>
            <td>{{ $activo->estado == 'A' ? 'U' : 'D' }}</td>
            <td>{{ $activo->condicion }}</td>
            <td>{{ $activo->aux_id }}</td>
            <td>{{ $activo->descripcion }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
