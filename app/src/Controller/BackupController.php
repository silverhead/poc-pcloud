<?php

namespace App\Controller;

use App\Service\RcloneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/backup', name: 'backup_')]
class BackupController extends AbstractController
{
    public function __construct(private RcloneService $rcloneService)
    {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $config = $this->rcloneService->getConfig();
        
        return $this->render('backup/index.html.twig', [
            'config' => $config
        ]);
    }

    #[Route('/local-files', name: 'local_files', methods: ['GET'])]
    public function localFiles(Request $request): JsonResponse
    {
        $path = $request->query->get('path', '');

        // try {
            $files = $this->rcloneService->listLocalFiles($path);
            return $this->json([
                'success' => true,
                'files' => $files,
                'currentPath' => $path
            ]);
        // } catch (\Exception $e) {
        //     return $this->json([
        //         'success' => false,
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }

    #[Route('/pcloud-files', name: 'pcloud_files', methods: ['GET'])]
    public function pcloudFiles(Request $request): JsonResponse
    {
        $path = $request->query->get('path', '');
        
        try {
            $files = $this->rcloneService->listPcloudFiles($path);
            return $this->json([
                'success' => true,
                'files' => $files,
                'currentPath' => $path
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sync-to-cloud', name: 'sync_to_cloud', methods: ['POST'])]
    public function syncToCloud(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $localPath = $data['localPath'] ?? '';
        $remotePath = $data['remotePath'] ?? '';
        
        if (empty($localPath) || empty($remotePath)) {
            return $this->json([
                'success' => false,
                'error' => 'Chemins local et distant requis'
            ], 400);
        }
        
        try {
            $result = $this->rcloneService->syncToCloud($localPath, $remotePath);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sync-from-cloud', name: 'sync_from_cloud', methods: ['POST'])]
    public function syncFromCloud(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $remotePath = $data['remotePath'] ?? '';
        $localPath = $data['localPath'] ?? '';
        
        if (empty($localPath) || empty($remotePath)) {
            return $this->json([
                'success' => false,
                'error' => 'Chemins local et distant requis'
            ], 400);
        }
        
        try {
            $result = $this->rcloneService->syncFromCloud($remotePath, $localPath);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/copy-to-cloud', name: 'copy_to_cloud', methods: ['POST'])]
    public function copyToCloud(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $localPath = $data['localPath'] ?? '';
        $remotePath = $data['remotePath'] ?? '';
        
        if (empty($localPath) || empty($remotePath)) {
            return $this->json([
                'success' => false,
                'error' => 'Chemins local et distant requis'
            ], 400);
        }
        
        try {
            $result = $this->rcloneService->copyToCloud($localPath, $remotePath);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/config', name: 'config', methods: ['GET', 'POST'])]
    public function config(Request $request): Response|JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->render('backup/config.html.twig', [
                'config' => $this->rcloneService->getConfig()
            ]);
        }
        
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            
            try {
                $this->rcloneService->updateConfig($data);
                return $this->json([
                    'success' => true,
                    'message' => 'Configuration mise à jour avec succès'
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        
        return $this->json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
    }

    #[Route('/delete-from-cloud', name: 'delete_from_cloud', methods: ['POST'])]
    public function deleteFromCloud(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $remotePath = $data['remotePath'] ?? '';
        
        if (empty($remotePath)) {
            return $this->json([
                'success' => false,
                'error' => 'Chemin distant requis'
            ], 400);
        }
        
        try {
            $result = $this->rcloneService->deleteFromCloud($remotePath);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/delete-multiple-from-cloud', name: 'delete_multiple_from_cloud', methods: ['POST'])]
    public function deleteMultipleFromCloud(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $remotePaths = $data['remotePaths'] ?? [];
        
        if (empty($remotePaths) || !is_array($remotePaths)) {
            return $this->json([
                'success' => false,
                'error' => 'Liste des chemins distants requise'
            ], 400);
        }
        
        try {
            $result = $this->rcloneService->deleteMultipleFromCloud($remotePaths);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }


} 