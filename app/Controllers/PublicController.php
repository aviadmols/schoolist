<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PageRepository;
use App\Repositories\BlockRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\EventRepository;
use App\Repositories\HomeworkRepository;
use App\Services\Logger;

class PublicController extends BaseController
{
    public function index(): void
    {
        $this->response->view('public/index');
    }

    public function page(array $params): void
    {
        Logger::info("PublicController::page: Starting", ['params' => $params]);
        $pageId = (int)($params['pageId'] ?? 0);
        
        if (!$this->db) {
            Logger::error("PublicController::page: Database not available");
            $this->response->view('public/error', ['message' => 'Database not available']);
            return;
        }

        $pageRepo = new PageRepository($this->db);
        $page = $pageRepo->findByUniqueId($pageId);

        if (!$page) {
            Logger::error("PublicController::page: Page not found", ['pageId' => $pageId]);
            $this->response->view('public/error', ['message' => 'Page not found']);
            return;
        }
        
        Logger::info("PublicController::page: Page found, loading content", ['id' => $page['id']]);

        $blockRepo = new BlockRepository($this->db);
        $announcementRepo = new AnnouncementRepository($this->db);
        
        // Ensure database is up to date
        (new \App\Services\Migration($this->db))->migrateAll();
        
        $eventRepo = new EventRepository($this->db);
        $homeworkRepo = new HomeworkRepository($this->db);

        $blocks = $blockRepo->findByPage($page['id']);
        Logger::info("PublicController::page: Found blocks", ['count' => count($blocks)]);
        
        // Ensure required blocks exist
        $requiredBlockTypes = [
            'schedule' => 'מערכת שעות',
            'calendar' => 'לוח חופשות, חגים וימים מיוחדים',
            'whatsapp' => 'קבוצות וואטסאפ ועדכונים',
            'links' => 'קישורים שימושיים',
            'contact_page' => 'דף קשר',
            'contacts' => 'אנשי קשר חשובים'
        ];
        
        // Create a map of existing blocks by type
        $existingBlocksByType = [];
        foreach ($blocks as $block) {
            $existingBlocksByType[$block['type']] = $block;
        }
        
        // Create missing required blocks
        $maxOrderIndex = $blockRepo->getMaxOrderIndex($page['id']);
        $orderIndex = $maxOrderIndex + 1;
        foreach ($requiredBlockTypes as $type => $title) {
            if (!isset($existingBlocksByType[$type])) {
                Logger::info("PublicController::page: Creating missing block", ['type' => $type]);
                $blockId = $blockRepo->create($page['id'], $type, $title, [], $orderIndex);
                $orderIndex++;
                // Reload blocks to include the new one
                $blocks = $blockRepo->findByPage($page['id']);
                // Rebuild the map
                $existingBlocksByType = [];
                foreach ($blocks as $block) {
                    $existingBlocksByType[$block['type']] = $block;
                }
            }
        }
        
        // Check if user is page admin
        $isPageAdmin = false;
        $user = $this->container->get('user') ?? null;
        
        if (!$user && $this->db) {
            $userRepo = new \App\Repositories\UserRepository($this->db);
            $otpRepo = new \App\Repositories\OtpRepository($this->db);
            $tokenRepo = new \App\Repositories\AuthTokenRepository($this->db);
            
            $emailService = $this->container->get(\App\Services\EmailService::class);
            $smsService = $this->container->get(\App\Services\SmsService::class);
            $rateLimiter = $this->container->get(\App\Services\RateLimiter::class);
            
            $auth = new \App\Services\Auth($userRepo, $otpRepo, $tokenRepo, $emailService, $smsService, $rateLimiter);
            $user = $auth->getUser();
            
            if ($user) {
                $this->container->set('user', $user);
            }
        }
        
        if ($user && $this->db) {
            if ($user['role'] === 'system_admin') {
                $isPageAdmin = true;
            } elseif ($user['role'] === 'page_admin') {
                $userRepo = new \App\Repositories\UserRepository($this->db);
                $isPageAdmin = $userRepo->isPageAdmin($user['id'], $page['id']);
            }
            Logger::info("PublicController::page: Auth check", ['email' => $user['email'], 'isPageAdmin' => $isPageAdmin]);
        }
        
        $announcements = $announcementRepo->findByPage($page['id']);
        $events = $isPageAdmin ? $eventRepo->findByPage($page['id']) : $eventRepo->findPublishedByPage($page['id']);
        $homework = $homeworkRepo->findByPage($page['id']);

        // Decode JSON data
        foreach ($blocks as &$block) {
            $block['data'] = isset($block['data_json']) ? (json_decode($block['data_json'], true) ?? []) : [];
        }

        $settings = json_decode($page['settings_json'] ?? '{}', true) ?? [];

        // Get page admins for footer
        $pageAdmins = [];
        if ($this->db) {
            $pageAdmins = $this->db->fetchAll(
                "SELECT u.email, u.first_name, u.last_name, u.phone 
                 FROM {$this->db->table('users')} u
                 INNER JOIN {$this->db->table('page_admins')} pa ON u.id = pa.user_id
                 WHERE pa.page_id = ? AND u.status = 'active'
                 ORDER BY u.first_name ASC",
                [$page['id']]
            );
        }

        // Get Israeli cities
        $cities = [];
        if ($this->db) {
            try {
                $tableName = $this->db->table('cities');
                $cities = $this->db->fetchAll("SELECT name FROM `{$tableName}` ORDER BY name ASC");
                $cities = array_column($cities, 'name');
            } catch (\Exception $e) {
                Logger::error("Error fetching cities", ['msg' => $e->getMessage()]);
            }
        }

        $this->response->view('public/page', [
            'page' => $page,
            'blocks' => $blocks,
            'announcements' => $announcements,
            'events' => $events,
            'homework' => $homework,
            'settings' => $settings,
            'isPageAdmin' => $isPageAdmin,
            'pageAdmins' => $pageAdmins,
            'cities' => $cities,
            'container' => $this->container
        ]);
    }

    public function getWeather(): void
    {
        // Open-Meteo API - Free, no API key required
        // Coordinates for Tel Aviv: 32.0853° N, 34.7818° E
        $latitude = 32.0853;
        $longitude = 34.7818;
        
        // Determine which day to show: tomorrow from 16:00, otherwise today
        $currentHour = (int)date('H');
        $isTomorrow = ($currentHour >= 16);
        
        // Get hourly forecast for today and tomorrow
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$latitude}&longitude={$longitude}&hourly=temperature_2m,weather_code,precipitation&timezone=Asia/Jerusalem&forecast_days=2";

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode !== 200) {
                error_log("Weather API error: HTTP {$httpCode}, Error: {$error}");
                // Return default on error
                $this->response->json([
                    'ok' => true,
                    'weather' => [
                        'temp' => 22,
                        'tempMin' => 20,
                        'tempMax' => 24,
                        'rain' => false,
                        'weatherMain' => 'Clear',
                        'isTomorrow' => $isTomorrow
                    ]
                ]);
                return;
            }

            $data = json_decode($response, true);
            
            if ($data && isset($data['hourly'])) {
                $hourly = $data['hourly'];
                $times = $hourly['time'] ?? [];
                $temperatures = $hourly['temperature_2m'] ?? [];
                $weatherCodes = $hourly['weather_code'] ?? [];
                $precipitations = $hourly['precipitation'] ?? [];
                
                // Find temperatures between 8:00 and 16:00 for the target day
                $targetDate = $isTomorrow ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
                $tempMin = null;
                $tempMax = null;
                $isRaining = false;
                $weatherCode = 0;
                
                foreach ($times as $index => $time) {
                    // Extract date and hour from time string (format: Y-m-dTH:i)
                    $timeParts = explode('T', $time);
                    if (count($timeParts) !== 2) continue;
                    
                    $date = $timeParts[0];
                    $hourMin = explode(':', $timeParts[1]);
                    $hour = (int)$hourMin[0];
                    
                    // Check if this is the target date and hour is between 8:00 and 16:00
                    if ($date === $targetDate && $hour >= 8 && $hour <= 16) {
                        $temp = $temperatures[$index] ?? null;
                        if ($temp !== null) {
                            if ($tempMin === null || $temp < $tempMin) {
                                $tempMin = $temp;
                            }
                            if ($tempMax === null || $temp > $tempMax) {
                                $tempMax = $temp;
                            }
                        }
                        
                        // Check for rain
                        $precip = $precipitations[$index] ?? 0;
                        $code = $weatherCodes[$index] ?? 0;
                        if ($precip > 0 || ($code >= 51 && $code <= 67) || ($code >= 80 && $code <= 99)) {
                            $isRaining = true;
                        }
                        if ($weatherCode === 0) {
                            $weatherCode = $code;
                        }
                    }
                }
                
                // Fallback values if no data found
                if ($tempMin === null) $tempMin = 20;
                if ($tempMax === null) $tempMax = 24;
                
                // Map weather code to main condition
                $weatherMain = 'Clear';
                if ($isRaining) {
                    $weatherMain = 'Rain';
                } elseif ($weatherCode >= 71 && $weatherCode <= 77) {
                    $weatherMain = 'Snow';
                } elseif ($weatherCode >= 45 && $weatherCode <= 48) {
                    $weatherMain = 'Fog';
                } elseif ($weatherCode >= 1 && $weatherCode <= 3) {
                    $weatherMain = 'Clouds';
                }
                
                $this->response->json([
                    'ok' => true,
                    'weather' => [
                        'temp' => round(($tempMin + $tempMax) / 2),
                        'tempMin' => round($tempMin),
                        'tempMax' => round($tempMax),
                        'rain' => $isRaining,
                        'weatherMain' => $weatherMain,
                        'isTomorrow' => $isTomorrow
                    ]
                ]);
            } else {
                // Invalid response
                error_log("Weather API: Invalid response structure");
                $this->response->json([
                    'ok' => true,
                    'weather' => [
                        'temp' => 22,
                        'tempMin' => 20,
                        'tempMax' => 24,
                        'rain' => false,
                        'weatherMain' => 'Clear',
                        'isTomorrow' => $isTomorrow
                    ]
                ]);
            }
        } catch (\Exception $e) {
            error_log("Weather API exception: " . $e->getMessage());
            // Return default on exception
            $this->response->json([
                'ok' => true,
                'weather' => [
                    'temp' => 22,
                    'tempMin' => 20,
                    'tempMax' => 24,
                    'rain' => false,
                    'weatherMain' => 'Clear',
                    'isTomorrow' => $isTomorrow
                ]
            ]);
        }
    }
}



