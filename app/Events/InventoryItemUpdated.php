<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryItemUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Inventory $inventory
    ) {}
}

