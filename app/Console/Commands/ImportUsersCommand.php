<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ImportUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import {url} {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a JSON URL up to a specified limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $limit = (int) $this->argument('limit');

        if ($limit <= 0) {
            $this->error('Limit must be a positive integer');

            return Command::FAILURE;
        }

        $this->info("Fetching users from: $url");
        $this->info("Import limit: $limit");

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->ok()) {
                $this->error("Failed to fetch data from URL with: {$response->body()}, status code: {$response->status()}");

                return Command::FAILURE;
            }

            $users = $response->json();

            if (! is_array($users) || ! is_array($users[0])) {
                $this->error('Invalid JSON format. Expected an array of users.');

                return Command::FAILURE;
            }

            $users = array_slice($users, 0, $limit);
            $importedCount = 0;
            $skippedCount = 0;

            $this->info('Starting import...');
            $progressBar = $this->output->createProgressBar(count($users));
            $progressBar->start();

            foreach ($users as $userData) {
                if (isset($userData['email']) && User::where('email', $userData['email'])->exists()) {
                    $skippedCount++;
                    $progressBar->advance();

                    continue;
                }

                // Create user
                User::create([
                    'name' => $userData['name'] ?? 'Unknown',
                    'email' => $userData['email'] ?? null,
                    'password' => Hash::make($userData['username']), // User's username as default password
                ]);

                $importedCount++;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info('Import completed successfully!');
            $this->info("Imported: {$importedCount} users");
            $this->info("Skipped (duplicates): {$skippedCount} users");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during import: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
