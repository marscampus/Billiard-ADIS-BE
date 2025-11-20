<?php

use App\Helpers\Func;
use Illuminate\Http\Request;
use App\Helpers\GetterSetter;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\kas\KasController;
use App\Http\Controllers\api\DashboardController;
use App\Http\Controllers\plugin\PluginController;
use App\Http\Controllers\api\auth\LoginController;
use App\Http\Controllers\api\master\RakController;
use App\Http\Controllers\api\kasir\BayarController;
use App\Http\Controllers\api\kasir\KasirController;
use App\Http\Controllers\api\kasir\ReturController;
use App\Http\Controllers\api\master\UserController;
use App\Http\Controllers\api\master\KamarController;
use App\Http\Controllers\api\master\AntrianMejaController;
use App\Http\Controllers\api\master\ShiftController;
use App\Http\Controllers\api\kasir\ReprintController;
use App\Http\Controllers\api\master\ConfigController;
use App\Http\Controllers\api\master\GudangController;
use App\Http\Controllers\api\master\ProdukController;
use App\Http\Controllers\api\kasir\KasirTmpController;
use App\Http\Controllers\api\laporan\NeracaController;
use App\Http\Controllers\api\posting\JurnalController;
use App\Http\Controllers\api\master\RekeningController;
use App\Http\Controllers\api\kasir\RekapKasirController;
use App\Http\Controllers\api\kasir\ShiftKasirController;
use App\Http\Controllers\api\laporan\LabaRugiController;
use App\Http\Controllers\api\laporan\LapKasirController;
use App\Http\Controllers\api\master\TipeKamarController;
use App\Http\Controllers\api\pembukuan\AktivaController;
use App\Http\Controllers\api\kasir\CetakFakturController;
use App\Http\Controllers\api\laporan\BukuBesarController;
use App\Http\Controllers\api\master\PembayaranController;
use App\Http\Controllers\api\transaksi\InvoiceController;
use App\Http\Controllers\api\master\SatuanStockController;
use App\Http\Controllers\api\master\UangPecahanController;
use App\Http\Controllers\api\transaksi\ReservasiController;
use App\Http\Controllers\api\master\DiskonPeriodeController;
use App\Http\Controllers\api\master\GolonganStockController;
use App\Http\Controllers\api\master\JenisSupplierController;
use App\Http\Controllers\api\master\StockSupplierController;
use App\Http\Controllers\api\pembukuan\JurnalLainController;
use App\Http\Controllers\api\kasir\RekapKasirHotelController;
use App\Http\Controllers\api\master\ConfigRekeningController;
use App\Http\Controllers\api\master\DaftarSupplierController;
use App\Http\Controllers\api\master\FasilitasKamarController;
use App\Http\Controllers\api\master\GolonganAktivaController;
use App\Http\Controllers\api\posting\PostingAktivaController;
use App\Http\Controllers\api\laporan\DaftarPembelianController;
use App\Http\Controllers\api\pembelian\PurchaseOrderController;
use App\Http\Controllers\api\laporan\LapDaftarPenjualanController;
use App\Http\Controllers\api\master\PerubahanHargaStockController;
use App\Http\Controllers\api\pembelian\PembayaranFakturController;
use App\Http\Controllers\api\transaksi\LaporanTransaksiController;
use App\Http\Controllers\api\transaksistock\StockOpnameController;
use App\Http\Controllers\api\pemindahbukuan\PemindahbukuanController;
use App\Http\Controllers\api\laporan\laporanstock\LapSisaStockController;
use App\Http\Controllers\api\pembelian\PembelianPenerimaanBarangController;
use App\Http\Controllers\api\pembelian\ReturPembelianTanpaFakturController;
use App\Http\Controllers\api\pembelian\ReturPembelianDenganFakturController;
use App\Http\Controllers\api\laporan\laporantransaksistock\InventoriController;
use App\Http\Controllers\api\laporan\laporanpenjualan\PenjualanPerBarangController;
use App\Http\Controllers\api\laporan\laporanstock\LapPerubahanHargaStockController;
use App\Http\Controllers\api\laporan\laporantransaksistock\NilaiPersediaanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//login
Route::middleware(['security.header'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/checkowner', [PluginController::class, 'getClientHadPluginResto']);
    Route::post('/plugin/checkpluginuser', [PluginController::class, 'checkUserHadPlugin']);

    Route::post('/konsolidasi', [PluginController::class, 'checkUserHadPlugin']);


    Route::middleware(['check.token'])->group(function () {
        // Function
        Route::post('get_kode-kamar', function (Request $request) {
            $cKey = $request->Key;
            $nLen = $request->Len;
            $response = GetterSetter::getKodeKamar($cKey, $nLen);
            return response()->json($response);
        });

        Route::post('get_username', function (Request $request) {
            $response = $request->auth->name;
            return response()->json($response);
        });

        Route::post('get_email', function (Request $request) {
            $response = Func::getEmail($request);
            return response()->json($response);
        });

        Route::post('set_kode-kamar', function (Request $request) {
            $cKey = $request->Key;
            $response = GetterSetter::setKodeKamar($cKey);
            return response()->json($response);
        });

        Route::post('get_faktur', function (Request $request) {
            $cKey = $request->Key;
            $nLen = $request->Len;
            $response = GetterSetter::getKodeFaktur($cKey, $nLen);
            return response()->json($response);
        });

        Route::post('set_faktur', function (Request $request) {
            $cKey = $request->Key;
            $response = GetterSetter::setKodeFaktur($cKey);
            return response()->json($response);
        });

        Route::post('getSaldoAwalTnpGab', function (Request $request) {
            $dTgl = $request->Tgl;
            $cRekening = $request->Rekening;
            $response = GetterSetter::getSaldoAwalTnpGab($dTgl, $cRekening);
            return response()->json($response);
        });

        // Dashboard
        Route::post('/dashboard/get', [DashboardController::class, 'data']);
        Route::post('/dashboard/get-kamar', [DashboardController::class, 'getDataKamarDigunakan']);
        // Tipe Kamar
        Route::post('/tipekamar/get', [TipeKamarController::class, 'data']);
        Route::post('/tipekamar/store', [TipeKamarController::class, 'store']);
        Route::post('/tipekamar/update', [TipeKamarController::class, 'update']);
        Route::post('/tipekamar/delete', [TipeKamarController::class, 'delete']);
        // Fasilitas Kamar
        Route::post('/fasilitaskamar/get', [FasilitasKamarController::class, 'data']);
        Route::post('/fasilitaskamar/store', [FasilitasKamarController::class, 'store']);
        Route::post('/fasilitaskamar/update', [FasilitasKamarController::class, 'update']);
        Route::post('/fasilitaskamar/delete', [FasilitasKamarController::class, 'delete']);
        // Kamar
        Route::post('/kamar/get', [KamarController::class, 'data']);
        Route::post('/kamar/store', [KamarController::class, 'store']);
        Route::post('/kamar/update', [KamarController::class, 'update']);
        Route::post('/kamar/delete', [KamarController::class, 'delete']);
        Route::post('/kamar/get-data', [KamarController::class, 'getDataKamar']);
        // Antrian
        Route::post('/antrian-meja/get', [AntrianMejaController::class, 'data']);
        Route::post('/antrian-meja/update', [AntrianMejaController::class, 'update']);
        // Rekening
        Route::post('/rekening/get', [RekeningController::class, 'data']);
        Route::post('/rekening/get_rekening', [RekeningController::class, 'getAll']);
        Route::post('/rekening/store', [RekeningController::class, 'store']);
        Route::post('/rekening/update', [RekeningController::class, 'update']);
        Route::post('/rekening/delete', [RekeningController::class, 'delete']);
        // Reservasi
        Route::post('/reservasi/store', [ReservasiController::class, 'store']);
        Route::post('/reservasi/delete', [ReservasiController::class, 'delete']);
        Route::post('/reservasi/get-data', [ReservasiController::class, 'getDataReservasi']);
        Route::post('/reservasi/checkout', [ReservasiController::class, 'checkout']);
        Route::post('/reservasi/laporan', [ReservasiController::class, 'laporan']);
        Route::post('/reservasi/laporan-user', [ReservasiController::class, 'laporanPerUser']);
        Route::post('/reservasi/get-pdf', [ReservasiController::class, 'getDataPdfReservasi']);
        // Pembayaran
        Route::post('/pembayaran/get', [PembayaranController::class, 'data']);
        Route::post('/pembayaran/getforbooking', [PembayaranController::class, 'dataBooking']);
        Route::post('/pembayaran/store', [PembayaranController::class, 'store']);
        Route::post('/pembayaran/update', [PembayaranController::class, 'update']);
        Route::post('/pembayaran/delete', [PembayaranController::class, 'delete']);
        // User Manager
        Route::post('/user/get', [UserController::class, 'data']);
        Route::post('/user/store', [UserController::class, 'store']);
        Route::post('/user/update', [UserController::class, 'update']);
        Route::post('/user/delete', [UserController::class, 'delete']);
        // Invoice
        Route::post('/invoice/store', [InvoiceController::class, 'store']);
        Route::post('/invoice/delete', [InvoiceController::class, 'delete']);
        Route::post('/invoice/pay', [InvoiceController::class, 'pay']);
        Route::post('/invoice/laporan', [InvoiceController::class, 'laporan']);
        Route::post('/invoice/checkout', [InvoiceController::class, 'checkout']);
        Route::post('/invoice/get-pdf', [InvoiceController::class, 'getDataPdfInvoice']);
        // Konfigurasi
        Route::post('/config/get', [ConfigController::class, 'data']);
        Route::post('/config/get-all', [ConfigController::class, 'dataAll']);
        Route::post('/config/store', [ConfigController::class, 'store']);
        // Jurnal
        Route::post('jurnal-lain/data', [JurnalLainController::class, 'data']);
        Route::post('jurnal-lain/store', [JurnalLainController::class, 'store']);
        Route::post('jurnal-lain/delete', [JurnalLainController::class, 'delete']);
        // Kas
        Route::post('kas/get', [KasController::class, 'data']);
        Route::post('kas/store', [KasController::class, 'store']);
        Route::post('kas/delete', [KasController::class, 'delete']);
        Route::post('kas/update', [KasController::class, 'update']);
        Route::post('kas/get_faktur', [KasController::class, 'getFaktur']);
        Route::post('kas/get-data/pengeluaran', [KasController::class, 'getDataEditPengeluaranKas']);
        Route::post('kas/get-data/penerimaan', [KasController::class, 'getDataEditPenerimaanKas']);
        Route::post('kas/getDataByFakturDebet', [KasController::class, 'getDataByFakturDebet']);
        Route::post('kas/getDataByFakturKredit', [KasController::class, 'getDataByFakturKredit']);
        // Posting
        Route::post('posting/jurnal', [JurnalController::class, 'store']);
        // Config Rekening
        Route::post('/config/pembukuan/get', [ConfigRekeningController::class, 'data']);
        Route::post('/config/pembukuan/store', [ConfigRekeningController::class, 'store']);
        // Laporan Akuntansi
        // Laporan Neraca
        Route::post('/laporan/neraca/get', [NeracaController::class, 'data']);
        Route::post('/laporan/laba-rugi/get', [LabaRugiController::class, 'data']);

        // GOLONGAN AKTIVA
        Route::post('golonganaktiva/get', [GolonganAktivaController::class, 'data']);
        Route::post('golonganaktiva/store', [GolonganAktivaController::class, 'store']);
        Route::post('golonganaktiva/delete', [GolonganAktivaController::class, 'delete']);
        Route::post('golonganaktiva/update', [GolonganAktivaController::class, 'update']);

        // golongan stock
        Route::post('golongan_stock/get', [GolonganStockController::class, 'data']);
        Route::post('golongan_stock/store', [GolonganStockController::class, 'store']);
        Route::post('golongan_stock/delete', [GolonganStockController::class, 'delete']);
        Route::post('golongan_stock/update', [GolonganStockController::class, 'update']);

        // ROUTE SATUAN STOCK
        Route::post('satuan_stock/get', [SatuanStockController::class, 'data']);
        Route::post('satuan_stock/store', [SatuanStockController::class, 'store']);
        Route::post('satuan_stock/delete', [SatuanStockController::class, 'delete']);
        Route::post('satuan_stock/update', [SatuanStockController::class, 'update']);

        // ROUTE GUDANG
        Route::post('gudang/get', [GudangController::class, 'data']);
        Route::post('gudang/store', [GudangController::class, 'store']);
        Route::post('gudang/delete', [GudangController::class, 'delete']);
        Route::post('gudang/update', [GudangController::class, 'update']);
        // ROUTE RAK
        Route::post('rak/get', [RakController::class, 'data']);
        Route::post('rak/store', [RakController::class, 'store']);
        Route::post('rak/delete', [RakController::class, 'delete']);
        Route::post('rak/update', [RakController::class, 'update']);

        // ROUTE JENIS SUPPLIER
        Route::post('jenis_supplier/get', [JenisSupplierController::class, 'data']);
        Route::post('jenis_supplier/store', [JenisSupplierController::class, 'store']);
        Route::post('jenis_supplier/delete', [JenisSupplierController::class, 'delete']);
        Route::post('jenis_supplier/update', [JenisSupplierController::class, 'update']);
        // ROUTE DAFTAR SUPPLIER
        Route::post('supplier/get', [DaftarSupplierController::class, 'data']);
        Route::post('supplier/store', [DaftarSupplierController::class, 'store']);
        Route::post('supplier/delete', [DaftarSupplierController::class, 'delete']);
        Route::post('supplier/update', [DaftarSupplierController::class, 'update']);
        // ROUTE STOCK SUPPLIER
        Route::post('stock_supplier/getdata_bysupplier', [StockSupplierController::class, 'getDataBySupplier']);
        Route::post('stock_supplier/get_barcode', [StockSupplierController::class, 'getBarcode']);
        Route::post('stock_supplier/store', [StockSupplierController::class, 'store']);
        Route::post('stock_supplier/delete', [StockSupplierController::class, 'delete']);

        // ROUTE STOCK
        Route::post('produk/get_kode', [ProdukController::class, 'getKode']);
        Route::post('produk/get', [ProdukController::class, 'data']);
        Route::post('produk/get-filter', [ProdukController::class, 'dataFilter']);
        Route::post('produk/get-barcode/kasir', [ProdukController::class, 'getBarcodeKasir']);
        Route::post('produk/get-barcode', [ProdukController::class, 'getBarcode']);
        Route::post('produk/get/grid', [ProdukController::class, 'dataGrid']);
        Route::post('produk/store', [ProdukController::class, 'store']);
        Route::post('produk/delete', [ProdukController::class, 'delete']);
        Route::post('produk/update', [ProdukController::class, 'update']);
        Route::post('produk/getdata_edit', [ProdukController::class, 'getDataEdit']);
        Route::post('produk/pricetag', [ProdukController::class, 'priceTag']);
        Route::post('produk/pricetag_disc', [ProdukController::class, 'insertDiscStok']);
        Route::post('produk/status-hapus', [ProdukController::class, 'updStatusHapus']);

        // ROUTE KASIR
        Route::post('kasir/get_barcode', [KasirController::class, 'getBarcode']);
        // Route::post('kasir/get_faktur', [KasirController::class, 'getNoNota']);
        // Route::post('kasir/store', [KasirController::class, 'store']);
        // Route::post('kasir/shift', [KasirController::class, 'getShift']);
        // Route::post('kasir/print-receipt', [PrintController::class, 'printReceipt']);

        // ROUTE PURCHASE ORDER
        Route::post('purchase_order/get_faktur', [PurchaseOrderController::class, 'getFaktur']);
        Route::post('purchase_order/get', [PurchaseOrderController::class, 'data']);
        Route::post('purchase_order/get_barcode', [PurchaseOrderController::class, 'getBarcode']);
        Route::post('purchase_order/store', [PurchaseOrderController::class, 'store']);
        Route::post('purchase_order/delete', [PurchaseOrderController::class, 'delete']);
        Route::post('purchase_order/getdata_edit', [PurchaseOrderController::class, 'getDataEdit']);
        Route::post('purchase_order/update', [PurchaseOrderController::class, 'update']);
        Route::post('purchase_order/reorder', [PurchaseOrderController::class, 'reorderPO']);
        Route::post('purchase_order/getdata_reorder', [PurchaseOrderController::class, 'getDataByReorderPO']);
        Route::post('purchase_order/repeat', [PurchaseOrderController::class, 'repeatPO']);
        Route::post('purchase_order/getdata_repeat', [PurchaseOrderController::class, 'getDataByRepeatPO']);
        Route::post('purchase_order/print_faktur', [PurchaseOrderController::class, 'print']);

        // ROUTE PEMBELIAN/PENERIMAAN BARANG
        Route::post('pembelian/get_faktur', [PembelianPenerimaanBarangController::class, 'getFaktur']);
        Route::post('pembelian/get_fakturpo', [PembelianPenerimaanBarangController::class, 'getDataFakturPO']);
        Route::post('pembelian/getdata_fakturpo', [PembelianPenerimaanBarangController::class, 'getDataByFakturPO']);
        Route::post('pembelian/get', [PembelianPenerimaanBarangController::class, 'data']);
        Route::post('pembelian/get_barcode', [PembelianPenerimaanBarangController::class, 'getBarcode']);
        Route::post('pembelian/store', [PembelianPenerimaanBarangController::class, 'store']);
        Route::post('pembelian/getdata_edit', [PembelianPenerimaanBarangController::class, 'getDataEdit']);
        Route::post('pembelian/update', [PembelianPenerimaanBarangController::class, 'update']);
        Route::post('pembelian/delete', [PembelianPenerimaanBarangController::class, 'delete']);
        Route::post('pembelian/print_faktur', [PembelianPenerimaanBarangController::class, 'print']);

        // ROUTE RETUR PEMBELIAN DENGAN FAKTUR
        Route::post('rtnpembelian_faktur/get_faktur', [ReturPembelianDenganFakturController::class, 'getFaktur']);
        Route::post('rtnpembelian_faktur/get_fakturpembelian', [ReturPembelianDenganFakturController::class, 'getFakturPembelian']);
        Route::post('rtnpembelian_faktur/getdata_faktur', [ReturPembelianDenganFakturController::class, 'getDataByFakturPembelian']);
        Route::post('rtnpembelian_faktur/get', [ReturPembelianDenganFakturController::class, 'data']);
        Route::post('rtnpembelian_faktur/get_barcode', [ReturPembelianDenganFakturController::class, 'getBarcode']);
        Route::post('rtnpembelian_faktur/store', [ReturPembelianDenganFakturController::class, 'store']);
        Route::post('rtnpembelian_faktur/getdata_edit', [ReturPembelianDenganFakturController::class, 'getDataEdit']);
        Route::post('rtnpembelian_faktur/update', [ReturPembelianDenganFakturController::class, 'update']);
        Route::post('rtnpembelian_faktur/delete', [ReturPembelianDenganFakturController::class, 'delete']);
        Route::post('rtnpembelian_faktur/print_faktur', [ReturPembelianDenganFakturController::class, 'print']);

        // ROUTE RETUR PEMBELIAN TANPA FAKTUR
        Route::post('rtnpembelian/get_faktur', [ReturPembelianTanpaFakturController::class, 'getFaktur']);
        Route::post('rtnpembelian/get', [ReturPembelianTanpaFakturController::class, 'data']);
        Route::post('rtnpembelian/get_barcode', [ReturPembelianTanpaFakturController::class, 'getBarcode']);
        Route::post('rtnpembelian/store', [ReturPembelianTanpaFakturController::class, 'store']);
        Route::post('rtnpembelian/getdata_edit', [ReturPembelianTanpaFakturController::class, 'getDataEdit']);
        Route::post('rtnpembelian/update', [ReturPembelianTanpaFakturController::class, 'update']);
        Route::post('rtnpembelian/delete', [ReturPembelianTanpaFakturController::class, 'delete']);

        // ROUTE PEMBAYARAN FAKTUR
        Route::post('pembayaran_faktur/get', [PembayaranFakturController::class, 'data']);
        Route::post('pembayaran_faktur/get_faktur', [PembayaranFakturController::class, 'getFaktur']);
        Route::post('pembayaran_faktur/getdata_bysupplier', [PembayaranFakturController::class, 'getDataBySupplier']);
        Route::post('pembayaran_faktur/store', [PembayaranFakturController::class, 'store']);
        Route::post('pembayaran_faktur/delete', [PembayaranFakturController::class, 'delete']);

        // AKTIVA
        Route::post('aktiva/get', [AktivaController::class, 'data']);
        Route::post('aktiva/store', [AktivaController::class, 'store']);
        Route::post('aktiva/getdata_edit', [AktivaController::class, 'getDataEdit']);
        Route::post('aktiva/delete', [AktivaController::class, 'delete']);
        Route::post('aktiva/update', [AktivaController::class, 'update']);

        Route::post('posting/aktiva/data', [PostingAktivaController::class, 'data']);
        Route::post('posting/aktiva/store', [PostingAktivaController::class, 'store']);

        // STOCK OPNAME
        Route::post('stock-opname/data', [StockOpnameController::class, 'data']);
        Route::post('stock-opname/store', [StockOpnameController::class, 'store']);
        Route::post('stock-opname/update', [StockOpnameController::class, 'update']);
        Route::post('stock-opname/proses-adjustment', [StockOpnameController::class, 'prosesAdjustment']);
        Route::post('stock-opname/batal-adjustment', [StockOpnameController::class, 'batalAdjustment']);
        Route::post('stock-opname/posting-adjustment', [StockOpnameController::class, 'postingJurnalStockOpname']);

        // ROUTE SHIFT KASIR
        Route::post('shift_kasir/get_faktur', [ShiftKasirController::class, 'getFaktur']);
        Route::post('shift_kasir/select_kasir', [ShiftKasirController::class, 'selectKasir']);
        Route::post('shift_kasir/select_shift', [ShiftKasirController::class, 'selectShift']);
        Route::post('shift_kasir/get', [ShiftKasirController::class, 'data']);
        Route::post('shift_kasir/store', [ShiftKasirController::class, 'store']);
        Route::post('shift_kasir/select_sesi', [ShiftKasirController::class, 'selectSesi']);
        Route::post('shift_kasir/sesi_fullname', [ShiftKasirController::class, 'sesiFullName']);
        Route::post('shift_kasir/closing', [ShiftKasirController::class, 'closing']);
        // ROUTE KASIR
        Route::post('kasir/get_barcode', [KasirController::class, 'getBarcode']);
        Route::post('kasir/get_faktur', [KasirController::class, 'getNoNota']);
        Route::post('kasir/get_pemesanan', [KasirController::class, 'getCaraPemesanan']);
        Route::post('kasir/store', [KasirController::class, 'store']);
        Route::post('kasir/shift', [KasirController::class, 'getShift']);

        // ROUTE UANG PECAHAN
        Route::post('uang_pecahan/get', [UangPecahanController::class, 'data']);
        Route::post('uang_pecahan/store', [UangPecahanController::class, 'store']);
        Route::post('uang_pecahan/delete', [UangPecahanController::class, 'delete']);
        Route::post('uang_pecahan/update', [UangPecahanController::class, 'update']);

        // ROUTE REKAP DATA KASIR
        Route::post('rekapkasir/get_total', [RekapKasirController::class, 'getTotal']);
        Route::post('rekapkasir/get_uangpecahan', [RekapKasirController::class, 'getUangPecahan']);
        Route::post('rekapkasir/save', [RekapKasirController::class, 'save']);
        Route::post('rekapkasir/get_penjualanBySesi', [RekapKasirController::class, 'get_penjualanBySesi']);
        Route::post('rekapkasir/closing_shift', [RekapKasirController::class, 'closingShift']);

        // ROUTE REKAP DATA KASIR
        Route::post('rekapkasir/hotel/get_total', [RekapKasirHotelController::class, 'getTotal']);
        Route::post('rekapkasir/hotel/get_uangpecahan', [RekapKasirHotelController::class, 'getUangPecahan']);
        Route::post('rekapkasir/hotel/save', [RekapKasirHotelController::class, 'save']);
        Route::post('rekapkasir/hotel/get_penjualanBySesi', [RekapKasirHotelController::class, 'get_penjualanBySesi']);
        Route::post('rekapkasir/hotel/closing_shift', [RekapKasirHotelController::class, 'closingShift']);
        // ROUTE BAYAR
        Route::post('bayar/get_data', [BayarController::class, 'getDataEdit']);
        // ROUTE RETUR
        Route::post('retur/get_faktur', [ReturController::class, 'getFaktur']);
        Route::post('retur/cari_faktur', [ReturController::class, 'cariFaktur']);
        Route::post('retur/store', [ReturController::class, 'store']);
        // ROUTE REPRINT
        Route::post('reprint/get_faktur', [ReprintController::class, 'getFaktur']);
        Route::post('reprint/cari_faktur', [ReprintController::class, 'cariFaktur']);
        Route::post('reprint/have_member', [ReprintController::class, 'haveMember']);
        // ROUTE KASIR TMP    - HOLD -
        Route::post('kasir_tmp/store', [KasirTmpController::class, 'store']);
        Route::post('kasir_tmp/get_faktur', [KasirTmpController::class, 'getFaktur']);
        Route::post('kasir_tmp/detail_faktur', [KasirTmpController::class, 'detailFaktur']);
        Route::post('kasir_tmp/delete_faktur', [KasirTmpController::class, 'deleteFaktur']);
        // ROUTE Perubahan harga MASTER
        Route::post('perubahan_harga_stock/store', [PerubahanHargaStockController::class, 'store']);
        // ROUTE SHIFT MASTER
        Route::post('shift/get', [ShiftController::class, 'data']);
        Route::post('shift/store', [ShiftController::class, 'store']);
        Route::post('shift/delete', [ShiftController::class, 'delete']);
        Route::post('shift/update', [ShiftController::class, 'update']);
        // ROUTE DISKON PERIODE
        Route::post('diskon_periode/get', [DiskonPeriodeController::class, 'data']);
        Route::post('diskon_periode/get_cetak', [DiskonPeriodeController::class, 'dataCetak']);
        Route::post('diskon_periode/get_bykode', [DiskonPeriodeController::class, 'getDataCetak']);
        Route::post('diskon_periode/store', [DiskonPeriodeController::class, 'store']);
        Route::post('diskon_periode/update', [DiskonPeriodeController::class, 'update']);
        Route::post('diskon_periode/delete', [DiskonPeriodeController::class, 'delete']);
        Route::post('diskon_periode/print', [DiskonPeriodeController::class, 'print']);
        //laporan

        //transaksi hotel
        Route::post('laporan/transaksi/get-all', [LaporanTransaksiController::class, 'data']);

        //cetak-faktur
        Route::post('kasir/cetak-faktur/', [CetakFakturController::class, 'data']);
        Route::post('kasir/cetak-faktur/getdata_byfaktur', [CetakFakturController::class, 'getDataByFaktur']);

        // Laporan Perubahan Harga Stock
        Route::post('laporan/stock/perubahan-harga', [LapPerubahanHargaStockController::class, 'data']);
        Route::post('laporan/stock/data-faktur', [LapPerubahanHargaStockController::class, 'dataFaktur']);

        // LAPORAN PENJUALAN KASIR
        Route::post('laporan/kasir/get', [LapKasirController::class, 'data']);
        Route::post('laporan/kasir/getdata_byfaktur', [LapKasirController::class, 'getDataByFaktur']);

        // LAPORAN PENJUALAN KASIR
        Route::post('laporan/daftar-penjualan/get', [LapDaftarPenjualanController::class, 'data']);
        Route::post('laporan/daftar-penjualan/getdata_byfaktur', [LapDaftarPenjualanController::class, 'getDataByFaktur']);
        Route::post('laporan/penjualan/per-barang', [PenjualanPerBarangController::class, 'data']);

        // LAPORAN DAFTAR PEMBELIAN
        Route::post('laporan/pembelian/daftar', [DaftarPembelianController::class, 'data']);
        Route::post('laporan/pembelian/store', [DaftarPembelianController::class, 'store']);
        Route::post('laporan/pembelian/update', [DaftarPembelianController::class, 'update']);

        // LAPORAN TRANSAKSI STOCK
        Route::post('laporan/transaksi-stock/nilai-persediaan', [NilaiPersediaanController::class, 'data']);
        Route::post('laporan/transaksi-stock/nilai-persediaan/cetak', [NilaiPersediaanController::class, 'exportPDF']);
        // LAPORAN REKAPITULASI INVENTORI
        Route::post('laporan/transaksi-stock/inventori', [InventoriController::class, 'data']);
        Route::post('laporan/transaksi-stock/inventori/excel', [InventoriController::class, 'dataExcel']);
        // LAPORAN SISA STOCK
        Route::post('laporan/stock/sisa', [LapSisaStockController::class, 'data']);

        // LAPORAN BUKU BESAR
        Route::post('buku-besar/get-total', [BukuBesarController::class, 'dataTotal']);
        Route::post('buku-besar/get-detail', [BukuBesarController::class, 'dataDetail']);
    });
});

// Route::middleware(['upload.token', 'check.token', 'change.database'])->group();
Route::post('posting/jurnal/cronjob', [JurnalController::class, 'store']);
