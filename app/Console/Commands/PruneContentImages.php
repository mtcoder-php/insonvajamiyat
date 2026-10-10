<?php

namespace App\Console\Commands;

use App\Services\Content\ContentImageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:prune-content-images')]
#[Description("Matn muharririga yuklanib, hech qaysi yangilik/tadbirda ishlatilmagan rasmlarni o'chiradi")]
class PruneContentImages extends Command
{
    public function handle(ContentImageService $images): int
    {
        $this->info("O'chirildi: ".$images->pruneOrphans());

        return self::SUCCESS;
    }
}
