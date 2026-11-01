<?php

namespace App\Console\Commands;

use App\Models\Dispute;
use App\Models\PaperAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizeEvidence extends Command
{
    protected $signature = 'evidence:privatize';

    protected $description = 'Move legacy public evidence to private local disk after verifying bytes';

    public function handle(): int
    {
        $count = 0;
        foreach ([[Dispute::class, 'evidence_file_path'], [PaperAttendance::class, 'scan_file_path']] as [$class,$field]) {
            foreach ($class::whereNotNull($field)->cursor() as $row) {
                $p = $row->$field;
                if (! Storage::disk('public')->exists($p)) {
                    continue;
                }$bytes = Storage::disk('public')->get($p);
                if (Storage::disk('local')->exists($p) && hash('sha256', Storage::disk('local')->get($p)) !== hash('sha256', $bytes)) {
                    $this->error('Xung đột file '.$p);

                    return 1;
                }Storage::disk('local')->put($p, $bytes);
                if (hash('sha256', Storage::disk('local')->get($p)) !== hash('sha256', $bytes)) {
                    return 1;
                }Storage::disk('public')->delete($p);
                $count++;
            }
        }$this->info('Đã chuyển '.$count.' file; giữ nguyên đường dẫn trong CSDL.');

        return 0;
    }
}
