<?php

namespace App\Services;

class BackupService
{
    protected string $tmpDir;

    public function __construct()
    {
        $this->tmpDir = WRITEPATH . 'tmp/';
        if (! is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }
    }

    /**
     * Create a full backup zip and return its absolute path.
     * The caller is responsible for streaming and deleting it.
     */
    public function createBackup(): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $sqlFile   = $this->tmpDir . 'backup-' . $timestamp . '.sql';
        $zipFile   = $this->tmpDir . 'backup-' . $timestamp . '.zip';

        // 1. Dump SQL
        $this->dumpSql($sqlFile);

        // 2. Package into zip
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create zip archive at: ' . $zipFile);
        }

        $zip->addFile($sqlFile, 'database.sql');
        $this->zipDirectory($zip, WRITEPATH . 'uploads/', 'uploads/');
        $zip->close();

        // Clean up the loose SQL file
        @unlink($sqlFile);

        return $zipFile;
    }

    /**
     * Return a list of existing backup zips in writable/tmp/, newest first.
     *
     * @return array<array{filename: string, size: string, created: string}>
     */
    public function listBackups(): array
    {
        $files = glob($this->tmpDir . 'backup-*.zip') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function (string $path): array {
            return [
                'filename' => basename($path),
                'size'     => $this->humanSize(filesize($path)),
                'created'  => date('Y-m-d H:i:s', filemtime($path)),
                'path'     => $path,
            ];
        }, $files);
    }

    /**
     * Delete a backup zip by filename (basename only — no path traversal).
     */
    public function deleteBackup(string $filename): bool
    {
        // Strict allowlist: only accept filenames matching backup-*.zip
        if (! preg_match('/^backup-[\d_-]+\.zip$/', $filename)) {
            return false;
        }
        $path = $this->tmpDir . $filename;
        if (is_file($path)) {
            return unlink($path);
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Dump every table in the database to a SQL file using CI4's DB connection.
     * No exec() or mysqldump needed.
     */
    private function dumpSql(string $outputFile): void
    {
        $db = db_connect();

        $fh = fopen($outputFile, 'w');
        if (! $fh) {
            throw new \RuntimeException('Cannot write SQL dump to: ' . $outputFile);
        }

        $dbName = $db->getDatabase();
        fwrite($fh, "-- Pubvana DB Backup\n");
        fwrite($fh, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "-- Database:  {$dbName}\n\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        // Get all table names
        $tables = [];
        $result = $db->query('SHOW TABLES');
        foreach ($result->getResultArray() as $row) {
            $tables[] = reset($row);
        }

        foreach ($tables as $table) {
            // CREATE TABLE
            $createResult = $db->query("SHOW CREATE TABLE `{$table}`");
            $createRow    = $createResult->getRowArray();
            $createSql    = $createRow['Create Table'] ?? array_values($createRow)[1];

            fwrite($fh, "-- Table: {$table}\n");
            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fh, $createSql . ";\n\n");

            // INSERT rows
            $rows = $db->query("SELECT * FROM `{$table}`")->getResultArray();
            if (! empty($rows)) {
                $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                fwrite($fh, "INSERT INTO `{$table}` ({$columns}) VALUES\n");

                $lastIdx = count($rows) - 1;
                foreach ($rows as $i => $row) {
                    $vals = array_map(function ($v) use ($db): string {
                        if ($v === null) return 'NULL';
                        return "'" . $db->escapeLikeString(str_replace("'", "''", $v)) . "'";
                    }, array_values($row));
                    $sep = ($i === $lastIdx) ? ';' : ',';
                    fwrite($fh, '  (' . implode(', ', $vals) . ')' . $sep . "\n");
                }
                fwrite($fh, "\n");
            }
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    /**
     * Recursively add a directory to an open ZipArchive.
     */
    private function zipDirectory(\ZipArchive $zip, string $dirPath, string $zipPrefix): void
    {
        if (! is_dir($dirPath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relative = $zipPrefix . str_replace($dirPath, '', $file->getPathname());
            $relative = str_replace('\\', '/', $relative); // normalise on Windows

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                // Skip index.html placeholder files
                if ($file->getFilename() === 'index.html') continue;
                $zip->addFile($file->getPathname(), $relative);
            }
        }
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) return round($bytes, 1) . ' ' . $unit;
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' TB';
    }
}
