<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishOpenApiCommand extends Command
{
    protected $signature = 'openapi:publish {--skip-generate : Copy existing spec without running scribe:generate}';

    protected $description = 'Generate Scribe OpenAPI spec and publish static copies for docs and frontend typegen';

    public function handle(): int
    {
        if (! $this->option('skip-generate')) {
            $this->call('scribe:generate', ['--force' => true]);
        }

        $source = storage_path('app/private/scribe/openapi.yaml');
        if (! File::exists($source)) {
            $this->error('OpenAPI spec not found at '.$source.'. Run scribe:generate first.');

            return self::FAILURE;
        }

        $targets = [
            public_path('openapi.yaml'),
            base_path('school-system-frontend/openapi.yaml'),
        ];

        foreach ($targets as $target) {
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
            $this->line('Published '.$target);
        }

        $this->info('OpenAPI spec published. Frontend types: npm run types:api');

        return self::SUCCESS;
    }
}
