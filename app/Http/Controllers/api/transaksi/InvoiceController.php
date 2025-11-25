<?php

namespace App\Http\Controllers\api\transaksi;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\GetterSetter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\api\master\ConfigController;

class InvoiceController extends Controller
{
    public function store(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'nama_tamu' => 'required|max:100',
                'no_telepon' => 'required|max:20',
                'nik' => 'required|max:16',
                'total_harga' => 'required',
                'sesi_jual' => 'required'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Data sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $cKodeInvoice = GetterSetter::getKodeFaktur('INV', 3);
            $kamarInvoiceData = DB::table('detail_invoice as i')
                ->select(
                    'i.no_kamar',
                    'i.tgl_checkin',
                    'i.tgl_checkout',
                    'k.status',
                    'k.per_harga'
                )
                ->leftJoin('kamar as k', 'i.no_kamar', 'k.kode_kamar')
                ->orderByDesc('i.id')
                ->get()
                ->groupBy('no_kamar');

            $vaArray = [];

            if (empty($request->input('kamar'))) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Meja tidak boleh kosong',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            foreach ($request->input('kamar') as $item) {
                $dTglIn = Carbon::parse($item['cek_in']);
                $dTglOut = Carbon::parse($item['cek_out']);
                $kode_kamar = $item['kode_kamar'];
                $no_kamar = $item['no_kamar'];


                // Cek apakah no_kamar ada di data hasil query
                // dd($kamarInvoiceData[$kode_kamar]);
                if (isset($kamarInvoiceData[$kode_kamar]) && $kamarInvoiceData[$kode_kamar]->first()->status == 1) {
                    $invoice = $kamarInvoiceData[$kode_kamar];
                    $dTglDigunakan = '';


                    // Periksa apakah tanggal check-in bertabrakan dengan data di database
                    $isDateMatchedDB = $invoice->contains(function ($i) use (&$dTglDigunakan, $dTglIn, $dTglOut) {
                        $tglCheckin = Carbon::parse($i->tgl_checkin);
                        $tglCheckout = Carbon::parse($i->tgl_checkout);

                        $tglCheckin = Carbon::parse($i->tgl_checkin);
                        $tglCheckout = Carbon::parse($i->tgl_checkout);

                        $isMatched = $dTglIn < $tglCheckout && $dTglOut > $tglCheckin;

                        if ($isMatched) {
                            $dTglDigunakan = $tglCheckin->format('Y-m-d H:i') . " - " . $tglCheckout->format('Y-m-d H:i');
                        }

                        return $isMatched;
                    });

                    if ($isDateMatchedDB) {
                        return response()->json([
                            'status' => self::$status['GAGAL'],
                            'message' => "Maaf, kamar $no_kamar sedang digunakan pada tanggal $dTglDigunakan",
                            'datetime' => date('Y-m-d H:i:s')
                        ], 400);
                    }
                }

                $vaArray[] = [
                    'kode_invoice' => $cKodeInvoice,
                    'no_kamar' => $item['kode_kamar'],
                    'harga_kamar' => $item['harga_kamar'],
                    'tgl_checkin' => $item['cek_in'],
                    'tgl_checkout' => $item['cek_out'],
                    'per_harga' => $item['per_harga']
                ];
            }


            $vaInsert =  DB::table('detail_invoice')->insert($vaArray);

            if (!$vaInsert) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Create Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            $vaData = DB::table('invoice')->insert([
                'kode_invoice' => $cKodeInvoice,
                'kode_reservasi' => $request->kode_reservasi,
                'nama_tamu' => $request->nama_tamu,
                'nik' => $request->nik,
                'no_telepon' => $request->no_telepon,
                'total_harga' => $request->total_harga,
                'total_kamar' => $request->total_kamar,
                'disc' => $request->disc,
                'sesi_jual' => $request->sesi_jual,
                'ppn' => $request->ppn,
                'dp' => $request->dp,
                'bayar' => $request->bayar,
                'status_bayar' => $request->status_bayar,
                'sisa_bayar' => $request->sisa_bayar,
                'kembalian' => $request->kembalian,
                'cara_bayar' => $request->metode_pembayaran,
                'tgl' => Carbon::now(),
                'datetime' => Carbon::now(),
            ]);

            if (!$vaData) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Create Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }


            if (count($vaArray) > 0) {
                $vaNoKamar = array_map(function ($dt) {
                    return $dt['no_kamar'];
                }, $vaArray);

                DB::table('kamar')
                    ->whereIn('kode_kamar', $vaNoKamar)
                    ->update(['status' => '1']);
            }


            if ($request->kode_reservasi) {
                DB::table('reservasi')->where('kode_reservasi', $request->kode_reservasi)->update([
                    'status' => '1'
                ]);
            }

            GetterSetter::setKodeFaktur('INV');

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Create Data',
                'kode_invoice' => $cKodeInvoice,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function pay(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'nama_tamu' => 'required|max:100',
                'no_telepon' => 'required|max:20',
                'total_harga' => 'required',
                'sesi_jual' => 'required'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Data sudah ada di database.'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            foreach ($request->kamar as $item) {
                DB::table('detail_invoice')
                    ->where('kode_invoice', $request->kode_invoice)
                    ->update([
                        'harga_kamar' => $item['harga_kamar'],
                        'tgl_checkout' => $item['cek_out']
                    ]);

                DB::table('kamar')
                    ->where('kode_kamar', $item['kode_kamar'])
                    ->update(['status' => $item['status']]);
            }

            $vaInput = [
                'disc' => $request->disc,
                'ppn' => $request->ppn,
                'dp' => $request->dp,
                'total_harga' => $request->total_harga,
                'total_kamar' => $request->total_kamar,
                'status_bayar' => $request->status_bayar,
                'sisa_bayar' => $request->sisa_bayar,
                'kembalian' => $request->kembalian,
                'cara_bayar' => $request->metode_pembayaran,
                'sesi_jual' => $request->sesi_jual,
                'tgl' => Carbon::now(),
            ];

            if ($request->bayar > 0) {
                $vaInput['bayar'] = $request->bayar;
            }

            // Update data di tabel invoice
            $vaData = DB::table('invoice')->where('kode_invoice', $request->kode_invoice)->update($vaInput);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Update Data',
                'kode_invoice' => $request->kode_invoice,
                'datetime' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function laporan(Request $request)
    {
        $dTglAwal = $request->tgl_awal;
        $dTglAkhir = $request->tgl_akhir;
        try {

            $vaPrint = [];

            $vaData = DB::table('invoice as i')
                ->select(
                    'i.kode_invoice',
                    'i.nama_tamu',
                    'i.nik',
                    'i.no_telepon',
                    'i.bayar',
                    'i.total_harga',
                    'p.keterangan as cara_bayar',
                    'i.tgl',
                    'i.total_kamar',
                    'i.status_bayar',
                    'i.kembalian',
                    'i.sisa_bayar',
                    'i.dp',
                    'i.ppn',
                    'i.disc',
                    'i.cara_bayar as kode_cara_bayar'
                )
                ->leftJoin('pembayaran as p', 'i.cara_bayar', 'p.kode')
                ->whereBetween('i.tgl', [$dTglAwal, $dTglAkhir])
                ->orderByDesc('i.tgl')
                ->get();

            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'SUKSES',
                    'data' => [],
                    'totData' => 0,
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }

            $totLaporan = 0;

            $data = [
                'kode' => ['ppn'],
            ];

            $request = new Request($data);

            $configController = new ConfigController();
            $response = $configController->data($request);

            $config = json_decode($response->getContent(), true);

            $response = $vaData->map(function ($invoice) use (&$totLaporan, $config, &$vaPrint) {
                // Query untuk mendapatkan detail kamar terkait dengan invoice ini
                $vaData2 = DB::table('detail_invoice as d')
                    ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                    ->select(
                        'k.no_kamar',
                        'k.kode_kamar',
                        'd.harga_kamar',
                        'd.tgl_checkin',
                        'd.tgl_checkout',
                        'k.status as status_kamar',
                        'k.per_harga',
                    )
                    ->where('d.kode_invoice', $invoice->kode_invoice)
                    ->get();

                $totalHarga = $invoice->total_harga;

                $disc = $invoice->total_kamar * ($invoice->disc / 100);
                $ppn = $invoice->total_kamar * ($invoice->ppn / 100);

                $totalHargareal = $invoice->total_kamar + $ppn - $disc;

                // $status_bayar = $totalHarga - $invoice->bayar < 1 ? 'Lunas' : 'Belum Lunas';
                // $sisa_bayar = $totalHarga - $invoice->bayar;

                if ($invoice->status_bayar == '0') {
                    $totLaporan++;
                }

                foreach ($vaData2 as $v) {
                    $totalHarga = "Rp " . number_format(floatval($totalHarga), 0, ',', '.');

                    $vaPrint[] = [
                        'FAKTUR' => $invoice->kode_invoice,
                        'TGL' => $invoice->tgl,
                        'NAMA' => $invoice->nama_tamu,
                        'NIP' => $invoice->nik,
                        'TELEPON' => $invoice->no_telepon,
                        'HARGA TOTAL' => $totalHarga,
                        'MEJA' => $v->no_kamar,
                        'JAM MAIN' => Carbon::parse($v->tgl_checkin)->format('H:i'),
                        'JAM SELESAI' => Carbon::parse($v->tgl_checkout)->format('H:i')
                    ];
                }

                return [
                    'kode_invoice' => $invoice->kode_invoice,
                    'nama_tamu' => $invoice->nama_tamu,
                    'no_telepon' => $invoice->no_telepon,
                    'nik' => $invoice->nik,
                    'total_bayar_tersisa' => $totalHarga,
                    'total_harga_real' => $totalHargareal,
                    'bayar' => $invoice->bayar,
                    'status_bayar' => $invoice->status_bayar,
                    'sisa_bayar' => $invoice->sisa_bayar,
                    'cara_bayar' => $invoice->cara_bayar,
                    'kode_cara_bayar' => $invoice->kode_cara_bayar,
                    'total_kamar' => $invoice->total_kamar,
                    'tgl_invoice' => $invoice->tgl,
                    'dp' => $invoice->dp,
                    'ppn' => $invoice->ppn,
                    'kembalian' => $invoice->kembalian,
                    'kamar' => $vaData2->map(function ($item) {
                        return [
                            'harga_kamar' => $item->harga_kamar,
                            'kode_kamar' => $item->kode_kamar,
                            'no_kamar' => $item->no_kamar,
                            'cek_in' => $item->tgl_checkin,
                            'cek_out' => $item->tgl_checkout,
                            'status' => $item->status_kamar,
                            'per_harga' => $item->per_harga
                        ];
                    }),
                ];
            });


            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
                'dataPrint' => $vaPrint,
                'totData' => $totLaporan,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function getDataPdfInvoice(Request $request)
    {

        try {
            $invoice = DB::table('invoice as i')
                ->select(
                    'i.kode_invoice',
                    'i.nama_tamu',
                    'i.nik',
                    'i.no_telepon',
                    'i.bayar',
                    'i.total_harga',
                    'p.keterangan as cara_bayar',
                    'i.tgl',
                    'i.dp',
                    'i.disc',
                    'i.ppn',
                    'i.total_kamar',
                    'i.sisa_bayar',
                    'i.status_bayar',
                    'i.kembalian',
                    'i.cara_bayar as kode_cara_bayar'
                )
                ->leftJoin('pembayaran as p', 'i.cara_bayar', 'p.kode')
                ->where('i.kode_invoice', $request->kode_invoice)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data Tidak Ditemukan Atau Error',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }


            $data = [
                'kode' => ['logo', 'nama_hotel', 'alamat_hotel', 'no_telp'],
            ];

            $request = new Request($data);

            $configController = new ConfigController();
            $response = $configController->data($request);

            $config = json_decode($response->getContent(), true);

            // Query untuk mendapatkan detail kamar terkait dengan invoice ini
            $vaData2 = DB::table('detail_invoice as d')
                ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                ->select('k.no_kamar', 'k.kode_kamar',  'd.harga_kamar', 'd.tgl_checkin', 'd.tgl_checkout', 'k.status as status_kamar', 'k.per_harga')
                ->where('d.kode_invoice', $invoice->kode_invoice)
                ->get();

            // return response()->json([
            //     'status' => self::$status['BAD_REQUEST'],
            //     'message' => 'Terjadi Kesalahan Saat Proses Data',
            //     'data' => $totalHargaKamar,
            //     'datetime' => date('Y-m-d H:i:s')
            // ], 400);

            $totalHarga = $invoice->total_harga;


            $response = [
                'kode_invoice' => $invoice->kode_invoice,
                'nama_tamu' => $invoice->nama_tamu,
                'no_telepon' => $invoice->no_telepon,
                'nik' => $invoice->nik,
                'total_harga' => $totalHarga,
                'total_kamar' => $invoice->total_kamar,
                'dp' => $invoice->dp,
                'ppn' => $invoice->ppn,
                'disc' => $invoice->disc,
                'bayar' => $invoice->bayar,
                'status_bayar' => $invoice->status_bayar,
                'no_telp_hotel' => $config['data']['no_telp'],
                'alamat_hotel' => $config['data']['alamat_hotel'],
                'nama_hotel' => $config['data']['nama_hotel'],
                'logo_hotel' => $config['data']['logo'],
                'sisa_bayar' => $invoice->sisa_bayar,
                'cara_bayar' => $invoice->cara_bayar,
                'kode_cara_bayar' => $invoice->kode_cara_bayar,
                'kembalian' => $invoice->kembalian,
                'tgl_invoice' => $invoice->tgl,
                'kamar' => $vaData2->map(function ($item) {
                    return [
                        'harga_kamar' => $item->harga_kamar,
                        'kode_kamar' => $item->kode_kamar,
                        'no_kamar' => $item->no_kamar,
                        'cek_in' => $item->tgl_checkin,
                        'cek_out' => $item->tgl_checkout,
                        'status' => $item->status_kamar,
                        'per_harga' => $item->per_harga
                    ];
                }),
            ];

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function checkout(Request $request)
    {
        $dTglAwal = $request->tgl_awal;
        $dTglAkhir = $request->tgl_akhir;
        try {

            $update = DB::table('kamar')->where('kode_kamar', $request->kode_kamar)->update([
                'status' => '0'
            ]);

            $vaData = DB::table('invoice as i')
                ->select(
                    'i.kode_invoice',
                    'i.nama_tamu',
                    'i.nik',
                    'i.no_telepon',
                    'i.bayar',
                    'i.total_harga',
                    'i.status_bayar',
                    'i.sisa_bayar',
                    'i.cara_bayar',
                )
                ->whereBetween('i.tgl', [$dTglAwal, $dTglAkhir])
                ->get();

            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Terjadi Kesalahan Saat Mengupdate Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            $response = $vaData->map(function ($invoice) {
                $vaData2 = DB::table('detail_invoice as d')
                    ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                    ->select('k.no_kamar', 'k.kode_kamar', 'd.harga_kamar', 'd.tgl_checkin', 'd.tgl_checkout', 'k.status as status_kamar')
                    ->where('d.kode_invoice', $invoice->kode_invoice)
                    ->get();

                $totalHarga = $invoice->total_harga;

                // $status_bayar = $totalHarga - $invoice->bayar < 1 ? 'Lunas' : 'Belum Lunas';
                $sisa_bayar = $invoice->sisa_bayar;

                return [
                    'kode_invoice' => $invoice->kode_invoice,
                    'nama_tamu' => $invoice->nama_tamu,
                    'no_telepon' => $invoice->no_telepon,
                    'nik' => $invoice->nik,
                    'total_harga' => $totalHarga,
                    'bayar' => $invoice->bayar,
                    'status_bayar' => $invoice->status_bayar,
                    'sisa_bayar' => $sisa_bayar,
                    'cara_bayar' => $sisa_bayar,
                    'kamar' => $vaData2->map(function ($item) {
                        return [
                            'harga_kamar' => $item->harga_kamar,
                            'no_kamar' => $item->no_kamar,
                            'kode_kamar' => $item->kode_kamar,
                            'cek_in' => $item->tgl_checkin,
                            'cek_out' => $item->tgl_checkout,
                            'status' => $item->status_kamar
                        ];
                    }),
                ];
            });
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function delete(Request $request)
    {
        try {
            DB::table('invoice')->where('kode_invoice', $request->kode)->delete();

            $vaData = DB::table('detail_invoice')->where('kode_invoice', $request->kode)->get();

            foreach ($vaData as $val) {
                DB::table('kamar')->where('kode_kamar', $val->no_kamar)->update([
                    'status' => '0'
                ]);
            }

            DB::table('detail_invoice')->where('kode_invoice', $request->kode)->delete();

            if ($vaData === 0) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Hapus Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Hapus Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }
}
