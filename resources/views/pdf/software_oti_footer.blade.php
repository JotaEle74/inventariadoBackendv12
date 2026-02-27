<div style="font-size: 10px; line-height: 1.4;">
    <table width="100%" style="margin-top: 20px; text-align: center;">
        <tr>
            <td style="font-size: 10px; width: 50%;">
                ______________________________<br>
                <strong>INVENTARIADOR:</strong><br>
                DNI: {{ $inventariador->dni ?? '' }}<br>
                Nombre: {{ $inventariador->name ?? '' }}
            </td>
            <td style="font-size: 10px; width: 50%;">
                ______________________________<br>
                <strong>Encargado de oficina:</strong><br>
                DNI: {{ $responsable->dni ?? '' }}<br>
                Nombre: {{ $responsable->name ?? '' }}
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin-top:10px; border-top:1px solid #aaa; padding-top: 5px;">
        Página {PAGENO} de {nb}
    </div>
</div>
