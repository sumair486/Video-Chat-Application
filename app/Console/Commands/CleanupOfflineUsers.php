<?php

namespace App\Console\Commands;

use App\Services\UserStatusService;
use Illuminate\Console\Command;

class CleanupOfflineUsers extends Command
{
    protected $signature = 'users:cleanup-offline';
    protected $description = 'Cleanup offline users and broadcast status changes';

    protected UserStatusService $userStatusService;

    public function __construct(UserStatusService $userStatusService)
    {
        parent::__construct();
        $this->userStatusService = $userStatusService;
    }

    public function handle()
    {
        $this->userStatusService->cleanupOfflineUsers();
        $this->info('Offline users cleanup completed.');
        return 0;
    }
}