<?php

namespace App\Observers;

use App\Models\Queue;
use App\Models\Invoice;
use App\Enums\QueueState;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $price = $invoice->subtotal * (1 + $invoice->tax / 100);
        $total = $price - $invoice->amount_paid;

        if ($total <= 0) {
            return;
        }

        $latestAntrian = Queue::whereDate('created_at', \Carbon\Carbon::today())
            ->max('number') ?: 0;
        $latestAntrian += 1;

        $invoice->queue()->create([
            'number' => $latestAntrian,
            'state' => QueueState::WAITING,
        ]);
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $price = $invoice->subtotal * (1 + $invoice->tax / 100);
        $total = $price - $invoice->amount_paid;

        if ($total <= 0) {
            $invoice->queue()->update([
                'state' => QueueState::DONE,
            ]);
            return;
        }

        if ($invoice->queue || !$invoice->queued) {
            return;
        }

        $latestAntrian = Queue::whereDate('created_at', \Carbon\Carbon::today())
            ->max('number') ?: 0;
        $latestAntrian += 1;

        $invoice->queue()->create([
            'number' => $latestAntrian,
            'state' => QueueState::WAITING,
        ]);
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        $invoice->queue()->delete();
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        $invoice->queue()->delete();
    }
}
