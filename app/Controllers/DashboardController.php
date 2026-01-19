<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PageRepository;
use App\Repositories\UserRepository;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $user = $this->container->get('user');
        if (!$user || !$this->db) {
            $this->response->redirect('/login');
            return;
        }

        if ($user['role'] === 'system_admin') {
            $this->response->redirect('/admin');
            return;
        }

        // Page admin - find their pages
        $pages = $this->db->fetchAll(
            "SELECT p.* FROM {$this->db->table('pages')} p
             INNER JOIN {$this->db->table('page_admins')} pa ON p.id = pa.page_id
             WHERE pa.user_id = ?
             ORDER BY p.created_at DESC",
            [$user['id']]
        );

        if (empty($pages)) {
            $this->response->view('dashboard/no_pages');
            return;
        }

        if (count($pages) === 1) {
            // Redirect to public page, not editor
            $this->response->redirect('/p/' . $pages[0]['unique_numeric_id']);
            return;
        }

        $this->response->view('dashboard/index', ['pages' => $pages]);
    }
}














