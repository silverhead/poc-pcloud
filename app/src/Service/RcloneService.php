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