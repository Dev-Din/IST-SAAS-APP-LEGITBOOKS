<?php

namespace App\Jobs;

use App\Models\CsvImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $jobId) {}

    public function handle(): void
    {
        $job = CsvImportJob::find($this->jobId);

        if (! $job) {
            return;
        }

        $job->update(['status' => 'processing']);

        // Placeholder for actual CSV processing logic
        if (Storage::disk('local')->exists($job->file_path)) {
            $job->update([
                'status' => 'completed',
                'processed_rows' => $job->total_rows,
                'successful_rows' => $job->total_rows,
            ]);
        } else {
            $job->update([
                'status' => 'failed',
                'errors' => ['File not found'],
            ]);
        }
    }
}
