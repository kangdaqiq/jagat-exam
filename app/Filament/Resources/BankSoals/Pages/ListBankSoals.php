<?php

namespace App\Filament\Resources\BankSoals\Pages;

use App\Filament\Resources\BankSoals\BankSoalResource;
use App\Models\BankSoal;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use ZipArchive;

class ListBankSoals extends ListRecords
{
    protected static string $resource = BankSoalResource::class;

    protected static ?string $title = 'Bank Soal Ujian';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('success')
                ->tooltip('Unduh file Excel (.xlsx) sesuai kolom tabel yang sedang diaktifkan')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->action(fn () => $this->exportActiveColumnsToExcel()),

            CreateAction::make()
                ->label('Buat Bank Soal Baru')
                ->icon(Heroicon::OutlinedPlusCircle),
        ];
    }

    /**
     * Ekspor data Bank Soal ke file Microsoft Excel (.xlsx)
     * hanya untuk kolom-kolom yang sedang diaktifkan (dicentang) pada tabel.
     */
    public function exportActiveColumnsToExcel()
    {
        $visibleColumns = $this->getTable()->getVisibleColumns();

        // Fallback jika karena sesuatu hal visibleColumns kosong
        if (empty($visibleColumns)) {
            $visibleColumns = $this->getTable()->getColumns();
        }

        $headers = [];
        $columnKeys = [];
        foreach ($visibleColumns as $name => $column) {
            $columnKeys[] = $name;
            $headers[] = (string) $column->getLabel();
        }

        $records = $this->getFilteredSortedTableQuery()
            ->with(['mapel', 'user', 'kelases'])
            ->withCount('soals')
            ->get();

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($columnKeys as $colKey) {
                $row[] = $this->resolveColumnValueForExport($record, $colKey);
            }
            $rows[] = $row;
        }

        $filename = 'Bank_Soal_Ujian_' . now()->format('Ymd_His') . '.xlsx';

        if (!class_exists(ZipArchive::class)) {
            $csvFilename = 'Bank_Soal_Ujian_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($handle, $headers);
                foreach ($rows as $r) {
                    fputcsv($handle, $r);
                }
                fclose($handle);
            }, $csvFilename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $xlsxBinary = $this->buildNativeXlsxBinary($headers, $rows);

        return response()->streamDownload(function () use ($xlsxBinary) {
            echo $xlsxBinary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function resolveColumnValueForExport(BankSoal $record, string $colKey): string
    {
        return match ($colKey) {
            'judul' => (string) ($record->judul ?? '-'),
            'kode_bank' => (string) ($record->kode_bank ?? '-'),
            'mapel.nama_mapel' => (string) ($record->mapel?->nama_mapel ?? '-'),
            'kelases.nama_kelas' => $record->kelases->pluck('nama_kelas')->join(', ') ?: '-',
            'tingkat' => (string) ($record->tingkat ?: '-'),
            'soals_count' => ($record->soals_count ?? 0) . ' Soal',
            'total_bobot' => ((float) ($record->total_bobot ?? 0)) . ' Poin',
            'user.name' => (string) ($record->user?->name ?? '-'),
            'link_preview' => route('bank-soal.preview-guru', ['bankSoal' => $record->id]),
            'is_active' => $record->is_active ? 'Aktif' : 'Nonaktif',
            default => (string) data_get($record, $colKey, '-'),
        };
    }

    /**
     * Bangun file OpenXML .xlsx asli di memori (kompatibel 100% dengan Microsoft Excel, Google Sheets, & WPS).
     */
    protected function buildNativeXlsxBinary(array $headers, array $rows): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Bank Soal Ujian" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>');

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><color rgb="FF1E293B"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><u/><sz val="11"/><color rgb="FF2563EB"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '</styleSheet>');

        // Hitung lebar kolom otomatis berdasarkan panjang teks
        $colWidths = [];
        foreach ($headers as $cIdx => $h) {
            $colWidths[$cIdx] = max(14, mb_strlen($h) + 5);
        }
        foreach ($rows as $r) {
            foreach ($r as $cIdx => $val) {
                $len = mb_strlen((string) $val) + 4;
                $colWidths[$cIdx] = min(65, max($colWidths[$cIdx] ?? 14, $len));
            }
        }

        $colsXml = '<cols>';
        foreach ($colWidths as $cIdx => $width) {
            $colNum = $cIdx + 1;
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $sheetDataXml = '<sheetData>';
        $hyperlinks = [];
        $hyperlinkIndex = 1;

        // Baris 1: Header (style s="1")
        $sheetDataXml .= '<row r="1" ht="24" customHeight="1">';
        foreach ($headers as $cIdx => $h) {
            $cellRef = $this->excelColumnLetter($cIdx) . '1';
            $escaped = htmlspecialchars($h, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $sheetDataXml .= '<c r="' . $cellRef . '" t="inlineStr" s="1"><is><t>' . $escaped . '</t></is></c>';
        }
        $sheetDataXml .= '</row>';

        // Baris 2..N: Data (style s="0" untuk teks biasa, style s="2" + hyperlink aktif untuk URL)
        foreach ($rows as $rIdx => $row) {
            $rowNum = $rIdx + 2;
            $sheetDataXml .= '<row r="' . $rowNum . '">';
            foreach ($row as $cIdx => $val) {
                $strVal = (string) $val;
                $cellRef = $this->excelColumnLetter($cIdx) . $rowNum;
                $escaped = htmlspecialchars($strVal, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $isUrl = str_starts_with($strVal, 'http://') || str_starts_with($strVal, 'https://');

                if ($isUrl) {
                    $rId = 'rId_link_' . ($hyperlinkIndex++);
                    $hyperlinks[] = [
                        'ref' => $cellRef,
                        'rId' => $rId,
                        'url' => $escaped,
                    ];
                    $sheetDataXml .= '<c r="' . $cellRef . '" t="inlineStr" s="2"><is><t>' . $escaped . '</t></is></c>';
                } else {
                    $sheetDataXml .= '<c r="' . $cellRef . '" t="inlineStr" s="0"><is><t>' . $escaped . '</t></is></c>';
                }
            }
            $sheetDataXml .= '</row>';
        }

        $sheetDataXml .= '</sheetData>';

        $hyperlinksXml = '';
        if (!empty($hyperlinks)) {
            $hyperlinksXml = '<hyperlinks>';
            $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

            foreach ($hyperlinks as $hl) {
                $hyperlinksXml .= '<hyperlink ref="' . $hl['ref'] . '" r:id="' . $hl['rId'] . '"/>';
                $relsXml .= '<Relationship Id="' . $hl['rId'] . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="' . $hl['url'] . '" TargetMode="External"/>';
            }

            $hyperlinksXml .= '</hyperlinks>';
            $relsXml .= '</Relationships>';

            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $relsXml);
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $colsXml
            . $sheetDataXml
            . $hyperlinksXml
            . '</worksheet>');

        $zip->close();

        $binary = file_get_contents($tempFile);
        @unlink($tempFile);

        return $binary ?: '';
    }

    protected function excelColumnLetter(int $zeroBasedIndex): string
    {
        $letter = '';
        $n = $zeroBasedIndex;
        while ($n >= 0) {
            $letter = chr(65 + ($n % 26)) . $letter;
            $n = intdiv($n, 26) - 1;
        }
        return $letter;
    }
}
