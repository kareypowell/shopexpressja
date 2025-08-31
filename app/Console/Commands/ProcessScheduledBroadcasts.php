<?php

namespace App\Console\Commands;

use App\Services\BroadcastMessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledBroadcasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:process-scheduled 
                            {--dry-run : Show what would be processed without actually sending}
                            {--limit=50 : Maximum number of broadcasts to process in one run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled broadcast messages that are due to be sent';

    /**
     * The broadcast message service instance.
     *
     * @var BroadcastMessageService
     */
    protected $broadcastService;

    /**
     * Create a new command instance.
     *
     * @param BroadcastMessageService $broadcastService
     */
    public function __construct(BroadcastMessageService $broadcastService)
    {
        parent::__construct();
        $this->broadcastService = $broadcastService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);
        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Starting scheduled broadcast processing...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No broadcasts will actually be sent');
        }

        try {
            // Log the start of processing
            Log::info('Scheduled broadcast processing started', [
                'dry_run' => $isDryRun,
                'limit' => $limit,
                'started_at' => now()->toDateTimeString()
            ]);

            if ($isDryRun) {
                $result = $this->performDryRun($limit);
            } else {
                $result = $this->broadcastService->processScheduledBroadcasts();
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            if ($result['success']) {
                $this->info("✅ Processing completed successfully in {$executionTime}s");
                $this->info("📊 Processed: {$result['processed_count']} broadcasts");
                
                if ($result['failed_count'] > 0) {
                    $this->warn("⚠️  Failed: {$result['failed_count']} broadcasts");
                    
                    if (!empty($result['errors'])) {
                        $this->error('Errors encountered:');
                        foreach ($result['errors'] as $error) {
                            $this->error("  • {$error}");
                        }
                    }
                }

                // Log successful completion
                Log::info('Scheduled broadcast processing completed', [
                    'processed_count' => $result['processed_count'],
                    'failed_count' => $result['failed_count'],
                    'execution_time' => $executionTime,
                    'dry_run' => $isDryRun
                ]);

                return Command::SUCCESS;
            } else {
                $this->error("❌ Processing failed: {$result['message']}");
                
                Log::error('Scheduled broadcast processing failed', [
                    'error' => $result['message'],
                    'execution_time' => $executionTime,
                    'dry_run' => $isDryRun
                ]);

                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $this->error("❌ Command failed with exception: {$e->getMessage()}");
            
            Log::error('Scheduled broadcast processing command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time' => $executionTime,
                'dry_run' => $isDryRun
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Perform a dry run to show what would be processed
     *
     * @param int $limit
     * @return array
     */
    protected function performDryRun(int $limit): array
    {
        try {
            $dueBroadcasts = \App\Models\BroadcastMessage::where('status', 'scheduled')
                ->where('scheduled_at', '<=', now())
                ->limit($limit)
                ->with(['sender', 'recipients.customer'])
                ->get();

            $this->info("📋 Found {$dueBroadcasts->count()} scheduled broadcasts due for processing:");

            $processedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($dueBroadcasts as $broadcast) {
                try {
                    $recipients = $this->broadcastService->getRecipients($broadcast);
                    $recipientCount = $recipients->count();

                    $this->line("  📧 Broadcast #{$broadcast->id}:");
                    $this->line("     Subject: {$broadcast->subject}");
                    $this->line("     Sender: {$broadcast->sender->name}");
                    $this->line("     Recipients: {$recipientCount}");
                    $this->line("     Scheduled: {$broadcast->scheduled_at->format('Y-m-d H:i:s')}");
                    $this->line("     Status: Would be sent ✅");
                    $this->line("");

                    $processedCount++;

                } catch (\Exception $e) {
                    $this->error("  ❌ Broadcast #{$broadcast->id}: {$e->getMessage()}");
                    $failedCount++;
                    $errors[] = "Broadcast {$broadcast->id}: " . $e->getMessage();
                }
            }

            return [
                'success' => true,
                'message' => "Dry run completed",
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Dry run failed: ' . $e->getMessage(),
                'processed_count' => 0,
                'failed_count' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }
}