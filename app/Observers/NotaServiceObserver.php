<?php

namespace App\Observers;

use App\Enums\QueueState;
use App\Models\service\NotaService;
use App\Models\service\Queue;

class NotaServiceObserver
{
    /**
     * Handle the NotaService "created" event.
     */
    public function created(NotaService $notaService): void
    {
        $price = $notaService->HARGA > $notaService->ESTIMASIHARGA ? $notaService->HARGA : $notaService->ESTIMASIHARGA;
        $total = ($price * 0.11) - $notaService->NOMINALBAYAR;

        if ($total <= 0) {
            return;
        }

        $latestAntrian = Queue::whereDate('created_at', \Carbon\Carbon::today())
            ->max('number') ?: 0;
        $latestAntrian += 1;

        $notaService->queue()->create([
            'number' => $latestAntrian,
            'state' => QueueState::WAITING,
        ]);
    }

    /**
     * Handle the NotaService "updated" event.
     */
    public function updated(NotaService $notaService): void
    {
        $price = $notaService->HARGA > $notaService->ESTIMASIHARGA ? $notaService->HARGA : $notaService->ESTIMASIHARGA;
        $total = ($price * 0.11) - $notaService->NOMINALBAYAR;

        if ($total <= 0) {
            $notaService->queue()->update([
                'state' => QueueState::DONE,
            ]);
            return;
        }

        if ($notaService->queue) {
            return;
        }

        $latestAntrian = Queue::whereDate('created_at', \Carbon\Carbon::today())
            ->max('number') ?: 0;
        $latestAntrian += 1;

        $notaService->queue()->create([
            'number' => $latestAntrian,
            'state' => QueueState::WAITING,
        ]);
    }

    /**
     * Handle the NotaService "deleted" event.
     */
    public function deleted(NotaService $notaService): void
    {
        $notaService->queue()->delete();
    }

    /**
     * Handle the NotaService "restored" event.
     */
    public function restored(NotaService $notaService): void
    {
        //
    }

    /**
     * Handle the NotaService "force deleted" event.
     */
    public function forceDeleted(NotaService $notaService): void
    {
        $notaService->queue()->delete();
    }
}
