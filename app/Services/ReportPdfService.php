<?php

namespace App\Services;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Generates branded A4 PDF reports (matching the Open Gate digital-card look)
 * from a simple column/rows definition.
 */
class ReportPdfService
{
    /**
     * @param  array{title:string, subtitle?:string, filters?:array<string,string>}  $meta
     * @param  array<int, array{label:string, key?:string}>  $columns
     * @param  array<int, array<string,mixed>>  $rows
     * @param  array<int, array{label:string, value:string}>  $totals
     */
    public function generate(array $meta, array $columns, array $rows, array $totals = []): Mpdf
    {
        $orgMain = \App\Models\Setting::get('org.name', 'UMOJA WA VYUO KARISMATIKI KATOLIKI TANZANIA');
        $orgSub = \App\Models\Setting::get('org.sub', 'JIMBO KUU KATOLIKI LA ARUSHA NA JIMBO LA MOSHI');
        $campaign = \App\Models\Setting::get('event.name', 'Open Gate Camp');
        $orgLine = \App\Models\Setting::get('org.line', 'JIMBO KUU KATOLIKI LA ARUSHA NA JIMBO LA MOSHI');

        $html = view('reports.pdf', [
            'meta' => $meta,
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
            'orgMain' => $orgMain,
            'orgSub' => $orgSub,
            'campaign' => $campaign,
            'orgLine' => $orgLine,
            'generatedAt' => now()->format('d M Y H:i'),
            'logoPath' => public_path('logo.png'),
        ])->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $fontDirs[] = storage_path('fonts');

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $fontData['Manrope'] = [
            'R' => 'Manrope-Regular.ttf',
            'B' => 'Manrope-Bold.ttf',
            'SB' => 'Manrope-SemiBold.ttf',
        ];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'tempDir' => storage_path('app/private/mpdf'),
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => 'Manrope',
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf;
    }
}
