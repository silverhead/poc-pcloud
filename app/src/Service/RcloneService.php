<?php

namespace App\Service;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RcloneService
{
    private string $localBasePath;
    private string $pcloudBasePath;
    private string $rcloneConfigPath;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    )
    {
        // Configuration par défaut - à personnaliser via variables d'environnement
        $this->localBasePath = $parameterBag->get('app.rclone.local_base_path');
        $this->pcloudBasePath = $parameterBag->get('app.rclone.pcloud_base_path');
        $this->rcloneConfigPath = $parameterBag->get('app.rclone.rclone_config_path');
    }

    /**
     * Liste les fichiers et dossiers locaux
     */
    public function listLocalFiles(string $path = ''): array
    {
        $fullPath = $this->localBasePath . '/' . ltrim($path, '/');
        
        if (!is_dir($fullPath) || !is_readable($fullPath)) {
            return [];
        }

        $files = [];
        $items = scandir($fullPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $itemPath = $fullPath . '/' . $item;
            $relativePath = ltrim($path . '/' . $item, '/');
            
            $files[] = [
                'name' => $item,
                'path' => $relativePath,
                'type' => is_dir($itemPath) ? 'directory' : 'file',
                'size' => is_file($itemPath) ? filesize($itemPath) : 0,
                'modified' => filemtime($itemPath),
                'readable' => is_readable($itemPath)
            ];
        }
        
        // Tri : dossiers d'abord, puis par nom
        usort($files, function($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });
        
        return $files;
    }

    /**
     * Liste les fichiers et dossiers pCloud via rclone
     */
    public function listPcloudFiles(string $path = ''): array
    {
        $remotePath = 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($path, '/');
        
        $command = ['rclone', 'lsjson', '--no-modtime', '--no-mimetype', $remotePath];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        $process = new Process($command);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        
        $output = $process->getOutput();
        $items = json_decode($output, true) ?? [];
        
        $files = [];
        foreach ($items as $item) {
            $files[] = [
                'name' => $item['Name'],
                'path' => ltrim($path . '/' . $item['Name'], '/'),
                'type' => $item['IsDir'] ? 'directory' : 'file',
                'size' => $item['Size'] ?? 0,
                'modified' => isset($item['ModTime']) ? strtotime($item['ModTime']) : time()
            ];
        }
        
        // Tri : dossiers d'abord, puis par nom
        usort($files, function($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });
        
        return $files;
    }

    /**
     * Synchronise des fichiers du serveur vers pCloud
     */
    public function syncToCloud(string $localPath, string $remotePath): array
    {
        $sourcePath = $this->localBasePath . '/' . ltrim($localPath, '/');
        $destPath = 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($remotePath, '/');
        
        $command = ['rclone', 'sync', $sourcePath, $destPath, '--progress', '--stats', '1s'];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        $process = new Process($command);
        $process->setTimeout(3600); // 1 heure de timeout
        $process->run();
        
        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ];
    }

    /**
     * Synchronise des fichiers de pCloud vers le serveur
     */
    public function syncFromCloud(string $remotePath, string $localPath): array
    {
        $sourcePath = 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($remotePath, '/');
        $destPath = $this->localBasePath . '/' . ltrim($localPath, '/');
        
        $command = ['rclone', 'sync', $sourcePath, $destPath, '--progress', '--stats', '1s'];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        $process = new Process($command);
        $process->setTimeout(3600); // 1 heure de timeout
        $process->run();
        
        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ];
    }

    /**
     * Copie des fichiers (sans suppression à la destination)
     */
    public function copyToCloud(string $localPath, string $remotePath): array
    {
        $sourcePath = $this->localBasePath . '/' . ltrim($localPath, '/');
        $destPath = 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($remotePath, '/');
        
        $command = ['rclone', 'copy', $sourcePath, $destPath, '--progress', '--stats', '1s'];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();
        
        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ];
    }

    /**
     * Supprime un fichier ou dossier sur pCloud
     */
    public function deleteFromCloud(string $remotePath): array
    {
        $fullRemotePath = 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($remotePath, '/');
        
        // Utiliser 'rclone deletefile' pour les fichiers et 'rclone purge' pour les dossiers
        // On commence par tenter de supprimer comme un dossier, puis comme un fichier si ça échoue
        $command = ['rclone', 'purge', $fullRemotePath];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes de timeout pour les suppressions
        $process->run();
        
        // Si la suppression comme dossier échoue, essayer comme fichier
        if (!$process->isSuccessful()) {
            $command = ['rclone', 'deletefile', $fullRemotePath];
            
            if ($this->rcloneConfigPath) {
                $command[] = '--config';
                $command[] = $this->rcloneConfigPath;
            }
            
            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();
        }
        
        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ];
    }

    /**
     * Supprime plusieurs fichiers ou dossiers sur pCloud
     */
    public function deleteMultipleFromCloud(array $remotePaths): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($remotePaths as $remotePath) {
            $result = $this->deleteFromCloud($remotePath);
            $results[] = [
                'path' => $remotePath,
                'success' => $result['success'],
                'error' => $result['error']
            ];
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        return [
            'success' => $errorCount === 0,
            'results' => $results,
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'summary' => $errorCount === 0 ? 
                "Tous les éléments ont été supprimés avec succès" : 
                "{$successCount} supprimés avec succès, {$errorCount} erreurs"
        ];
    }

    /**
     * Synchronise avec progression en temps réel (Server-Sent Events)
     */
    public function syncToCloudWithProgress(array $localPaths, string $remotePath, callable $progressCallback = null): \Generator
    {
        $sourcePaths = array_map(fn($path) => $this->localBasePath . '/' . ltrim($path, '/'), $localPaths);
        $destPath = 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($remotePath, '/');
        
        // Construire la commande avec options de progression
        $command = [
            'rclone', 'sync', 
            implode(',', $sourcePaths), 
            $destPath, 
            '--progress', 
            '--stats=1s',
            '--stats-one-line',
            '--use-json-log'
        ];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        yield $this->executeWithProgress($command, $progressCallback);
    }

    /**
     * Copie vers pCloud avec progression en temps réel
     */
    public function copyToCloudWithProgress(array $localPaths, string $remotePath, callable $progressCallback = null): \Generator
    {
        $sourcePaths = array_map(fn($path) => $this->localBasePath . '/' . ltrim($path, '/'), $localPaths);
        $destPath = 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($remotePath, '/');
        
        $command = [
            'rclone', 'copy',
            implode(',', $sourcePaths),
            $destPath,
            '--progress',
            '--stats=1s',
            '--stats-one-line',
            '--use-json-log'
        ];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        yield $this->executeWithProgress($command, $progressCallback);
    }

    /**
     * Synchronise depuis pCloud avec progression en temps réel
     */
    public function syncFromCloudWithProgress(array $remotePaths, string $localPath, callable $progressCallback = null): \Generator
    {
        $sourcePaths = array_map(fn($path) => 'pcloud:' . $this->pcloudBasePath . '/' . ltrim($path, '/'), $remotePaths);
        $destPath = $this->localBasePath . '/' . ltrim($localPath, '/');
        
        $command = [
            'rclone', 'sync',
            implode(',', $sourcePaths),
            $destPath,
            '--progress',
            '--stats=1s',
            '--stats-one-line',
            '--use-json-log'
        ];
        
        if ($this->rcloneConfigPath) {
            $command[] = '--config';
            $command[] = $this->rcloneConfigPath;
        }
        
        yield $this->executeWithProgress($command, $progressCallback);
    }

    /**
     * Exécute une commande rclone avec parsing de progression en temps réel
     */
    private function executeWithProgress(array $command, callable $progressCallback = null): array
    {
        $process = new Process($command);
        $process->setTimeout(3600);
        
        $output = '';
        $error = '';
        $lastStats = null;
        
        $process->run(function ($type, $buffer) use (&$output, &$error, &$lastStats, $progressCallback) {
            if ($type === Process::ERR) {
                $error .= $buffer;
                
                // Parser la sortie d'erreur pour les statistiques
                $lines = explode("\n", $buffer);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $stats = $this->parseRcloneStats($line);
                    if ($stats) {
                        $lastStats = $stats;
                        if ($progressCallback) {
                            $progressCallback($stats);
                        }
                    }
                }
            } else {
                $output .= $buffer;
            }
        });
        
        return [
            'success' => $process->isSuccessful(),
            'output' => $output,
            'error' => $error,
            'finalStats' => $lastStats
        ];
    }

    /**
     * Parse les statistiques de rclone depuis une ligne de sortie
     */
    private function parseRcloneStats(string $line): ?array
    {
        // Patterns pour parser différents formats de rclone
        $patterns = [
            // Format: Transferred: 12.345M / 123.456M, 45%, 1.234M/s, ETA 1m23s
            '/Transferred:\s*([0-9.]+[KMGT]?B?)\s*\/\s*([0-9.]+[KMGT]?B?),\s*([0-9]+)%,\s*([0-9.]+[KMGT]?B?\/s),\s*ETA\s*([0-9hms]+)/',
            // Format: * filename.ext: 50% /12.34M, 1.23M/s, 30s
            '/\*\s*([^:]+):\s*([0-9]+)%\s*\/([0-9.]+[KMGT]?B?),\s*([0-9.]+[KMGT]?B?\/s),\s*([0-9hms]+)/',
            // Format: Checks: 123 / 456, 78%
            '/Checks:\s*([0-9]+)\s*\/\s*([0-9]+),\s*([0-9]+)%/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                if (count($matches) >= 6) {
                    // Format complet avec transfert
                    return [
                        'type' => 'transfer',
                        'transferred' => $matches[1],
                        'total' => $matches[2],
                        'percentage' => (int)$matches[3],
                        'speed' => $matches[4],
                        'eta' => $matches[5],
                        'rawLine' => $line
                    ];
                } elseif (count($matches) >= 4 && strpos($line, '*') === 0) {
                    // Format fichier individuel
                    return [
                        'type' => 'file',
                        'filename' => trim($matches[1]),
                        'percentage' => (int)$matches[2],
                        'size' => $matches[3],
                        'speed' => $matches[4],
                        'eta' => $matches[5] ?? '',
                        'rawLine' => $line
                    ];
                } elseif (count($matches) >= 4 && strpos($line, 'Checks:') !== false) {
                    // Format vérifications
                    return [
                        'type' => 'checks',
                        'completed' => (int)$matches[1],
                        'total' => (int)$matches[2],
                        'percentage' => (int)$matches[3],
                        'rawLine' => $line
                    ];
                }
            }
        }
        
        // Chercher le nom de fichier en cours de traitement
        if (preg_match('/^([^:]+):\s*(.+)$/', $line, $matches)) {
            $filename = trim($matches[1]);
            $status = trim($matches[2]);
            
            if (!empty($filename) && !str_contains($filename, 'Transferred') && !str_contains($filename, 'Checks')) {
                return [
                    'type' => 'current_file',
                    'filename' => $filename,
                    'status' => $status,
                    'rawLine' => $line
                ];
            }
        }
        
        return null;
    }

    /**
     * Obtient les informations de configuration
     */
    public function getConfig(): array
    {
        return [
            'localBasePath' => $this->localBasePath,
            'pcloudBasePath' => $this->pcloudBasePath,
            'rcloneConfigPath' => $this->rcloneConfigPath
        ];
    }

    /**
     * Met à jour la configuration
     */
    public function updateConfig(array $config): void
    {
        if (isset($config['localBasePath'])) {
            $this->localBasePath = $config['localBasePath'];
        }
        if (isset($config['pcloudBasePath'])) {
            $this->pcloudBasePath = $config['pcloudBasePath'];
        }
        if (isset($config['rcloneConfigPath'])) {
            $this->rcloneConfigPath = $config['rcloneConfigPath'];
        }
    }

    /**
     * Formate la taille en octets de manière lisible
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
} 