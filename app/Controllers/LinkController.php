<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\LinkRepository;
use App\Repositories\QActivationRepository;
use App\Repositories\PageRepository;
use App\Services\Logger;
use Throwable;

/**
 * Controller for handling direct link activation and redirection.
 */
class LinkController extends BaseController
{
    private const VIEW_ACTIVATE = 'link/activate';
    private const VIEW_ERROR = 'link/error';

    /**
     * Entry point for numeric direct links. Redirects if already active.
     */
    public function handle(array $params): void
    {
        $linkNumber = (int)($params['number'] ?? 0);
        
        if (!$this->db) {
            $this->response->view(self::VIEW_ERROR, ['message' => 'Database not available']);
            return;
        }

        $linkRepo = new LinkRepository($this->db);
        $link = $linkRepo->findByNumber($linkNumber);

        if ($link && !empty($link['page_unique_numeric_id'])) {
            // Already mapped to a class page
            $linkRepo->updateLastUsed($linkNumber);
            $this->response->redirect('/c/' . $link['page_unique_numeric_id']);
            return;
        }

        // Show manual activation form
        $this->response->view(self::VIEW_ACTIVATE, ['link_number' => $linkNumber]);
    }

    /**
     * Maps a numeric link to a specific class page using an activation code.
     */
    public function activate(): void
    {
        try {
            $data = $this->request->json();
            $linkNumber = (int)($data['link_number'] ?? 0);
            $activationCode = trim($data['activation_code'] ?? '');

            if ($linkNumber <= 0 || empty($activationCode)) {
                $this->response->json(['ok' => false, 'message_he' => 'נא למלא את כל השדות'], 400);
                return;
            }

            $pageUniqueId = (int)$activationCode;
            
            // 1. Verify Page exists
            $pageRepo = new PageRepository($this->db);
            if (!$pageRepo->findByUniqueId($pageUniqueId)) {
                $this->response->json(['ok' => false, 'message_he' => 'הקוד לא תקין - לא נמצאה התאמה לכיתה'], 404);
                return;
            }

            // 2. Activate Link
            $linkRepo = new LinkRepository($this->db);
            $link = $linkRepo->findByNumber($linkNumber);
            
            if ($link) {
                $linkRepo->activate($linkNumber, $pageUniqueId);
            } else {
                $linkRepo->create($linkNumber, 0);
                $linkRepo->activate($linkNumber, $pageUniqueId);
            }
            
            // 3. Sync with Q-Activations for reporting
            $qRepo = new QActivationRepository($this->db);
            $activations = $qRepo->list(['page_unique_id' => $pageUniqueId]);
            if (!empty($activations)) {
                $qRepo->updateLastUsed($activations[0]['id']);
            }

            $this->response->json([
                'ok' => true,
                'redirect_url' => '/c/' . $pageUniqueId
            ]);

        } catch (Throwable $e) {
            Logger::error("LinkController::activate failed", ['msg' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה פנימית'], 500);
        }
    }
}
