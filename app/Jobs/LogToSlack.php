<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogToSlack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $message;

    /**
     * Create a new job instance.
     *
     * @param  string  message
     * @return void
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel('slack')->info($this->message);
    }
}
