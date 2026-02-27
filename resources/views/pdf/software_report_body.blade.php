{{-- TABLA 1: SOFTWARE DE TERCEROS --}}
<div style="margin: 15px; 0">
    <h3 style="font-size: 8px; font-weight: bold; margin: 5px 0; background-color: #f0f0f0; color: black; padding: 5px; text-align: center;">
        SOFTWARE DE TERCEROS
    </h3>
    <table width="100%" border="1" cellspacing="0" cellpadding="4" style="font-size: 6px; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th>N°</th>
                <th>CÓDIGO</th>
                <th>DENOMINACIÓN</th>
                <th>NOMBRE</th>
                <th style="width: 15%;">FUENTE</th>
                <th>OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse($softwareTerceros as $i=>$software)
            <tr>
                <td>{{ $activo->item ?? $i+1 }}</td>
                <td>{{ $software->codigo ?? '' }}</td>
                <td>{{ $software->denominacion ?? '' }}</td>
                <td>{{ $software->nombre ?? '' }}</td>
                <td>{{ $software->fuente ?? '' }}</td>
                <td>{{ $software->observaciones ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #999;">No hay registros de software de terceros</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div>
        <strong>Descripción de tipos de software:</strong><br>
        <ul style="margin: 0; padding-left: 15px;">
            <li><strong>Gratuita:</strong> Software libre de uso, sin necesidad de pago.</li>
            <li><strong>Gratuita (GPL):</strong> Software libre bajo licencia GNU, uso y distribución permitidos.</li>
            <li><strong>De pago / De pago (Suscripción):</strong> Licencia comercial válida, puede requerir pago único o suscripción.</li>
            <li><strong>Volumen:</strong> Licencia corporativa válida para múltiples usuarios.</li>
            <li><strong>Cuenta Microsoft / Cuenta:</strong> Software legal mediante cuenta corporativa o educativa.</li>
            <li><strong>Redistribuible:</strong> Componentes legales para redistribución (por ejemplo, librerías o runtimes).</li>
            <li><strong>Demo / Prueba:</strong> Licencia válida para uso temporal según condiciones de prueba.</li>
            <li><strong>Controlador:</strong> Drivers legales necesarios para hardware o periféricos.</li>
        </ul>
    </div>
</div>

{{-- TABLA 2: SOFTWARE INTERNOS --}}
<div style="margin-bottom: 15px;">
    <h3 style="font-size: 11px; font-weight: bold; margin: 5px 0; background-color: #f0f0f0; color: black; padding: 5px; text-align: center;">
        SOFTWARE INTERNOS
    </h3>
    <table width="100%" border="1" cellspacing="0" cellpadding="4" style="font-size: 9px; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th>N°</th>
                <th style="width: 25%;">NOMBRE</th>
                <th style="width: 25%;">DENOMINACIÓN</th>
                <th style="width: 15%;">ESTADO</th>
                <th style="width: 35%;">TECNOLOGÍAS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($softwareInternos as $i=>$software)
            <tr>
                <td>{{ $activo->item ?? $i+1 }}</td>
                <td>{{ $software->nombre ?? '' }}</td>
                <td>{{ $software->denominacion ?? '' }}</td>
                <td>{{ $software->estado ?? '' }}</td>
                <td>{{ $software->tecnologias ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #999;">No hay registros de software internos</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- TABLA 3: REDES SOCIALES --}}
<div style="margin-bottom: 15px;">
    <h3 style="font-size: 11px; font-weight: bold; margin: 5px 0; background-color: #f0f0f0; color: black; padding: 5px; text-align: center;">
        REDES SOCIALES
    </h3>
    <table width="100%" border="1" cellspacing="0" cellpadding="4" style="font-size: 9px; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="width: 25%;">NOMBRE</th>
                <th style="width: 35%;">DESCRIPCIÓN</th>
                <th style="width: 15%;">ESTADO</th>
                <th style="width: 25%;">PLATAFORMA</th>
            </tr>
        </thead>
        <tbody>
            @forelse($redesSociales as $red)
            <tr>
                <td>{{ $red->nombre ?? '' }}</td>
                <td>{{ $red->descripcion ?? '' }}</td>
                <td>{{ $red->estado ?? '' }}</td>
                <td>{{ $red->plataforma ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #999;">No hay registros de redes sociales</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
