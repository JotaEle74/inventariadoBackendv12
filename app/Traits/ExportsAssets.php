<?php

namespace App\Traits;

use App\Models\Activo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

trait ExportsAssets
{
    /**
     * Export assets to Excel
     *
     * @param Collection $activos
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    protected function exportToExcel(Collection $activos)
    {
        $data = $activos->map(function ($activo) {
            return [
                'Código' => $activo->code,
                'Nombre' => $activo->nombre,
                'Categoría' => $activo->categoria->nombre,
                'Ubicación' => $activo->ubicacion->nombre,
                'Responsable' => $activo->responsable->nombre,
                'Estado' => $activo->estado,
                'Número de Serie' => $activo->numero_serie,
                'Costo' => $activo->costo,
                'Fecha de Adquisición' => $activo->fecha_adquisicion,
            ];
        });

        return Excel::download(
            new \App\Exports\ActivosExport($data),
            'activos-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export assets to PDF
     *
     * @param Collection $activos
     * @return \Illuminate\Http\Response
     */
    protected function exportToPDF(Collection $activos)
    {
        $pdf = PDF::loadView('exports.activos', [
            'activos' => $activos,
            'fecha' => now()->format('d/m/Y H:i:s')
        ]);

        return $pdf->download('activos-' . now()->format('Y-m-d') . '.pdf');
    }
} 