<?php

namespace Core45\LaravelPCaptcha\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'p-captcha:install
                           {--force : Overwrite existing files}
                           {--config : Only publish configuration}
                           {--assets : Only publish assets}
                           {--views : Only publish views}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Laravel P-CAPTCHA package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Installing Laravel P-CAPTCHA...');

        // Check what to install
        $configOnly = $this->option('config');
        $assetsOnly = $this->option('assets');
        $viewsOnly = $this->option('views');
        $force = $this->option('force');

        // If no specific option, install everything
        $installAll = !$configOnly && !$assetsOnly && !$viewsOnly;

        if ($configOnly || $installAll) {
            $this->publishConfiguration($force);
        }

        if ($assetsOnly || $installAll) {
            $this->publishAssets($force);
        }

        if ($viewsOnly || $installAll) {
            $this->publishViews($force);
        }

        if ($installAll) {
            $this->createDirectories();
            $this->updateGitIgnore();
            $this->showCompletionMessage();
        }

        $this->info('P-CAPTCHA installation completed!');

        return 0;
    }

    /**
     * Publish configuration files
     */
    protected function publishConfiguration(bool $force): void
    {
        $this->info('Publishing configuration...');

        $params = ['--tag' => 'p-captcha-config'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        // Check if config was published successfully
        if (File::exists(config_path('p-captcha.php'))) {
            $this->info('Configuration published to config/p-captcha.php');
        } else {
            $this->error('Failed to publish configuration');
        }
    }

    /**
     * Publish asset files
     */
    protected function publishAssets(bool $force): void
    {
        $this->info('Publishing assets...');

        $params = ['--tag' => 'p-captcha-assets'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        // Check if assets were published
        $cssPath = public_path('vendor/p-captcha/css/p-captcha.css');
        $jsPath = public_path('vendor/p-captcha/js/p-captcha.js');

        if (File::exists($cssPath) && File::exists($jsPath)) {
            $this->info('Assets published to public/vendor/p-captcha/');
        } else {
            $this->error('Failed to publish assets');
        }
    }

    /**
     * Publish view files
     */
    protected function publishViews(bool $force): void
    {
        $this->info('Publishing views...');

        $params = ['--tag' => 'p-captcha-views'];
        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        $viewPath = resource_path('views/vendor/p-captcha');
        if (File::exists($viewPath)) {
            $this->info('Views published to resources/views/vendor/p-captcha/');
        } else {
            $this->error('Failed to publish views');
        }
    }

    /**
     * Create necessary directories
     */
    protected function createDirectories(): void
    {
        $directories = [
            public_path('vendor/p-captcha'),
            public_path('vendor/p-captcha/css'),
            public_path('vendor/p-captcha/js'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("Created directory: {$directory}");
            }
        }
    }

    /**
     * Update .gitignore file
     */
    protected function updateGitIgnore(): void
    {
        $gitignorePath = base_path('.gitignore');

        if (!File::exists($gitignorePath)) {
            $this->warn('No .gitignore file found, skipping update');
            return;
        }

        $gitignoreContent = File::get($gitignorePath);
        $additions = [
            '# P-CAPTCHA cache files',
            '/storage/framework/cache/p-captcha*',
        ];

        $needsUpdate = false;
        foreach ($additions as $addition) {
            if (strpos($gitignoreContent, $addition) === false) {
                $needsUpdate = true;
                break;
            }
        }

        if ($needsUpdate) {
            if ($this->confirm('Add P-CAPTCHA entries to .gitignore?', true)) {
                $gitignoreContent .= "\n\n" . implode("\n", $additions) . "\n";
                File::put($gitignorePath, $gitignoreContent);
                $this->info('Updated .gitignore file');
            }
        }
    }

    /**
     * Show completion message with next steps
     */
    protected function showCompletionMessage(): void
    {
        $this->info('');
        $this->info('Laravel P-CAPTCHA has been installed successfully!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('1. Add @p-captcha directive to your forms');
        $this->info('2. Add ->middleware(\'p-captcha\') to your routes');
        $this->info('3. Customize config/p-captcha.php if needed');
        $this->info('');
        $this->info('Example usage:');
        $this->line('');
        $this->line('// In your Blade template:');
        $this->line('@p-captcha');
        $this->line('');
        $this->line('// In your routes:');
        $this->line('Route::post(\'/contact\', [ContactController::class, \'store\'])');
        $this->line('    ->middleware(\'p-captcha\');');
        $this->line('');
        $this->info('For more information, visit: https://github.com/core45/laravel-p-captcha');
    }

    /**
     * Check system requirements
     */
    protected function checkRequirements(): bool
    {
        $requirements = [
            'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'OpenSSL extension' => extension_loaded('openssl'),
            'Laravel 9+' => version_compare(app()->version(), '9.0.0', '>='),
        ];

        $allMet = true;
        $this->info('Checking system requirements...');

        foreach ($requirements as $requirement => $met) {
            if ($met) {
                $this->info("PASS: {$requirement}");
            } else {
                $this->error("FAIL: {$requirement}");
                $allMet = false;
            }
        }

        return $allMet;
    }

    /**
     * Test CAPTCHA installation
     */
    protected function testInstallation(): void
    {
        $this->info('Testing P-CAPTCHA installation...');

        try {
            // Test service resolution
            $service = app('p-captcha');
            $this->info('Service resolves correctly');

            // Test challenge generation
            $challenge = $service->generateChallenge();
            if (isset($challenge['id']) && isset($challenge['type'])) {
                $this->info('Challenge generation works');
            } else {
                $this->error('Challenge generation failed');
            }

            // Test configuration
            $config = config('p-captcha');
            if (is_array($config) && isset($config['challenge_types'])) {
                $this->info('Configuration loaded correctly');
            } else {
                $this->error('Configuration not loaded');
            }

        } catch (\Exception $e) {
            $this->error('Installation test failed: ' . $e->getMessage());
        }
    }
}
