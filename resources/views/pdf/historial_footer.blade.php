<div style="font-size: 12px; line-height: 1.4;">
    <ul style="margin: 0; padding-left: 15px;">
        <li>Al firmar el presente documento, el usuario esta asumiendo la plena responsabilidad sobre la existencia, conservación y funcionamiento de los bienes asimismo la realización de transferencias de bienes mismo que debe ser comunicado a la Sub unidad de patrimonio señal de conformidad de los hechos vertidos en el presente documento se suscribe la presente acta</li>
<!--         <li>El usuario es responsable de la permanencia y conservación de cada uno de los bienes muebles descritos; se recomienda tomar las precauciones del caso para evitar sustracciones, deterioros, etc.</li>
        <li>Cualquier necesidad de traslado del bien mueble dentro o fuera del local de la Entidad u organización de la Entidad, debe ser previamente comunicado al encargado de la OCP.</li> -->
    </ul>

    <table width="100%" style="margin-top: 15px; text-align: center;">
        <tr>
            <td style="font-size: 10px;">
                ______________________________<br>
                USUARIO RESPONSABLE(S):<br>
                {{ $activos[0]->r_dni ?? '' }} - {{ $activos[0]->r_name ?? '' }}
                @if($user_two)
                    <br>{{ $user_two->dni }} - {{ $user_two->name }}
                @endif
            </td>
            <td style="font-size: 10px;">
                ______________________________<br>
                PERSONAL DE PATRIMONIO:<br>
                {{ $user->dni ?? '' }} - {{ $user->name ?? '' }}
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin-top:5px; border-top:1px solid #aaa;">
        Página {PAGENO} de {nb}
    </div>
</div>
