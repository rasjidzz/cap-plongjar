<?php

namespace App\Exports;

use App\Models\PlottinganPengajaran;
use App\Models\TahunAjaran; // Untuk validasi atau info tambahan
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PlottinganPengajaranExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $id_tahun_ajaran;

    public function __construct(int $id_tahun_ajaran)
    {
        $this->id_tahun_ajaran = $id_tahun_ajaran;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        // Query data yang sama dengan fungsi getHasilPlottinganPengajaranByTahunAjaranId
        return PlottinganPengajaran::query()->with([
            'dosen:id,name,lecturer_code',
            'mappingKelasMatakuliah' => function ($query) {
                $query->select([
                    'id',
                    'id_matakuliah',
                    'id_tahun_ajaran',
                    'id_program_studi',
                    'nama_kelas',
                    'kuota',
                    'team_teaching'
                ])
                    ->with([
                        'matakuliah' => function ($matakuliahQuery) {
                            $matakuliahQuery->select([
                                'id',
                                'nama_matakuliah',
                                'kode_matkul',
                                'sks',
                                'praktikum',
                                'id_pic',
                                'mandatory_status',
                                'tingkat_matakuliah',
                                'hour_target',
                                'matakuliah_eksepsi',
                                'mode_perkuliahan' // Tambahkan mode_perkuliahan jika ada di model Matakuliah
                            ])->with('pic:id,name');
                        },
                        'tahunAjaran:id,tahun_ajaran,semester',
                        'programStudi:id,nama', // Memuat relasi programStudi dari mapping
                        'koordinatorMatakuliah' => function ($kmQuery) {
                            $kmQuery->select(['id', 'id_dosen', 'id_mapping_kelas_matakuliah'])
                                ->with('dosen:id,name,lecturer_code');
                        }
                    ]);
            }
        ])
            ->whereHas('mappingKelasMatakuliah', function ($query) {
                $query->where('id_tahun_ajaran', $this->id_tahun_ajaran);
            });
        // Anda bisa menambahkan orderBy di sini jika perlu
        // ->orderBy(...)
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Sesuaikan dengan daftar field yang Anda inginkan di Excel
        return [
            'Kode Mata Kuliah',
            'Nama Mata Kuliah',
            'Program Studi', // Ditambahkan
            'PIC Mata Kuliah',
            'Kode Dosen Pengajar',
            'Nama Dosen Pengajar', // Ditambahkan
            'Status Wajib Matkul',
            'Tingkat Matakuliah',
            'SKS Mata Kuliah',
            'Beban SKS Dosen',
            'Nama Kelas',
            'Kuota Kelas',
            'Praktikum',
            'Kode Dosen Koordinator',
            'Nama Dosen Koordinator', // Ditambahkan
            'Tahun Ajaran',
            'Semester', // Ditambahkan
            'Target Jam',
            'Team Teaching Kelas',
            'Matakuliah Eksepsi',
            'Mode Perkuliahan', // Ditambahkan
        ];
    }

    /**
     * @param PlottinganPengajaran $plot // Type hint ke model PlottinganPengajaran
     * @return array
     */
    public function map($plot): array
    {
        $mkm = $plot->mappingKelasMatakuliah;
        $matakuliah = $mkm ? $mkm->matakuliah : null;
        $pic = $matakuliah ? $matakuliah->pic : null;
        $dosenPengajar = $plot->dosen;
        $koordinatorPlot = $mkm ? $mkm->koordinatorMatakuliah : null;
        $dosenKoordinator = $koordinatorPlot ? $koordinatorPlot->dosen : null;
        $tahunAjaranInfo = $mkm ? $mkm->tahunAjaran : null;
        $programStudiInfo = $mkm ? $mkm->programStudi : null; // Mengambil info program studi

        return [
            $matakuliah ? $matakuliah->kode_matkul : null,
            $matakuliah ? $matakuliah->nama_matakuliah : null,
            $programStudiInfo ? $programStudiInfo->nama : null, // Ditambahkan
            $pic ? $pic->name : null,
            $dosenPengajar ? $dosenPengajar->lecturer_code : null,
            $dosenPengajar ? $dosenPengajar->name : null, // Ditambahkan
            $matakuliah ? $matakuliah->mandatory_status : null,
            $matakuliah ? $matakuliah->tingkat_matakuliah : null,
            $matakuliah ? $matakuliah->sks : null,
            $plot->beban_sks,
            $mkm ? $mkm->nama_kelas : null,
            $mkm ? $mkm->kuota : null,
            $matakuliah ? ($matakuliah->praktikum ? 'Yes' : 'No') : null,
            $dosenKoordinator ? $dosenKoordinator->lecturer_code : null,
            $dosenKoordinator ? $dosenKoordinator->name : null, // Ditambahkan
            $tahunAjaranInfo ? $tahunAjaranInfo->tahun_ajaran : null,
            $tahunAjaranInfo ? $tahunAjaranInfo->semester : null, // Ditambahkan
            $matakuliah ? $matakuliah->hour_target : null,
            $mkm ? ($mkm->team_teaching ? 'Yes' : 'No') : null,
            $matakuliah ? $matakuliah->matakuliah_eksepsi : null,
            $matakuliah ? $matakuliah->mode_perkuliahan : null, // Ditambahkan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // return [
        //     // Style baris pertama (header) menjadi bold.
        //     1    => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        //     2 => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]
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
