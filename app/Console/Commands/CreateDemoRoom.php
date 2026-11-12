<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Support\Ids;
use Illuminate\Console\Command;

class CreateDemoRoom extends Command
{
    protected $signature = 'chat:demo {--label=Demo room}';

    protected $description = 'Create a throw-away test room and print its IDs (refuses to run in production).';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to create demo rooms in production. Create real rooms from the operator console.');

            return self::FAILURE;
        }

        $chat = Ids::generate();
        $m1 = Ids::generate();
        $m2 = Ids::generate();

        $room = Room::create(['chat_hash' => Ids::hash($chat), 'label' => $this->option('label')]);
        $room->members()->create(['slot' => 1, 'id_hash' => Ids::hash($m1)]);
        $room->members()->create(['slot' => 2, 'id_hash' => Ids::hash($m2)]);

        $this->info('Demo room created (local test only):');
        $this->line("  Chat ID:      {$chat}");
        $this->line("  Member 1 ID:  {$m1}");
        $this->line("  Member 2 ID:  {$m2}");
        $this->line('  Secret phrase: pick any 10+ characters, the same one on both sides.');

        return self::SUCCESS;
    }
}
