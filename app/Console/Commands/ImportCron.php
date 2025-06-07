<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessShopifyImport;

class ImportCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for pending uploads and dispatch Shopify import jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Import Cron Job running at " . now());
        
        try {
            // Find uploads that are ready for processing
            $pendingUploads = DB::table('uploads')
                ->where('status', 'pending')
                ->orWhere(function($query) {
                    $query->where('status', 'processing')
                          ->where('updated_at', '<', now()->subMinutes(10)); // Resume stalled jobs
                })
                ->get();

            if ($pendingUploads->isEmpty()) {
                $this->info('No pending uploads found.');
                return;
            }

            foreach ($pendingUploads as $upload) {
                // Check if there are pending products for this upload
                $pendingProductsCount = DB::table('products')
                    ->where('upload_id', $upload->id)
                    ->where('import_status', 'pending')
                    ->count();

                if ($pendingProductsCount > 0) {
                    // Mark upload as processing
                    DB::table('uploads')
                        ->where('id', $upload->id)
                        ->update([
                            'status' => 'processing',
                            'updated_at' => now()
                        ]);

                    // Dispatch the job
                    ProcessShopifyImport::dispatch($upload->id);
                    
                    $this->info("Dispatched import job for upload ID: {$upload->id}");
                    Log::info("Dispatched Shopify import job for upload: {$upload->id}");
                } else {
                    // No pending products, mark as completed
                    DB::table('uploads')
                        ->where('id', $upload->id)
                        ->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'updated_at' => now()
                        ]);
                    
                    $this->info("Upload {$upload->id} marked as completed (no pending products)");
                }
            }

            // Optional: Clean up old completed/failed uploads
            //$this->cleanupOldUploads();

        } catch (\Exception $e) {
            $this->error('Import cron job failed: ' . $e->getMessage());
            Log::error('Import cron job failed: ' . $e->getMessage());
        }
    }

    /**
     * Clean up old uploads (optional)
     */
    private function cleanupOldUploads()
    {
        try {
            // Delete uploads older than 30 days that are completed or failed
            $deletedCount = DB::table('uploads')
                ->whereIn('status', ['completed', 'failed'])
                ->where('created_at', '<', now()->subDays(30))
                ->delete();

            if ($deletedCount > 0) {
                $this->info("Cleaned up {$deletedCount} old uploads");
                Log::info("Cleaned up {$deletedCount} old uploads");
            }
        } catch (\Exception $e) {
            Log::error('Failed to clean up old uploads: ' . $e->getMessage());
        }
    }
}