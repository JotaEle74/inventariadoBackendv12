<div style="text-align: center;">
    <p><strong>UNIVERSIDAD NACIONAL DEL ALTIPLANO</strong></p>
    <div class="titulo">
        <span class="subtitulo">UNIDAD DE ABASTECIMIENTO - SUB UNIDAD DE PATRIMONIO</span><br>
        ACTA DE VERIFICACIÓN DE BIENES MUEBLES
        <!-- <span class="subtitulo">INVENTARIO 2025</span> -->
    </div>
    <table width="100%" style="font-size: 11px;">
        <tr>
            <td>
                <strong>CENTRO DE COSTO:</strong> {{ $area->oficina->denominacion ?? '' }}<br>
                <strong>UBICACIÓN:</strong> {{ $area->aula ?? '' }}<br>
                <strong>RESPONSABLE:</strong> {{ $activos[0]->r_dni ?? '' }} - {{ $activos[0]->r_name ?? '' }}<br>
                @if($user_two)
                    <strong>RESPONSABLE:</strong> {{ $user_two->dni }} - {{ $user_two->name }}
                @endif
                <br>
            </td>
            <td style="text-align:right;">
                {{ $numero_acta->numero_acta }}<br>
                Fecha: {{ \Carbon\Carbon::parse($activos[0]->fecha_registro ?? now())->format('Y-m-d') }}<br>
                Tipo de verificación: FÍSICA(X) DIGITAL( )
            </td>
        </tr>
    </table>
</div>