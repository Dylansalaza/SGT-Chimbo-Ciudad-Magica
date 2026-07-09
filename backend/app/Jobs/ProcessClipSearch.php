<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ProcessClipSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 
    /** @var int ID del registro en image_searches */
    public int $searchId;
 
    public function __construct(int $searchId)
    {
        $this->searchId = $searchId;
    }
 
    /**
     * El handle está vacío a propósito:
     * Python lee el payload serializado de la tabla `jobs`
     * y extrae $this->searchId desde el campo `command`.
     */
    public function handle(): void {}
}