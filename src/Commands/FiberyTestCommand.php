<?php

namespace WMBH\Fibery\Commands;

use Illuminate\Console\Command;
use WMBH\Fibery\Exceptions\AuthenticationException;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\Fibery;

class FiberyTestCommand extends Command
{
    public $signature = 'fibery:test';

    public $description = 'Test the connection to Fibery API';

    public function handle(Fibery $fibery): int
    {
        $this->info('Testing Fibery API connection...');
        $this->newLine();

        // Check configuration
        $workspace = config('fibery.workspace');
        $token = config('fibery.token');

        if (empty($workspace)) {
            $this->error('FIBERY_WORKSPACE is not configured.');
            $this->line('Add FIBERY_WORKSPACE to your .env file.');

            return self::FAILURE;
        }

        if (empty($token)) {
            $this->error('FIBERY_TOKEN is not configured.');
            $this->line('Add FIBERY_TOKEN to your .env file.');

            return self::FAILURE;
        }

        $this->line("Workspace: <comment>{$workspace}</comment>");
        $this->line("API URL: <comment>{$fibery->getBaseUri()}</comment>");
        $this->newLine();

        try {
            $this->line('Fetching workspace schema...');

            $schema = $fibery->schema()->getSchema();
            $types = $fibery->schema()->getTypes();
            $spaces = $fibery->schema()->getSpaces();

            $this->info('Connection successful!');
            $this->newLine();

            $this->line('Workspace Statistics:');
            $this->line('  - Spaces: <comment>'.count($spaces).'</comment>');
            $this->line('  - Databases: <comment>'.count($types).'</comment>');

            if (count($spaces) > 0) {
                $this->newLine();
                $this->line('Available Spaces:');
                foreach (array_slice($spaces, 0, 10) as $space) {
                    $this->line("  - {$space}");
                }
                if (count($spaces) > 10) {
                    $this->line('  ... and '.(count($spaces) - 10).' more');
                }
            }

            return self::SUCCESS;
        } catch (AuthenticationException $e) {
            $this->error('Authentication failed!');
            $this->line('Please check your FIBERY_TOKEN is valid.');

            return self::FAILURE;
        } catch (FiberyException $e) {
            $this->error('Connection failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
