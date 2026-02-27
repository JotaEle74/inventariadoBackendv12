<div style="padding: 5px;">
    <div style="text-align: center;">
        <p style="margin: 5px 0; font-size: 12px; font-weight: bold;">
            LEVANTAMIENTO DE INFORMACIÓN - OTI 2025
        </p>
        <p style="margin: 5px 0; font-size: 12px; font-weight: bold; color: #333;">
            FORMATO DE TOMA DE INVENTARIO DE SOFTWARE
        </p>
    </div>

    @if($oficina)
    <table width="100%" style="font-size: 10px; margin-top: 10px;">
        <tr>
            <td style="width: 50%;">
                @if($oficina)
                    <strong>UBICACIÓN:</strong>
                    {{ $oficina->denominacion ?? '' }}<br>
                @endif
            </td>
            <td style="width: 50%; text-align: right;">
                Fecha: {{ date('d/m/Y') }}
            </td>
        </tr>
    </table>
    @endif
</div>
