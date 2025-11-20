<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateRoomStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-room-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $vaKamar = DB::table('kamar')
            ->where('status', 1)
            ->get();

        foreach ($vaKamar as $k) {

            $last = DB::table('detail_invoice')
                ->where('no_kamar', $k->kode_kamar)
                ->orderByDesc('tgl_checkout')
                ->first();

            if ($last && Carbon::parse($last->tgl_checkout)->lt(now())) {
                DB::table('kamar')
                    ->where('kode_kamar', $k->kode_kamar)
                    ->update(['status' => 0]);
            }
        }
    }
}
