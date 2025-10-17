<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected static $status = [
        'SUKSES' => '00',
        'GAGAL' => '01',
        'PENDING' => '02',
        'NOT_FOUND' => '03',
        'BAD_REQUEST' => '99'
    ];

    protected static $menuBase = [
        [
            'label' => 'Home',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'pi pi-fw pi-home', 'to' => '/'],
            ]
        ],
        [
            'label' => 'Transaksi',
            'items' => [

                [
                    'label' => 'Master',
                    'icon' => 'pi pi-briefcase',
                    'items' => [
                        [
                            "label" => "Shift",
                            "icon" => "pi pi-fw pi-users",
                            "to" => "/master/shift"
                        ]
                    ],
                ],
                [
                    'label' => 'Transaksi',
                    'to' => '/transaksi',
                    'icon' => 'pi pi-shopping-cart'
                ],
                [
                    "label" => "Laporan",
                    "icon" => "pi pi-book",
                    "items" => [
                        [
                            "label" => "Laporan Transaksi Kasir",
                            "icon" => "pi pi-fw pi-list",
                            "to" => "/laporan/transaksi/kasir"
                        ],
                    ]
                ]
            ],
        ],
        [
            'label' => 'ANTRIAN',
            'items' => [
                [
                    'label' => 'Master',
                    'icon' => 'pi pi-briefcase',
                    'items' => [
                        ['label' => 'Antrian Meja', 'to' => '/master/antrian_meja', 'icon' => 'pi pi-briefcase'],
                    ]
                ],
            ]
        ],
        [
            'label' => 'MEJA',
            'items' => [
                [
                    'label' => 'Master',
                    'icon' => 'pi pi-briefcase',
                    'items' => [
                        ['label' => 'Meja', 'to' => '/master/meja', 'icon' => 'pi pi-briefcase'],
                        ['label' => 'Fasilitas', 'to' => '/master/fasilitas_kamar', 'icon' => 'pi pi-briefcase'],
                        ['label' => 'Tipe Kamar', 'to' => '/master/tipe_kamar', 'icon' => 'pi pi-briefcase'],
                        ['label' => 'Tipe Kamar', 'to' => '/master/tipe_kamar', 'icon' => 'pi pi-briefcase']
                    ]
                ],
                [
                    'label' => 'Laporan',
                    'icon' => 'pi pi-book',
                    'items' => [
                        ['label' => 'Reservasi', 'to' => '/laporan/reservasi', 'icon' => 'pi pi-book'],
                        ['label' => 'Invoice', 'to' => '/laporan/invoice', 'icon' => 'pi pi-book']
                    ]
                ]
            ]
        ],
        // [
        //     "label" => "Toko",
        //     "icon" => "pi pi-shopping-cart",
        //     "items" => [
        //         [
        //             "label" => "Penjualan",
        //             "icon" => "pi pi-shopping-cart",
        //             "items" => [
        //                 [
        //                     "label" => "Master",
        //                     "icon" => "pi pi-briefcase",
        //                     "items" => [
        //                         [
        //                             "label" => "Perubahan Harga",
        //                             "icon" => "pi pi-fw pi-tag",
        //                             "to" => "/master/inventori/perubahan_harga"
        //                         ],
        //                         [
        //                             "label" => "Diskon Periode",
        //                             "icon" => "pi pi-fw pi-percentage",
        //                             "to" => "/master/diskon-periode"
        //                         ],
        //                         [
        //                             "label" => "Shift",
        //                             "icon" => "pi pi-fw pi-users",
        //                             "to" => "/master/shift"
        //                         ],
        //                     ]
        //                 ],
        //                 [
        //                     "label" => "Penjualan Toko",
        //                     "icon" => "pi pi-wallet",
        //                     "to" => "/kasir"
        //                 ],
        //                 [
        //                     "label" => "Laporan",
        //                     "icon" => "pi pi-book",
        //                     "items" => [
        //                         [
        //                             "label" => "Laporan Penjualan Kasir",
        //                             "icon" => "pi pi-fw pi-list",
        //                             "to" => "/laporan/penjualan/kasir"
        //                         ],
        //                         [
        //                             "label" => "Laporan Daftar Penjualan",
        //                             "icon" => "pi pi-fw pi-list",
        //                             "to" => "/laporan/penjualan/daftar-penjualan"
        //                         ],
        //                         [
        //                             "label" => "Laporan Penjualan Per Barang",
        //                             "icon" => "pi pi-fw pi-inbox",
        //                             "to" => "/laporan/penjualan/penjualan-per-barang"
        //                         ],
        //                         [
        //                             "label" => "Laporan Perubahan Harga",
        //                             "icon" => "pi pi-shopping-cart",
        //                             "to" => "/laporan/laporan-stock/laporan-perubahan-harga"
        //                         ],
        //                     ]
        //                 ]
        //             ]
        //         ],
        //         [
        //             "label" => "Pembelian",
        //             "icon" => "pi pi-shopping-cart",
        //             "items" => [
        //                 [
        //                     "label" => "Master",
        //                     "icon" => "pi pi-briefcase",
        //                     "items" => [
        //                         [
        //                             "label" => "Produk",
        //                             "icon" => "pi pi-fw pi-briefcase",
        //                             "to" => "/master/inventori/produk"
        //                         ],
        //                         [
        //                             "label" => "Supplier",
        //                             "icon" => "pi pi-fw pi-shopping-cart",
        //                             "items" => [
        //                                 [
        //                                     "label" => "Jenis Supplier",
        //                                     "icon" => "pi pi-fw pi-paperclip",
        //                                     "to" => "/master/supplier/jenis_supplier"
        //                                 ],
        //                                 [
        //                                     "label" => "Daftar Supplier",
        //                                     "icon" => "pi pi-fw pi-list",
        //                                     "to" => "/master/supplier/daftar_supplier"
        //                                 ],
        //                                 [
        //                                     "label" => "Stock Supplier",
        //                                     "icon" => "pi pi-fw pi-list",
        //                                     "to" => "/master/supplier/stock_supplier"
        //                                 ]
        //                             ]
        //                         ]
        //                     ]
        //                 ],
        //                 [
        //                     "label" => "Transaksi",
        //                     "icon" => "pi pi-wallet",
        //                     "items" => [

        //                         [
        //                             "label" => "Purchase Order",
        //                             "icon" => "pi pi-fw pi-external-link",
        //                             "to" => "/pembelian/purchase-order"
        //                         ],
        //                         [
        //                             "label" => "Pembelian/Penerimaan Barang",
        //                             "icon" => "pi pi-fw pi-box",
        //                             "to" => "/pembelian/penerimaan-barang"
        //                         ],
        //                         [
        //                             "label" => "Retur Pembelian",
        //                             "icon" => "pi pi-fw pi-replay",
        //                             "to" => "/pembelian/retur"
        //                         ],
        //                         [
        //                             "label" => "Pembayaran Faktur",
        //                             "icon" => "pi pi-fw pi-money-bill",
        //                             "to" => "/pembelian/pembayaran-faktur"
        //                         ]
        //                     ]
        //                 ],
        //                 [
        //                     "label" => "Cetak Faktur",
        //                     "icon" => "pi pi-fw pi-print",
        //                     "to" => "/pembelian/cetak-faktur"
        //                 ],
        //                 [
        //                     "label" => "Laporan",
        //                     "icon" => "pi pi-book",
        //                     "items" => [
        //                         [
        //                             "label" => "Daftar Pembelian",
        //                             "icon" => "pi pi-fw pi-calculator",
        //                             "to" => "/laporan/laporan-pembelian/laporan-daftar-pembelian"
        //                         ]
        //                     ]
        //                 ]
        //             ]
        //         ],
        //         [
        //             "label" => "Stock",
        //             "icon" => "pi pi-box",
        //             "items" => [
        //                 [
        //                     "label" => "Master",
        //                     "icon" => "pi pi-briefcase",
        //                     "items" => [
        //                         [
        //                             "label" => "Golongan Stock",
        //                             "icon" => "pi pi-fw pi-folder",
        //                             "to" => "/master/stok/golongan_stok"
        //                         ],
        //                         [
        //                             "label" => "Satuan Stock",
        //                             "icon" => "pi pi-fw pi-paperclip",
        //                             "to" => "/master/stok/satuan_stok"
        //                         ],
        //                         [
        //                             "label" => "Gudang",
        //                             "icon" => "pi pi-fw pi-th-large",
        //                             "to" => "/master/stok/gudang"
        //                         ],
        //                         [
        //                             "label" => "Rak",
        //                             "icon" => "pi pi-fw pi-chart-bar",
        //                             "to" => "/master/stok/rak"
        //                         ]
        //                     ]
        //                 ],
        //                 [
        //                     "label" => "Transaksi",
        //                     "icon" => "pi pi-wallet",
        //                     "items" => [

        //                         [
        //                             "label" => "Stock Opname",
        //                             "icon" => "pi pi-fw pi-file-export",
        //                             "to" => "/transaksistock/stock-opname"
        //                         ]
        //                     ]
        //                 ],
        //                 [
        //                     "label" => "Laporan",
        //                     "icon" => "pi pi-book",
        //                     "items" => [

        //                         [
        //                             "label" => "Laporan Sisa Stock",
        //                             "icon" => "pi pi-shopping-bag",
        //                             "to" => "/laporan/laporan-stock/laporan-sisa-stock"
        //                         ],
        //                         [
        //                             "label" => "Laporan Nilai Persediaan",
        //                             "icon" => "pi pi-shopping-cart",
        //                             "to" => "/laporan/laporan-transaksi-stock/laporan-nilai-persediaan"
        //                         ],
        //                         [
        //                             "label" => "Laporan Rekapitulasi Inventori",
        //                             "icon" => "pi pi-box",
        //                             "to" => "/laporan/laporan-transaksi-stock/laporan-inventori"
        //                         ]
        //                     ],
        //                     [
        //                         "label" => "Posting Stock",
        //                         "icon" => "pi pi-circle",
        //                         "to" => "/posting/stock"
        //                     ]
        //                 ]
        //             ]
        //         ],
        //     ],
        // ],
        [
            'label' => 'Akuntansi',
            'items' => [
                [
                    'label' => 'Master',
                    'icon' => 'pi pi-briefcase',
                    'items' => [
                        ['label' => 'Golongan Aktiva', 'to' => '/master/golaktiva', 'icon' => 'pi pi-briefcase'],
                        ['label' => 'Pembayaran', 'to' => '/master/pembayaran', 'icon' => 'pi pi-briefcase'],
                        ['label' => 'Rekening', 'to' => '/master/rekening', 'icon' => 'pi pi-briefcase'],
                    ]
                ],
                [
                    'label' => 'Jurnal Lain-Lain',
                    'icon' => 'pi pi-wallet',
                    'to' => '/jurnal-lain'
                ],
                [
                    'label' => 'Aktiva',
                    'icon' => 'pi pi-building',
                    'to' => '/aktiva'
                ],
                [
                    'label' => 'Transaksi Kas',
                    'icon' => 'pi pi-wallet',
                    'to' => '/transaksikas'
                ],
                [
                    'label' => 'Laporan',
                    'icon' => 'pi pi-book',
                    'items' => [
                        ['label' => 'Neraca', 'to' => '/laporan/neraca', 'icon' => 'pi pi-book'],
                        ['label' => 'Laba Rugi', 'to' => '/laporan/laba-rugi', 'icon' => 'pi pi-book'],
                        ['label' => 'Buku Besar', 'to' => '/laporan/laporan-bukubesar', 'icon' => 'pi pi-book'],
                    ]
                ],
                [
                    'label' => 'Posting',
                    'icon' => 'pi pi-circle',
                    'items' => [
                        [
                            'label' => 'Aktiva',
                            'to' => '/posting/aktiva',
                            'icon' => 'pi pi-book'
                        ],
                        [
                            'label' => 'Jurnal',
                            'to' => '/posting/jurnal',
                            'icon' => 'pi pi-book'
                        ],

                    ]
                ],
            ]
        ],
        [
            'label' => 'Konfigurasi',
            'items' => [
                [
                    'label' => 'Config Rekening',
                    'icon' => 'pi pi-wallet',
                    'items' => [
                        [
                            'label' => 'Hotel Rekening',
                            'to' => '/master/rekening_config/hotel',
                            'icon' => 'pi pi-wallet',
                        ],
                        // [
                        //     'label' => 'Toko Rekening',
                        //     'to' => '/master/rekening_config/toko',
                        //     'icon' => 'pi pi-wallet',
                        // ],
                    ]
                ],
                [
                    'label' => 'Hotel Info',
                    'to' => '/master/hotel_config',
                    'icon' => 'pi pi-building'
                ]
            ]
        ],
        [
            'label' => 'User',
            'items' => [
                [
                    'label' => 'User Manager',
                    'to' => '/master/users',
                    'icon' => 'pi pi-users'
                ]
            ]
        ]
    ];
}
