<?php

namespace App\Exports;

use App\Models\PlottinganPengajaran;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class HasilPlottinganExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $id_tahun_ajaran;
    protected $id_program_studi;

    public function __construct(int $id_tahun_ajaran, int $id_program_studi)
    {
        $this->id_tahun_ajaran = $id_tahun_ajaran;
        $this->id_program_studi = $id_program_studi;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return PlottinganPengajaran::query()->with([
            'dosen:id,name,lecturer_code',
            'mappingKelasMatakuliah' => function ($query) {
                $query->with([
                    'matakuliah' => function ($matakuliahQuery) {
                        $matakuliahQuery->with('pic:id,name');
                    },
                    'tahunAjaran:id,tahun_ajaran,semester',
                    'koordinatorMatakuliah' => function ($kmQuery) {
                        $kmQuery->with('dosen:id,name,lecturer_code');
                    },
                    'programStudi:id,nama'
                ]);
            }
        ])
            ->whereHas('mappingKelasMatakuliah', function ($query) {
                $query->where('id_tahun_ajaran', $this->id_tahun_ajaran)
                    ->where('id_program_studi', $this->id_program_studi);
            });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Daftar header kolom untuk file Excel
        return [
            'ID Plottingan',
            'Program Studi',
            'Kode Matakuliah',
            'Nama Matakuliah',
            'PIC Matakuliah',
            'Kode Dosen Pengajar',
            'Dosen Pengajar',
            'Beban SKS Dosen',
            'Status Wajib',
            'Tingkat Matakuliah',
            'SKS Matakuliah',
            'Nama Kelas',
            'Kode Koordinator',
            'Koordinator Matakuliah',
            'Tahun Ajaran',
            'Target Jam',
            'MK Eksepsi',
        ];
    }

    /**
     * @param PlottinganPengajaran $plot
     * @return array
     */
    public function map($plot): array
    {
        $mkm = $plot->mappingKelasMatakuliah;
        $matakuliah = $mkm ? $mkm->matakuliah : null;
        $dosenPengajar = $plot->dosen;
        $prodi = $mkm->programStudi;
        $dosenKoordinator = $mkm?->koordinatorMatakuliah?->dosen;
        $tahunAjaranInfo = $mkm ? $mkm->tahunAjaran : null;

        return [
            $plot->id,
            $prodi->nama,
            $matakuliah?->kode_matkul,
            $matakuliah?->nama_matakuliah,
            $matakuliah?->pic?->name,
            $dosenPengajar?->lecturer_code,
            $dosenPengajar?->name,
            $plot->beban_sks,
            $matakuliah?->mandatory_status,
            $matakuliah?->tingkat_matakuliah,
            $matakuliah?->sks,
            $mkm?->nama_kelas,
            $dosenKoordinator?->lecturer_code,
            $dosenKoordinator?->name,
            $tahunAjaranInfo ? ($tahunAjaranInfo->tahun_ajaran . ' - ' . $tahunAjaranInfo->semester) : null,
            $matakuliah?->hour_target,
            $matakuliah?->matakuliah_eksepsi,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // return [
        //     // Style baris header menjadi bold dan rata tengah
        //     1 => [
        //         'font'      => ['bold' => true],
        //         'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        //     ],
        // ];
        $stylesArray = [
            1    => [
                'font'      => ['bold' => true, 'size' => 14],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
        $numberOfColumns = count($this->headings());
        for ($i = 1; $i <= $numberOfColumns; $i++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $stylesArray[$columnLetter] = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ]
            ];
        }
        return $stylesArray;
    }
}
