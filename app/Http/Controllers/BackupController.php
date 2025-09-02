<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Carbon\Carbon;
use ZipArchive;

class BackupController extends Controller
{
    public function download()
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupName = "backup_{$timestamp}";
            $tempDir = storage_path("app/temp/{$backupName}");

            // Create temporary directory
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Get database configuration
            $databaseConfig = config('database.connections.' . config('database.default'));
            $connection = config('database.default');
            $backupDbFile = null;
            $sqlFile = null;

            // Handle database backup based on connection type
            if ($connection === 'sqlite') {
                // For SQLite, copy the database file directly
                $sqliteFile = $databaseConfig['database'];
                if (file_exists($sqliteFile)) {
                    $backupDbFile = "{$tempDir}/database.sqlite";
                    copy($sqliteFile, $backupDbFile);
                } else {
                    throw new \Exception('SQLite database file not found');
                }
            } else {
                // For MySQL and other SQL databases, use dump
                $host = $databaseConfig['host'];
                $port = $databaseConfig['port'];
                $database = $databaseConfig['database'];
                $username = $databaseConfig['username'];
                $password = $databaseConfig['password'];

                // Create MySQL dump
                $sqlFile = "{$tempDir}/database.sql";
                $mysqldumpCommand = [
                    'mysqldump',
                    '--host=' . $host,
                    '--port=' . $port,
                    '--user=' . $username,
                    '--password=' . $password,
                    '--single-transaction',
                    '--routines',
                    '--triggers',
                    $database
                ];

                $process = new Process($mysqldumpCommand);
                $process->setTimeout(300); // 5 minutes timeout
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                // Save the SQL dump to file
                file_put_contents($sqlFile, $process->getOutput());
            }

            // Copy storage folder if it exists
            $storageSource = public_path('storage');
            $storageDestination = "{$tempDir}/storage";

            if (is_dir($storageSource)) {
                $this->copyDirectory($storageSource, $storageDestination);
            }

            // Create ZIP file
            $zipFile = storage_path("app/{$backupName}.zip");
            $zip = new ZipArchive();

            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Cannot create zip file');
            }

            // Add database backup to zip
            if ($connection === 'sqlite' && $backupDbFile && file_exists($backupDbFile)) {
                $zip->addFile($backupDbFile, 'database.sqlite');
            } elseif ($sqlFile && file_exists($sqlFile)) {
                $zip->addFile($sqlFile, 'database.sql');
            }

            // Add storage folder to zip recursively
            if (is_dir($storageDestination)) {
                $this->addDirectoryToZip($zip, $storageDestination, 'storage/');
            }

            $zip->close();

            // Clean up temporary directory
            $this->deleteDirectory($tempDir);

            // Return the zip file for download
            return response()->download($zipFile, "{$backupName}.zip")->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Clean up on error
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            if (isset($zipFile) && file_exists($zipFile)) {
                unlink($zipFile);
            }

            return response()->json([
                'error' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getRealPath(), strlen($source) + 1);
            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($item->getRealPath(), $targetPath);
            }
        }
    }

    private function addDirectoryToZip($zip, $directory, $zipPath = '')
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function deleteDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($directory);
    }
}
