<?php
// Calculate school year for title
$currentYear = (int)date('Y');
$currentMonth = (int)date('n');
if ($currentMonth >= 9) {
    $schoolYear = $currentYear . '-' . ($currentYear + 1);
} else {
    $schoolYear = ($currentYear - 1) . '-' . $currentYear;
}

$schoolName = htmlspecialchars($page['school_name'] ?? '', ENT_QUOTES, 'UTF-8');
$cityName = htmlspecialchars($page['city'] ?? '', ENT_QUOTES, 'UTF-8');
$classGrade = htmlspecialchars($page['class_type'] ?? '', ENT_QUOTES, 'UTF-8');

$titleParts = ['בי״ס'];
if ($schoolName) $titleParts[] = $schoolName;
if ($cityName) $titleParts[] = $cityName;
$titleLeft = implode(' ', $titleParts);
$titleRight = $schoolYear;
if ($classGrade) $titleRight .= ' - ' . $classGrade;
$title = $titleLeft . ' | ' . $titleRight;

$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <?php if ($isPageAdmin): ?>
        <link rel="stylesheet" href="/public/assets/css/quick-add.css">
    <?php endif; ?>
</head>
<body class="public-page">
    <div class="public-page-background"></div>
    <div class="page-container">
        <header class="page-header">
            <div class="page-header-content">
                <div class="schoolist-badge-inline">
                    <?php if (!empty($settings['logo'])): ?>
                        <img src="<?= htmlspecialchars($settings['logo'], ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
                    <?php else: ?>
                        <span class="schoolist-badge-text">
                            <?php 
                            $grade = $page['class_type'] ?? '';
                            $number = $page['class_number'] ?? '';
                            if ($grade && $number) {
                                echo htmlspecialchars($grade . "'" . $number, ENT_QUOTES, 'UTF-8');
                            } else {
                                // Fallback to school name if class data not available
                                echo mb_substr($page['school_name'], 0, 2, 'UTF-8');
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h1 id="page-title-text">
                    <?php
                    // Calculate school year (e.g., 2025-2026)
                    $currentYear = (int)date('Y');
                    $currentMonth = (int)date('n');
                    // School year starts in September (month 9)
                    if ($currentMonth >= 9) {
                        $schoolYear = $currentYear . '-' . ($currentYear + 1);
                    } else {
                        $schoolYear = ($currentYear - 1) . '-' . $currentYear;
                    }
                    
                    $schoolName = htmlspecialchars($page['school_name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $cityName = htmlspecialchars($page['city_name'] ?? '', ENT_QUOTES, 'UTF-8');
                    
                    $titleParts = ['בי״ס'];
                    if ($schoolName) {
                        $titleParts[] = $schoolName;
                    }
                    if ($cityName) {
                        $titleParts[] = $cityName;
                    }
                    $titleLeft = implode(' ', $titleParts);
                    
                    $titleRight = $schoolYear;
                    ?>
                    <div>
                        <span class="schoolist-titleRight"><?php echo $titleRight; ?></span><br>
                        <div class="schhol-name">
                            <div><?php echo $titleLeft; ?></div>
                            <?php if ($isPageAdmin): ?>
                                <div class="page-header-buttons">
                                    <button class="btn-edit-page-title" onclick="openEditPageTitleModal()" title="ערוך כותרת">
                                        <img src="/assets/files/pencil.svg" alt="ערוך">
                                    </button>
                                    <button class="btn-user-profile" title="פרופיל משתמש">
                                        <img src="/assets/files/user.svg" alt="פרופיל">
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </h1>
            </div>
        </header>

        <?php
        // Find children with birthdays in the next week (from today to 7 days ahead)
        $thisWeekBirthdayChildren = [];
        
        // Calculate next week range (today to 7 days ahead)
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        $weekEnd = clone $today;
        $weekEnd->modify('+7 days'); // 8 days total (today + 7 more days)
        $weekEnd->setTime(23, 59, 59);
        
        // Get all days in the next week (from today)
        $weekDays = [];
        $currentDay = clone $today;
        for ($i = 0; $i < 8; $i++) {
            $weekDays[] = [
                'day' => (int)$currentDay->format('d'),
                'month' => (int)$currentDay->format('m')
            ];
            $currentDay->modify('+1 day');
        }
        
        // Helper function to parse and check birth date
        $parseBirthDate = function($birthDate, $childName) use ($weekDays, $today, $weekEnd) {
            if (empty($birthDate)) return null;
            
            // Parse date manually (YYYY-MM-DD format)
            $birthParts = explode('-', $birthDate);
            if (count($birthParts) !== 3) {
                return null;
            }
            
            $birthYear = (int)$birthParts[0];
            $birthMonth = (int)$birthParts[1];
            $birthDay = (int)$birthParts[2];
            
            // Validate date parts
            if ($birthYear < 1900 || $birthYear > 2100 || $birthMonth < 1 || $birthMonth > 12 || $birthDay < 1 || $birthDay > 31) {
                return null;
            }
            
            // Check if birthday is in the next week (only day and month)
            $isInWeek = false;
            $weekDayIndex = -1;
            foreach ($weekDays as $index => $weekDay) {
                if ($birthDay === $weekDay['day'] && $birthMonth === $weekDay['month']) {
                    $isInWeek = true;
                    $weekDayIndex = $index;
                    break;
                }
            }
            
            if ($isInWeek) {
                // Calculate the actual birthday date in the next week
                $birthdayThisWeek = new DateTime();
                $birthdayThisWeek->setTimestamp($today->getTimestamp());
                $birthdayThisWeek->modify('+' . $weekDayIndex . ' days');
                $birthdayThisWeek->setTime(0, 0, 0);
                
                // Calculate age based on the birthday date in the next week
                $age = (int)$birthdayThisWeek->format('Y') - $birthYear;
                
                // Format date for display (DD/MM)
                $formattedBirthDate = sprintf('%02d/%02d', $birthDay, $birthMonth);
                
                // Get day name in Hebrew for the birthday date in the next week
                $dayNamesHe = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
                $dayOfWeek = (int)$birthdayThisWeek->format('w'); // 0 = Sunday
                $dayName = $dayNamesHe[$dayOfWeek];
                
                // Calculate sort order (birthday date in the next week - closest first)
                $sortOrder = (int)$birthdayThisWeek->format('Ymd');
                
                return [
                    'name' => $childName,
                    'birth_date' => $formattedBirthDate,
                    'day_name' => $dayName,
                    'age' => $age,
                    'sort_order' => $sortOrder
                ];
            }
            
            return null;
        };
        
        // Collect children from contact_page blocks
        foreach ($blocks as $block) {
            if ($block['type'] === 'contact_page' && !empty($block['data']['children'])) {
                error_log("PublicController::page: Found contact_page block with " . count($block['data']['children']) . " children");
                foreach ($block['data']['children'] as $child) {
                    $birthDate = $child['birth_date'] ?? '';
                    $childName = $child['name'] ?? '';
                    
                    error_log("PublicController::page: Checking child: name=" . $childName . ", birth_date=" . $birthDate);
                    
                    if (empty($birthDate) || empty($childName)) {
                        error_log("PublicController::page: Skipping child - missing birth_date or name");
                        continue;
                    }
                    
                    $birthdayData = $parseBirthDate($birthDate, $childName);
                    if ($birthdayData) {
                        error_log("PublicController::page: Found birthday in next week: " . json_encode($birthdayData));
                        $thisWeekBirthdayChildren[] = $birthdayData;
                    } else {
                        error_log("PublicController::page: Birthday not in next week for: " . $childName);
                    }
                }
            }
        }
        
        error_log("PublicController::page: Total birthdays found: " . count($thisWeekBirthdayChildren));
        
        // Collect children from invitation_codes table
        $db = $container->get(\App\Services\Database::class) ?? null;
        if ($db) {
            try {
                $invitationRepo = new \App\Repositories\InvitationRepository($db);
                $invitations = $invitationRepo->list(['status' => 'used']);
                
                foreach ($invitations as $invitation) {
                    // Check if this invitation belongs to this page
                    if (isset($invitation['used_page_id']) && (int)$invitation['used_page_id'] === (int)$page['id']) {
                        $childBirthDate = $invitation['child_birth_date'] ?? '';
                        $childName = $invitation['child_name'] ?? '';
                        
                        if (!empty($childBirthDate) && !empty($childName)) {
                            $birthdayData = $parseBirthDate($childBirthDate, $childName);
                            if ($birthdayData) {
                                // Check if not already added (avoid duplicates)
                                $alreadyAdded = false;
                                foreach ($thisWeekBirthdayChildren as $existing) {
                                    if ($existing['name'] === $birthdayData['name'] && 
                                        $existing['birth_date'] === $birthdayData['birth_date']) {
                                        $alreadyAdded = true;
                                        break;
                                    }
                                }
                                if (!$alreadyAdded) {
                                    $thisWeekBirthdayChildren[] = $birthdayData;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silently fail if there's an error accessing the database
                error_log("Error fetching birthday children: " . $e->getMessage());
            }
        }
        
        // Sort by next birthday date (closest first)
        usort($thisWeekBirthdayChildren, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });
        
        // Display birthday strip if there are children with birthdays this week
        if (!empty($thisWeekBirthdayChildren)):
            $closestChild = $thisWeekBirthdayChildren[0];
            $totalChildren = count($thisWeekBirthdayChildren);
            $remainingChildren = $totalChildren - 1; // Don't count the child already displayed
        ?>
            <div class="birthday-strip">
                <div class="birthday-content">
                    <div class="birthday-item">
                        <strong><?= htmlspecialchars($closestChild['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        חוגג ביום <?= htmlspecialchars($closestChild['day_name'], ENT_QUOTES, 'UTF-8') ?> יום הולדת - <?= $closestChild['age'] ?>
                    </div>
                    <?php if ($totalChildren > 1): ?>
                        <div class="birthday-more-info">
                            <span class="birthday-count-text">יש השבוע יום הולדת לעד <?= $remainingChildren ?> ילדים</span>
                            <button class="btn-birthday-more" onclick="showAllBirthdays()">קרא עוד</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($totalChildren > 1): ?>
            <!-- Birthday Modal -->
            <div id="birthdayModal" class="birthday-modal" style="display: none;">
                <div class="birthday-modal-overlay" onclick="closeBirthdayModal()"></div>
                <div class="birthday-modal-content">
                    <div class="modal-header-unified">
                        <h3>ימי הולדת השבוע</h3>
                        <button class="modal-close-btn" onclick="closeBirthdayModal()">
                            <img src="/assets/files/cross.svg" alt="סגור">
                        </button>
                    </div>
                    <div class="birthday-modal-body">
                        <?php foreach ($thisWeekBirthdayChildren as $child): ?>
                            <div class="birthday-modal-item">
                                <strong><?= htmlspecialchars($child['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                חוגג ביום <?= htmlspecialchars($child['day_name'], ENT_QUOTES, 'UTF-8') ?> יום הולדת - <?= $child['age'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        // Find calendar block and display next holiday date
        $calendarBlock = null;
        foreach ($blocks as $block) {
            if ($block['type'] === 'calendar') {
                $calendarBlock = $block;
                break;
            }
        }
        
        $nextHoliday = null;
        if ($calendarBlock && !empty($calendarBlock['data']['holidays'])) {
            $today = date('Y-m-d');
            $todayTimestamp = strtotime($today);
            
            foreach ($calendarBlock['data']['holidays'] as $holiday) {
                $startDate = $holiday['start_date'] ?? $holiday['date'] ?? '';
                if (empty($startDate)) continue;
                
                $holidayTimestamp = strtotime($startDate);
                
                // Find the next holiday (today or in the future)
                if ($holidayTimestamp >= $todayTimestamp) {
                    if ($nextHoliday === null || $holidayTimestamp < strtotime($nextHoliday['start_date'] ?? $nextHoliday['date'])) {
                        $nextHoliday = $holiday;
                    }
                }
            }
        }
        
        if ($nextHoliday):
            $nextHolidayDate = $nextHoliday['start_date'] ?? $nextHoliday['date'];
            $nextHolidayName = $nextHoliday['name'] ?? '';
            $nextHolidayHasCamp = $nextHoliday['has_camp'] ?? false;
            $formattedDate = date('d.m.Y', strtotime($nextHolidayDate));
        ?>
            <div class="next-holiday-strip">
                <div class="next-holiday-content">
                    <div class="next-holiday-text">
                        <span class="normal-text">החופשה הקרובה היא <?= htmlspecialchars($nextHolidayName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="DateTime-text"><?= $formattedDate ?>       <?php if ($nextHolidayHasCamp): ?>
                            <span class="next-holiday-camp-badge"> יש קייטנה</span>
                        <?php endif; ?></span>
                  
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <?php
        // Find schedule block
        $scheduleBlock = null;
        foreach ($blocks as $block) {
            if ($block['type'] === 'schedule') {
                $scheduleBlock = $block;
                break;
            }
        }
        
        // Prepare schedule data
        $scheduleData = null;
        $hasSchedule = false;
        $displayDayKey = null;
        $label = '';
        
        if ($scheduleBlock && !empty($scheduleBlock['data']['schedule'])) {
            $scheduleData = $scheduleBlock['data'];
            // Determine which day to show: tomorrow from 16:00, otherwise today
            $currentHour = (int)date('H');
            $currentDay = (int)date('w'); // 0 = Sunday, 6 = Saturday
            
            if ($currentHour >= 16) {
                // Show tomorrow's schedule
                $displayDay = ($currentDay + 1) % 7;
                $isTomorrow = true;
            } else {
                // Show today's schedule
                $displayDay = $currentDay;
                $isTomorrow = false;
            }
            
            $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            $dayNamesHe = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
            $displayDayKey = $dayNames[$displayDay];
            $displayDayName = $dayNamesHe[$displayDay];
            
            // Determine label
            if ($isTomorrow) {
                $label = 'מערכת שעות מחר - יום ' . $displayDayName;
            } else {
                $label = 'מערכת שעות היום - יום ' . $displayDayName;
            }
            
            $hasSchedule = isset($scheduleData['schedule'][$displayDayKey]) && !empty($scheduleData['schedule'][$displayDayKey]);
        }
        
        // Display homework until due date (today and future dates)
        $homeworkForToday = [];
        $todayDate = date('Y-m-d');
        $todayTimestamp = strtotime($todayDate);
        
        error_log("PublicController::page: Looking for homework with date >= " . $todayDate);
        error_log("PublicController::page: Total homework items: " . count($homework));
        
        foreach ($homework as $hw) {
            $hwDate = $hw['date'] ?? null;
            
            error_log("PublicController::page: Processing homework ID " . ($hw['id'] ?? 'unknown') . ", raw date: " . var_export($hwDate, true));
            
            // Handle date - it might be a DATE field or DATETIME
            if ($hwDate) {
                // If it's a DateTime object, convert to string
                if ($hwDate instanceof \DateTime) {
                    $hwDate = $hwDate->format('Y-m-d');
                } else {
                    // If it's a string, extract date part only
                    $hwDate = (string)$hwDate;
                    // Remove time if exists
                    if (strpos($hwDate, ' ') !== false) {
                        $dateParts = explode(' ', $hwDate);
                        $hwDate = $dateParts[0];
                    }
                    // Remove time if exists (format: Y-m-d H:i:s)
                    if (strpos($hwDate, 'T') !== false) {
                        $dateParts = explode('T', $hwDate);
                        $hwDate = $dateParts[0];
                    }
                }
                
                // Normalize date format to Y-m-d - handle edge cases
                if ($hwDate && strtotime($hwDate) !== false) {
                    $hwDate = date('Y-m-d', strtotime($hwDate));
                    $hwDateTimestamp = strtotime($hwDate);
                    
                    // Show homework if date is today or in the future
                    if ($hwDateTimestamp >= $todayTimestamp) {
                        $homeworkForToday[] = $hw;
                        error_log("PublicController::page: Added homework ID " . ($hw['id'] ?? 'unknown') . " to display list (date: " . $hwDate . ")");
                    } else {
                        error_log("PublicController::page: Skipped homework ID " . ($hw['id'] ?? 'unknown') . " - date is in the past (" . $hwDate . ")");
                    }
                } else {
                    error_log("PublicController::page: Invalid date format for homework ID " . ($hw['id'] ?? 'unknown') . ": " . var_export($hwDate, true));
                }
            } else {
                error_log("PublicController::page: Homework ID " . ($hw['id'] ?? 'unknown') . " has no date");
            }
        }
        
        // Sort homework by date (ascending - earliest first)
        usort($homeworkForToday, function($a, $b) {
            $dateA = $a['date'] ?? '';
            $dateB = $b['date'] ?? '';
            if (empty($dateA)) return 1;
            if (empty($dateB)) return 1;
            
            // Extract date part only
            if (strpos($dateA, ' ') !== false) {
                $dateA = explode(' ', $dateA)[0];
            }
            if (strpos($dateB, ' ') !== false) {
                $dateB = explode(' ', $dateB)[0];
            }
            
            return strtotime($dateA) <=> strtotime($dateB);
        });
        
        error_log("PublicController::page: Found " . count($homeworkForToday) . " homework items for display");
        
        $hasHomework = !empty($homeworkForToday);
        
        // Show section if there's schedule OR homework
        if ($hasSchedule || $hasHomework):
            $lessonNumbers = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שביעי', 'שמיני', 'תשיעי', 'עשירי'];
            $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            $dayNamesHe = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
            $dayLetters = ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש'];
        ?>
        <section class="today-schedule-section">
            <?php if ($hasSchedule): ?>
            <div class="today-schedule-card">
                <div class="schedule-day-selector">
                    <?php foreach ($dayNames as $index => $dayKey): 
                        $hasLessons = isset($scheduleData['schedule'][$dayKey]) && !empty($scheduleData['schedule'][$dayKey]);
                        if (!$hasLessons) continue;
                        $isActive = ($dayKey === $displayDayKey);
                    ?>
                        <button class="schedule-day-btn <?= $isActive ? 'active' : '' ?>" 
                                data-day="<?= htmlspecialchars($dayKey, ENT_QUOTES, 'UTF-8') ?>"
                                onclick="switchScheduleDay('<?= htmlspecialchars($dayKey, ENT_QUOTES, 'UTF-8') ?>')">
                            <?= htmlspecialchars($dayLetters[$index], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="schedule-greeting title-section" id="schedule-greeting">
                    <?php
                    $greetingDayName = $displayDayName;
                    $greetingText = $isTomorrow ? 'מחר' : 'היום';
                    $greetingHtml = 'יום ' . $greetingDayName . ' ' . $greetingText . '!';
                    
                    // Add time-based greeting only for active day (today or tomorrow based on 16:00 rule)
                    $currentHour = (int)date('H');
                    $timeGreeting = '';
                    if ($currentHour >= 5 && $currentHour < 12) {
                        $timeGreeting = 'בוקר טוב';
                    } elseif ($currentHour >= 12 && $currentHour < 17) {
                        $timeGreeting = 'צהריים טובים';
                    } elseif ($currentHour >= 17 && $currentHour < 22) {
                        $timeGreeting = 'ערב טוב';
                    } else {
                        $timeGreeting = 'לילה טוב';
                    }
                    $greetingHtml .= ' <span class="Boldsubheading">' . htmlspecialchars($timeGreeting, ENT_QUOTES, 'UTF-8') . '</span>';
                    
                    echo $greetingHtml;
                    ?>
                </div>
                <div class="today-schedule-list" id="schedule-list">
                    <?php foreach ($scheduleData['schedule'][$displayDayKey] as $index => $lesson): 
                        $lessonNum = $lessonNumbers[$index] ?? 'שיעור ' . ($index + 1);
                    ?>
                        <div class="today-schedule-item">
                        <span class="lesson-subject normal-text">
                            <?= htmlspecialchars($lesson['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($lesson['teacher'])): ?>
                                <span class="lesson-teacher"><?= htmlspecialchars($lesson['teacher'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </span>

                        <span class="lesson-time small-text"><?= htmlspecialchars($lesson['time'] ?? 'שיעור ' . $lessonNum, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($lesson['room'])): ?>
                                <span class="lesson-room">חדר <?= htmlspecialchars($lesson['room'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($hasHomework): ?>
            <div class="announcement-card" style="margin-top: 1rem;">
                <div class="announcement-header-row title-section">
               שיעורי בית
                </div>
                <?php foreach ($homeworkForToday as $hw): 
                    $hwTitle = $hw['title'] ?? '';
                    $hwHtml = $hw['html'] ?? '';
                    $hwId = $hw['id'];
                    $hwDate = $hw['date'] ?? null;
                    
                    // Parse and format date
                    $formattedDate = '';
                    $dayName = '';
                    if ($hwDate) {
                        // Extract date part only
                        $dateOnly = $hwDate;
                        if (strpos($dateOnly, ' ') !== false) {
                            $dateOnly = explode(' ', $dateOnly)[0];
                        }
                        if (strpos($dateOnly, 'T') !== false) {
                            $dateOnly = explode('T', $dateOnly)[0];
                        }
                        
                        if (strtotime($dateOnly) !== false) {
                            // Format date as d/m/Y
                            $formattedDate = date('d/m/Y', strtotime($dateOnly));
                            
                            // Get day name in Hebrew
                            $dayNames = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
                            $dayOfWeek = (int)date('w', strtotime($dateOnly)); // 0 = Sunday
                            $dayName = $dayNames[$dayOfWeek];
                        }
                    }
                    
                    // Check if there's additional content
                    $hasAdditionalContent = false;
                    if ($hwTitle) {
                        $htmlText = strip_tags($hwHtml);
                        $htmlText = trim($htmlText);
                        $hasAdditionalContent = !empty($htmlText);
                    } else {
                        $textContent = strip_tags($hwHtml);
                        $hasAdditionalContent = mb_strlen($textContent, 'UTF-8') > 50;
                    }
                ?>
                    <div class="announcement-item" data-id="<?= $hwId ?>">
                        <div class="announcement-header" onclick="openHomeworkView(<?= $hwId ?>)">
                            <?php if ($isPageAdmin): ?>
                                <div class="announcement-edit-controls" onclick="event.stopPropagation();">
                                    <button class="btn-announcement-menu" onclick="toggleHomeworkMenu(event, <?= $hwId ?>)" title="פעולות">
                                        <img src="/assets/files/menu-dots-vertical.svg" alt="פעולות">
                                    </button>
                                    <div class="announcement-menu-popup" id="homework-menu-<?= $hwId ?>" onclick="event.stopPropagation();">
                                        <div class="announcement-menu-header">פעולות</div>
                                        <div class="announcement-menu-item" onclick="editHomework(<?= $hwId ?>); closeHomeworkMenu(<?= $hwId ?>);">
                                            <img src="/assets/files/pencil.svg" alt="עריכה">
                                            <span>עריכה</span>
                                        </div>
                                        <div class="announcement-menu-item" onclick="deleteHomework(<?= $hwId ?>); closeHomeworkMenu(<?= $hwId ?>);">
                                            <img src="/assets/files/trash.svg" alt="מחיקה">
                                            <span>מחיקה</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="announcement-title">
                                <span><?= htmlspecialchars($hwTitle ?: strip_tags($hwHtml), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($hasAdditionalContent): ?>
                                    <button class="read-more-btn" onclick="event.stopPropagation(); openHomeworkView(<?= $hwId ?>)">קרא עוד</button>
                                <?php endif; ?>
                                <?php if ($formattedDate && $dayName): ?>
                                <div class="homework-due-date" style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">
                                    יום <?= htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') ?> 
                                </div>
                            <?php endif; ?>
                            </div>
                         
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php 
            endif;
        ?>

        <div class="weather-strip">
            <div class="weather-icon"><img decoding="async" src="https://www.schoolist.co.il/wp-content/uploads/2025/12/sun.svg" alt="שמש"></div>
            <div class="weather-content">
                <div class="weather-text normal-text" id="weather-greeting">
                    <span id="weather-greeting-text" class="normal-text"></span>
                    <span id="weather-temperature"  class="normal-text"></span>
                </div>
                <div class="weather-recommendation normal-text" id="weather-recommendation">טוען המלצה...</div>
            </div>
        </div>

        <?php
        // Prepare announcements for display
        $announcementsForDisplay = [];
        foreach ($announcements as $announcement) {
            $announcementsForDisplay[] = $announcement;
        }
        
        // Sort announcements by date (null dates go to end)
        usort($announcementsForDisplay, function($a, $b) {
            if ($a['date'] === null && $b['date'] === null) return 0;
            if ($a['date'] === null) return 1;
            if ($b['date'] === null) return -1;
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        // Prepare future events for display
        $futureEvents = [];
        $today = date('Y-m-d');
        $todayTimestamp = strtotime($today);
        
        foreach ($events as $event) {
            $eventDate = $event['date'] ?? null;
            if ($eventDate) {
                $eventTimestamp = strtotime($eventDate);
                // Only add events that are today or in the future
                if ($eventTimestamp >= $todayTimestamp) {
                    $futureEvents[] = $event;
                }
            }
        }
        
        // Sort events by date (ascending)
        usort($futureEvents, function($a, $b) {
            $dateA = $a['date'] ?? '';
            $dateB = $b['date'] ?? '';
            if (empty($dateA)) return 1;
            if (empty($dateB)) return -1;
            return strtotime($dateA) - strtotime($dateB);
        });
        ?>
        
        <?php if (!empty($announcementsForDisplay)): ?>
            <section class="announcements-section">
                <div class="announcement-card">
                    <div class="announcement-header-row title-section">
                        הודעות
                        <?php if ($isPageAdmin): ?>
                            <button class="btn-edit-announcements" onclick="openAddAnnouncementModal()" title="ערוך הודעות">
                                <img src="/assets/files/pencil.svg" alt="ערוך">
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php 
                    $today = date('Y-m-d');
                    $todayTimestamp = strtotime($today);
                    $weekFromNow = strtotime('+7 days', $todayTimestamp);
                    
                    foreach ($announcementsForDisplay as $announcement):
                        $announcementDate = $announcement['date'] ?? null;
                        $isHighlighted = false;
                        $isPermanent = empty($announcementDate); // הודעה בלי תאריך = תמיד מוצגת
                        
                        if ($announcementDate) {
                            $announcementTimestamp = strtotime($announcementDate);
                            // Highlight if date is within 7 days from today
                            if ($announcementTimestamp >= $todayTimestamp && $announcementTimestamp <= $weekFromNow) {
                                $isHighlighted = true;
                            }
                        }
                        
                        $title = $announcement['title'] ?? '';
                        $html = $announcement['html'] ?? '';
                        $announcementId = $announcement['id'];
                        
                        // Check if there's additional content to show
                        $hasAdditionalContent = false;
                        if ($title) {
                            // If there's a title, check if there's HTML content
                            $htmlText = strip_tags($html);
                            $htmlText = trim($htmlText);
                            $hasAdditionalContent = !empty($htmlText);
                        } else {
                            // If no title, check if content is longer than 50 chars
                            $textContent = strip_tags($html);
                            $hasAdditionalContent = mb_strlen($textContent, 'UTF-8') > 50;
                        }
                    ?>
                        <div class="announcement-item <?= $isHighlighted ? 'highlighted' : '' ?> <?= $isPermanent ? 'permanent' : '' ?>" data-id="<?= $announcementId ?>">
                            <div class="announcement-header">
                                <div class="announcement-content" onclick="toggleAnnouncement(<?= $announcementId ?>)">
                                    <?php if ($isPageAdmin): ?>
                                        <div class="announcement-edit-controls" onclick="event.stopPropagation();">
                                            <button class="btn-announcement-edit" onclick="editAnnouncement(<?= $announcementId ?>)" title="ערוך הודעה">
                                                <img src="/assets/files/pencil.svg" alt="עריכה">
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    <div class="announcement-title  normal-text">
                                        <?php 
                                        // Get day letter in Hebrew if date exists
                                        $dayLetter = '';
                                        if ($announcementDate) {
                                            $dayLetters = ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש'];
                                            $dayOfWeek = (int)date('w', strtotime($announcementDate)); // 0 = Sunday
                                            $dayLetter = $dayLetters[$dayOfWeek];
                                        }
                                        ?>
                                        <?php if ($title): ?>
                                          <?php if ($dayLetter): ?>
                                            <span class="announcement-day  small-text">יום <?= htmlspecialchars($dayLetter, ENT_QUOTES, 'UTF-8') ?>' </span>
                                          <?php endif; ?>
                                          <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            <?php 
                                            // Extract first line or first 50 chars as title
                                            $textContent = strip_tags($html);
                                            $titlePreview = mb_substr($textContent, 0, 50, 'UTF-8');
                                            if (mb_strlen($textContent, 'UTF-8') > 50) {
                                                $titlePreview .= '...';
                                            }
                                            ?>
                                            <?php if ($dayLetter): ?>
                                              <span class="announcement-day  small-text">יום <?= htmlspecialchars($dayLetter, ENT_QUOTES, 'UTF-8') ?>' </span>
                                            <?php endif; ?>
                                            <strong><?= htmlspecialchars($titlePreview, ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php endif; ?>
                                        <?php if ($hasAdditionalContent): ?>
                                            <button class="read-more-btn" onclick="event.stopPropagation(); openAnnouncementViewModal(<?= $announcementId ?>)" data-id="<?= $announcementId ?>">קרא עוד ›</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button class="announcement-checkmark" onclick="toggleAnnouncementCheck(<?= $announcementId ?>, event)" title="סמן כבוצע">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                                        <g clip-path="url(#clip0_11_383_<?= $announcementId ?>)">
                                            <path class="checkmark-path" d="M16.7392 3.32326L6.375 13.6868C6.30532 13.7567 6.22251 13.8122 6.13132 13.8501C6.04013 13.888 5.94236 13.9075 5.84362 13.9075C5.74488 13.9075 5.64712 13.888 5.55593 13.8501C5.46474 13.8122 5.38193 13.7567 5.31225 13.6868L1.30425 9.67501C1.23457 9.60505 1.15176 9.54954 1.06057 9.51166C0.969384 9.47379 0.871615 9.45429 0.772875 9.45429C0.674134 9.45429 0.576366 9.47379 0.485179 9.51166C0.393992 9.54954 0.311182 9.60505 0.2415 9.67501C0.171542 9.74469 0.116033 9.8275 0.0781567 9.91869C0.0402804 10.0099 0.0207829 10.1076 0.0207829 10.2064C0.0207829 10.3051 0.0402804 10.4029 0.0781567 10.4941C0.116033 10.5853 0.171542 10.6681 0.2415 10.7378L4.251 14.7465C4.67396 15.1687 5.24715 15.4058 5.84475 15.4058C6.44235 15.4058 7.01554 15.1687 7.4385 14.7465L17.802 4.38526C17.8718 4.31559 17.9273 4.23282 17.9651 4.14171C18.0029 4.05059 18.0223 3.95291 18.0223 3.85426C18.0223 3.75561 18.0029 3.65792 17.9651 3.56681C17.9273 3.47569 17.8718 3.39292 17.802 3.32326C17.7323 3.2533 17.6495 3.19779 17.5583 3.15991C17.4671 3.12204 17.3694 3.10254 17.2706 3.10254C17.1719 3.10254 17.0741 3.12204 16.9829 3.15991C16.8917 3.19779 16.8089 3.2533 16.7392 3.32326Z" fill="black"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_11_383_<?= $announcementId ?>">
                                                <rect width="18" height="18" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <?php if (!empty($futureEvents)): ?>
            <section class="announcements-section" style="margin-top: 1rem;">
                <div class="announcement-card">
                    <div class="announcement-header-row title-section">
                        אירועים
                        <?php if ($isPageAdmin): ?>
                            <button class="btn-edit-announcements" onclick="openAddEventModal()" title="ערוך אירועים">
                                <img src="/assets/files/pencil.svg" alt="ערוך">
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php foreach ($futureEvents as $event): 
                        $eventDate = $event['date'] ?? null;
                        $eventId = $event['id'];
                        $eventName = $event['name'] ?? '';
                        $eventDescription = $event['description'] ?? '';
                        $hasAdditionalContent = !empty($eventDescription) || !empty($event['location']);
                        
                        // Get day letter in Hebrew if date exists
                        $dayLetter = '';
                        if ($eventDate) {
                            $dayLetters = ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש'];
                            $dayOfWeek = (int)date('w', strtotime($eventDate)); // 0 = Sunday
                            $dayLetter = $dayLetters[$dayOfWeek];
                        }
                    ?>
                        <div class="announcement-item event-item" data-id="<?= $eventId ?>" data-type="event">
                            <div class="announcement-header" onclick="openEventView(<?= $eventId ?>)">
                                <?php if ($isPageAdmin): ?>
                                    <div class="announcement-edit-controls" onclick="event.stopPropagation();">
                                        <button class="btn-announcement-menu" onclick="toggleEventMenu(event, <?= $eventId ?>)" title="פעולות">
                                            <img src="/assets/files/menu-dots-vertical.svg" alt="פעולות">
                                        </button>
                                        <div class="announcement-menu-popup" id="event-menu-<?= $eventId ?>" onclick="event.stopPropagation();">
                                            <div class="announcement-menu-header">פעולות</div>
                                            <div class="announcement-menu-item" onclick="editEvent(<?= $eventId ?>); closeEventMenu(<?= $eventId ?>);">
                                                <img src="/assets/files/pencil.svg" alt="עריכה">
                                                <span>עריכה</span>
                                            </div>
                                            <div class="announcement-menu-item" onclick="deleteEvent(<?= $eventId ?>); closeEventMenu(<?= $eventId ?>);">
                                                <img src="/assets/files/trash.svg" alt="מחיקה">
                                                <span>מחיקה</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="announcement-title">
                                    <span class="normal-text"><?= htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($eventDate): ?>
                                        <div class="small-text">
                                            <?php if ($dayLetter): ?>
                                                <span class="announcement-day">יום <?= htmlspecialchars($dayLetter, ENT_QUOTES, 'UTF-8') ?>' </span>
                                            <?php endif; ?>
                                            <?= date('d.m', strtotime($eventDate)) ?>
                                            <?php if (!empty($event['time'])): ?>
                                                <?= date('H:i', strtotime($event['time'])) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($event['location'])): ?>
                                                <?= htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($hasAdditionalContent): ?>
                                        <button class="read-more-btn" onclick="event.stopPropagation(); openEventView(<?= $eventId ?>)" data-id="<?= $eventId ?>">קרא עוד ›</button>
                                    <?php endif; ?>
                                    <?php if (!empty($event['date'])): ?>
                                        <button class="btn-add-to-calendar" onclick="event.stopPropagation(); addEventToCalendar(<?= htmlspecialchars(json_encode([
                                            'name' => $event['name'] ?? '',
                                            'date' => $event['date'] ?? '',
                                            'time' => $event['time'] ?? null,
                                            'location' => $event['location'] ?? null,
                                            'description' => $event['description'] ?? null
                                        ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS), ENT_QUOTES, 'UTF-8') ?>)" title="הוסף ליומן">
                                            <img src="/assets/files/calendar-pen.svg" alt="הוסף ליומן">
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>


        <?php
        // Find schedule block
        $scheduleBlock = null;
        foreach ($blocks as $block) {
            if ($block['type'] === 'schedule') {
                $scheduleBlock = $block;
                break;
            }
        }
        
        // Prepare schedule data
        $scheduleData = null;
        $hasSchedule = false;
        $displayDayKey = null;
        $label = '';
        
        if ($scheduleBlock && !empty($scheduleBlock['data']['schedule'])) {
            $scheduleData = $scheduleBlock['data'];
            // Determine which day to show: tomorrow from 16:00, otherwise today
            $currentHour = (int)date('H');
            $currentDay = (int)date('w'); // 0 = Sunday, 6 = Saturday
            
            if ($currentHour >= 16) {
                // Show tomorrow's schedule
                $displayDay = ($currentDay + 1) % 7;
                $isTomorrow = true;
            } else {
                // Show today's schedule
                $displayDay = $currentDay;
                $isTomorrow = false;
            }
            
            $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            $dayNamesHe = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
            $displayDayKey = $dayNames[$displayDay];
            $displayDayName = $dayNamesHe[$displayDay];
            
            // Determine label
            if ($isTomorrow) {
                $label = 'מערכת שעות מחר - יום ' . $displayDayName;
            } else {
                $label = 'מערכת שעות היום - יום ' . $displayDayName;
            }
            
            $hasSchedule = isset($scheduleData['schedule'][$displayDayKey]) && !empty($scheduleData['schedule'][$displayDayKey]);
        }
        
        // Display homework until due date (today and future dates)
        $homeworkForToday = [];
        $todayDate = date('Y-m-d');
        $todayTimestamp = strtotime($todayDate);
        
        error_log("PublicController::page: Looking for homework with date >= " . $todayDate);
        error_log("PublicController::page: Total homework items: " . count($homework));
        
        foreach ($homework as $hw) {
            $hwDate = $hw['date'] ?? null;
            
            error_log("PublicController::page: Processing homework ID " . ($hw['id'] ?? 'unknown') . ", raw date: " . var_export($hwDate, true));
            
            // Handle date - it might be a DATE field or DATETIME
            if ($hwDate) {
                // If it's a DateTime object, convert to string
                if ($hwDate instanceof \DateTime) {
                    $hwDate = $hwDate->format('Y-m-d');
                } else {
                    // If it's a string, extract date part only
                    $hwDate = (string)$hwDate;
                    // Remove time if exists
                    if (strpos($hwDate, ' ') !== false) {
                        $dateParts = explode(' ', $hwDate);
                        $hwDate = $dateParts[0];
                    }
                    // Remove time if exists (format: Y-m-d H:i:s)
                    if (strpos($hwDate, 'T') !== false) {
                        $dateParts = explode('T', $hwDate);
                        $hwDate = $dateParts[0];
                    }
                }
                
                // Normalize date format to Y-m-d - handle edge cases
                if ($hwDate && strtotime($hwDate) !== false) {
                    $hwDate = date('Y-m-d', strtotime($hwDate));
                    $hwDateTimestamp = strtotime($hwDate);
                    
                    // Show homework if date is today or in the future
                    if ($hwDateTimestamp >= $todayTimestamp) {
                        $homeworkForToday[] = $hw;
                        error_log("PublicController::page: Added homework ID " . ($hw['id'] ?? 'unknown') . " to display list (date: " . $hwDate . ")");
                    } else {
                        error_log("PublicController::page: Skipped homework ID " . ($hw['id'] ?? 'unknown') . " - date is in the past (" . $hwDate . ")");
                    }
                } else {
                    error_log("PublicController::page: Invalid date format for homework ID " . ($hw['id'] ?? 'unknown') . ": " . var_export($hwDate, true));
                }
            } else {
                error_log("PublicController::page: Homework ID " . ($hw['id'] ?? 'unknown') . " has no date");
            }
        }
        
        // Sort homework by date (ascending - earliest first)
        usort($homeworkForToday, function($a, $b) {
            $dateA = $a['date'] ?? '';
            $dateB = $b['date'] ?? '';
            if (empty($dateA)) return 1;
            if (empty($dateB)) return -1;
            
            // Extract date part only
            if (strpos($dateA, ' ') !== false) {
                $dateA = explode(' ', $dateA)[0];
            }
            if (strpos($dateB, ' ') !== false) {
                $dateB = explode(' ', $dateB)[0];
            }
            
            return strtotime($dateA) <=> strtotime($dateB);
        });
        
        error_log("PublicController::page: Found " . count($homeworkForToday) . " homework items for display");
        
        $hasHomework = !empty($homeworkForToday);
        ?>
        
        <section class="blocks-section" id="blocks-section">
            <?php if (empty($blocks)): ?>
                <p class="empty-state"><?= htmlspecialchars($i18n->t('no_blocks'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <?php foreach ($blocks as $block): ?>
                    <div class="block-accordion" data-block-type="<?= htmlspecialchars($block['type'], ENT_QUOTES, 'UTF-8') ?>" data-block-id="<?= $block['id'] ?>">
                        <?php if ($isPageAdmin): ?>
                            <div class="drag-handle-block" title="גרור לסידור מחדש">
                                <img src="/assets/files/menu-burger.svg" alt="גרור">
                            </div>
                            <div class="block-edit-controls" onclick="event.stopPropagation();">
                                <button class="btn-block-menu" onclick="toggleBlockMenu(event, <?= $block['id'] ?>)" title="פעולות">
                                    <img src="/assets/files/menu-dots-vertical.svg" alt="פעולות">
                                </button>
                                <div class="block-menu-popup" id="block-menu-<?= $block['id'] ?>" onclick="event.stopPropagation();">
                                    <div class="block-menu-header">פעולות</div>
                                    <div class="block-menu-item" onclick="editBlock(<?= $block['id'] ?>); closeBlockMenu(<?= $block['id'] ?>);">
                                        <img src="/assets/files/pencil.svg" alt="עריכה">
                                        <span>עריכה</span>
                                    </div>
                                    <div class="block-menu-item" onclick="deleteBlock(<?= $block['id'] ?>); closeBlockMenu(<?= $block['id'] ?>);">
                                        <img src="/assets/files/trash.svg" alt="מחיקה">
                                        <span>מחיקה</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="block-card" onclick="toggleBlock(this)">
                            <div class="block-icon">
                                <?php
                                $icons = [
                                    'schedule' => '<div class="ue_icon_holder">
        <div class="ue_icon"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="uuid-4c73cb05-bea8-4bd3-9cc1-e9f09f0cc875" data-name="Layer 2" viewBox="0 0 14 14"><defs><clipPath id="uuid-2aacc2a0-1e16-453e-bd09-1c5dc32a8dbe"><rect width="14" height="14" style="fill: none;"></rect></clipPath></defs><g id="uuid-a9104946-7b70-472c-88eb-89fef438b49f" data-name="Layer 1"><g style="clip-path: url(#uuid-2aacc2a0-1e16-453e-bd09-1c5dc32a8dbe);"><g><path d="M5.5,6.5h3l-1.5,4.5" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M1.5,2.5c-.265220046043396,0-.519569993019104.10536003112793-.70710700750351.292890071868896-.187536001205444.18753981590271-.29289299249649.441890001296997-.29289299249649.707109928131104v9c0,.265199661254883.105356991291046.519599914550781.29289299249649.707099914550781.187537014484406.1875.441886961460114.292900085449219.70710700750351.292900085449219h11c.265199661254883,0,.519599914550781-.105400085449219.707099914550781-.292900085449219s.292900085449219-.441900253295898.292900085449219-.707099914550781V3.5c0-.265219926834106-.105400085449219-.519570112228394-.292900085449219-.707109928131104-.1875-.187530040740967-.441900253295898-.292890071868896-.707099914550781-.292890071868896h-2" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M3.5.5v4" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M10.5.5v4" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M3.5,2.5h5" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path></g></g></g></svg></div>
      </div>',
                                    'contacts' => '<div class="ue_icon_holder">
        <div class="ue_icon"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="uuid-415ff247-9827-41c6-8fb1-f9d26e462128" data-name="Layer 2" viewBox="0 0 14 14"><defs><clipPath id="uuid-c353ba4a-447e-4046-92bb-f207bb0d8d56"><rect width="14" height="14" style="fill: none;"></rect></clipPath></defs><g id="uuid-0e84c8b4-f2eb-4be8-ba23-8bccac0da319" data-name="Layer 1"><g style="clip-path: url(#uuid-c353ba4a-447e-4046-92bb-f207bb0d8d56);"><g><path d="M12,13.5H2c-.265220046043396,0-.519569993019104-.105400085449219-.707110047340393-.292900085449219-.187529921531677-.1875-.292889952659607-.441900253295898-.292889952659607-.707099914550781V1.5c0-.265220046043396.10536003112793-.519569993019104.292889952659607-.70710700750351.187540054321289-.187536001205444.441890001296997-.29289299249649.707110047340393-.29289299249649h10c.265199661254883,0,.519599914550781.105356991291046.707099914550781.29289299249649.1875.187537014484406.292900085449219.441886961460114.292900085449219.70710700750351v11c0,.265199661254883-.105400085449219.519599914550781-.292900085449219.707099914550781s-.441900253295898.292900085449219-.707099914550781.292900085449219Z" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M4,.5v13" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M7.5,4h2" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path></g></g></g></svg></div>
      </div>',
                                    'whatsapp' => '<div class="ue_icon_holder">
        <div class="ue_icon"><svg xmlns="http://www.w3.org/2000/svg" height="512pt" viewBox="0 0 512 512.00004" width="512pt"><path d="m436.8125 75.1875c-48.484375-48.484375-112.699219-75.1875-180.8125-75.1875-140.96875 0-256 115.046875-256 256 0 41.710938 10.242188 82.886719 29.675781 119.492188l-29.234375 117.898437c-1.265625 5.101563.234375 10.496094 3.953125 14.214844 3.695313 3.699219 9.082031 5.226562 14.214844 3.953125l117.898437-29.238282c36.605469 19.433594 77.78125 29.679688 119.492188 29.679688 68.113281 0 132.328125-26.703125 180.8125-75.1875 48.484375-48.488281 75.1875-112.699219 75.1875-180.8125s-26.703125-132.328125-75.1875-180.8125zm-180.8125 406.8125c-38.59375 0-76.65625-9.933594-110.082031-28.71875-3.328125-1.871094-7.246094-2.402344-10.960938-1.484375l-99.40625 24.652344 24.648438-99.410157c.921875-3.707031.390625-7.628906-1.480469-10.960937-18.789062-33.421875-28.71875-71.484375-28.71875-110.078125 0-124.617188 101.382812-226 226-226s226 101.382812 226 226-101.382812 226-226 226zm0 0"></path><path d="m391.367188 301.546875c-9.941407-9.945313-20.886719-19.984375-33.53125-26.082031-18.894532-9.105469-36.921876-6.496094-50.765626 7.347656-6.300781 6.300781-14.96875 18.722656-19.957031 24.832031-12.867187-2.152343-38.574219-23.828125-48.78125-34-10.167969-10.207031-31.828125-35.917969-33.980469-48.757812 6.074219-4.960938 18.539063-13.664063 24.835938-19.957031 13.84375-13.84375 16.453125-31.875 7.347656-50.765626-6.097656-12.648437-16.136718-23.589843-26.074218-33.527343-20.242188-20.667969-51.464844-22.191407-78.414063 4.761719-20.140625 20.136718-38.082031 57.46875-9.207031 120.964843 17.085937 37.5625 44.621094 70.785157 58.195312 84.496094l.105469.105469c13.710937 13.578125 46.933594 41.113281 84.496094 58.195312 42.421875 19.292969 87.890625 23.871094 120.96875-9.203125 27.355469-27.359375 25.050781-58.542969 4.761719-78.410156zm-25.976563 57.195313c-19.535156 19.535156-48.917969 20.582031-87.335937 3.109374-33.726563-15.339843-64.265626-40.789062-75.753907-52.152343-11.363281-11.492188-36.8125-42.027344-52.152343-75.753907-17.472657-38.417968-16.425782-67.800781 3.109374-87.335937 11.402344-11.402344 24.527344-16.460937 35.769532-4.980469l.214844.21875c26.628906 26.628906 24.738281 35.859375 18.730468 41.867188-5.332031 5.332031-21.09375 16.035156-26.1875 21.128906-11.957031 11.953125-9.730468 30.46875 6.613282 55.027344 9.753906 14.660156 22.636718 28.886718 28.714843 34.980468l.03125.035157c6.097657 6.078125 20.320313 18.960937 34.980469 28.714843 24.5625 16.34375 43.074219 18.566407 55.027344 6.613282 5.097656-5.097656 15.796875-20.855469 21.128906-26.1875 4.0625-4.0625 8.066406-5.613282 16.523438-1.535156 9.804687 4.726562 20.300781 15.222656 25.347656 20.265624l.21875.214844c11.570312 11.332032 6.425781 24.363282-4.980469 35.769532zm0 0"></path></svg></div>
      </div>',
                                    'links' => '<div class="ue_icon_holder">
        <div class="ue_icon"><!--?xml version="1.0" encoding="UTF-8"?-->
<svg id="uuid-7c66dece-c927-44d4-8879-37fec423214b" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14.002719855826399 14.011955863413277">
  <g id="uuid-36defd8d-1243-405e-a3da-8964f6493124" data-name="Layer 1">
    <g id="uuid-a3bd4770-8354-4ca2-8f7d-9c45e0f9ebf3" data-name="Group">
      <path id="uuid-3647a86a-da45-442c-882c-6d32bfe1ac19" data-name="Vector" d="M5.812589110934823,11.010153485031879l2.179999828338623,2.170000076293945c.133749961853027.137100219726562.300979614257812.236900329589844.485139846801758.28950023651123s.378870010375977.05620002746582.564860343933105.010499954223633c.187150001525879-.043499946594238.359959602355957-.134400367736816.501819610595703-.263999938964844.141850471496582-.129600524902344.247980117797852-.293499946594238.30817985534668-.47599983215332l3.579999923706055-10.730000376701355c.074700355529785-.20104992389679.090100288391113-.419319987297058.044400215148926-.628880023956299-.045700073242188-.209549903869629-.150599479675293-.401570975780487-.302299499511719-.553232967853546s-.343700408935547-.256586968898773-.553200721740723-.30230301618576c-.209599494934082-.045715987682343-.427899360656738-.030300974845886-.628899574279785.044414043426514L1.26258892019996,4.150153828354632c-.188750028610229.064469814300537-.35685795545578.178169727325439-.486952006816864.329360008239746-.130093991756439.151199817657471-.217453002929688.33437967300415-.253048002719879.5306396484375-.036740958690643.178490161895752-.028561949729919.36331033706665.023802042007446.537859916687012.052363991737366.174540042877197.147273004055023.33335018157959.276197969913483.462140083312988l2.739999949932098,2.739999771118164-.089999914169312,3.470000267028809,2.340000152587891-1.210000038146973Z" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path>
      <path id="uuid-2b389b44-b5d9-487e-910b-bef82557d81b" data-name="Vector 2" d="M13.112588824832528.790038598271167L3.562588872516244,8.750043583603656" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path>
    </g>
  </g>
</svg></div>
      </div>',
                                    'calendar' => '<div class="ue_icon_holder">
        <div class="ue_icon">
     <svg xmlns="http://www.w3.org/2000/svg" id="uuid-e05fd42a-e8c9-443e-99c7-0b6d8f369701" data-name="Layer 2" viewBox="0 0 14 14"><g id="uuid-516b99a0-e7d0-4f52-900c-853e93b541f7" data-name="Layer 1"><g><path d="M13.5,13.5h-.5c-.530400276184082,0-1.03909969329834-.210700035095215-1.414199829101562-.585800170898438s-.585800170898438-.88379955291748-.585800170898438-1.414199829101562c0,.530400276184082-.210700035095215,1.03909969329834-.585800170898438,1.414199829101562s-.883769989013672.585800170898438-1.414199829101562.585800170898438-1.039140224456787-.210700035095215-1.414209842681885-.585800170898438c-.375080108642578-.375100135803223-.585790157318115-.88379955291748-.585790157318115-1.414199829101562,0,.530400276184082-.210710048675537,1.03909969329834-.585790157318115,1.414199829101562-.375069618225098.375100135803223-.883780002593994.585800170898438-1.414209842681885.585800170898438s-1.039139986038208-.210700035095215-1.414210081100464-.585800170898438c-.375079870223999-.375100135803223-.585789918899536-.88379955291748-.585789918899536-1.414199829101562,0,.530400276184082-.210710048675537,1.03909969329834-.585789918899536,1.414199829101562-.375070095062256.375100135803223-.883780121803284.585800170898438-1.414210081100464.585800170898438h-.5" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M2.5,2.5c0-.53042995929718.210710048675537-1.039139986038208.585789918899536-1.414209961891174.375070095062256-.375076055526733.883780002593994-.585790038108826,1.414210081100464-.585790038108826.530429840087891,0,1.039140224456787.210713982582092,1.414209842681885.585790038108826.375080108642578.375069975852966.585790157318115.883780002593994.585790157318115,1.414209961891174v7" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M9.5.5c.530400276184082,0,1.03909969329834.210713982582092,1.414199829101562.585790038108826.375100135803223.375069975852966.585800170898438.883780002593994.585800170898438,1.414209961891174v7" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M6.5,4.5h5" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M6.5,7.5h5" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path></g></g></svg>
        </div>
      </div>',
                                    'alerts' => '<div class="ue_icon_holder">
        <div class="ue_icon"><!--?xml version="1.0" encoding="UTF-8"?-->
<svg id="uuid-7c66dece-c927-44d4-8879-37fec423214b" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14.002719855826399 14.011955863413277">
  <g id="uuid-36defd8d-1243-405e-a3da-8964f6493124" data-name="Layer 1">
    <g id="uuid-a3bd4770-8354-4ca2-8f7d-9c45e0f9ebf3" data-name="Group">
      <path id="uuid-3647a86a-da45-442c-882c-6d32bfe1ac19" data-name="Vector" d="M5.812589110934823,11.010153485031879l2.179999828338623,2.170000076293945c.133749961853027.137100219726562.300979614257812.236900329589844.485139846801758.28950023651123s.378870010375977.05620002746582.564860343933105.010499954223633c.187150001525879-.043499946594238.359959602355957-.134400367736816.501819610595703-.263999938964844.141850471496582-.129600524902344.247980117797852-.293499946594238.30817985534668-.47599983215332l3.579999923706055-10.730000376701355c.074700355529785-.20104992389679.090100288391113-.419319987297058.044400215148926-.628880023956299-.045700073242188-.209549903869629-.150599479675293-.401570975780487-.302299499511719-.553232967853546s-.343700408935547-.256586968898773-.553200721740723-.30230301618576c-.209599494934082-.045715987682343-.427899360656738-.030300974845886-.628899574279785.044414043426514L1.26258892019996,4.150153828354632c-.188750028610229.064469814300537-.35685795545578.178169727325439-.486952006816864.329360008239746-.130093991756439.151199817657471-.217453002929688.33437967300415-.253048002719879.5306396484375-.036740958690643.178490161895752-.028561949729919.36331033706665.023802042007446.537859916687012.052363991737366.174540042877197.147273004055023.33335018157959.276197969913483.462140083312988l2.739999949932098,2.739999771118164-.089999914169312,3.470000267028809,2.340000152587891-1.210000038146973Z" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path>
      <path id="uuid-2b389b44-b5d9-487e-910b-bef82557d81b" data-name="Vector 2" d="M13.112588824832528.790038598271167L3.562588872516244,8.750043583603656" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path>
    </g>
  </g>
</svg></div>
      </div>',
                                    'contact_page' => '<div class="ue_icon_holder">
        <div class="ue_icon"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="uuid-415ff247-9827-41c6-8fb1-f9d26e462128" data-name="Layer 2" viewBox="0 0 14 14"><defs><clipPath id="uuid-c353ba4a-447e-4046-92bb-f207bb0d8d56"><rect width="14" height="14" style="fill: none;"></rect></clipPath></defs><g id="uuid-0e84c8b4-f2eb-4be8-ba23-8bccac0da319" data-name="Layer 1"><g style="clip-path: url(#uuid-c353ba4a-447e-4046-92bb-f207bb0d8d56);"><g><path d="M12,13.5H2c-.265220046043396,0-.519569993019104-.105400085449219-.707110047340393-.292900085449219-.187529921531677-.1875-.292889952659607-.441900253295898-.292889952659607-.707099914550781V1.5c0-.265220046043396.10536003112793-.519569993019104.292889952659607-.70710700750351.187540054321289-.187536001205444.441890001296997-.29289299249649.707110047340393-.29289299249649h10c.265199661254883,0,.519599914550781.105356991291046.707099914550781.29289299249649.1875.187537014484406.292900085449219.441886961460114.292900085449219.70710700750351v11c0,.265199661254883-.105400085449219.519599914550781-.292900085449219.707099914550781s-.441900253295898.292900085449219-.707099914550781.292900085449219Z" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M4,.5v13" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path><path d="M7.5,4h2" style="fill: none; stroke: #000001; stroke-linecap: round; stroke-linejoin: round;"></path></g></g></g></svg></div>
      </div>'
                                ];
                                echo $icons[$block['type']] ?? '📄';
                                ?>
                            </div>
                            <div class="block-content">
                                <h4><?= htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <footer class="page-footer">
            <div class="page-footer-content">
                <div class="page-footer-left">
                    <?php if (!empty($pageAdmins)): ?>
                        <div class="page-admin-info">
                            <strong>מנהל הדף:</strong>
                            <?php foreach ($pageAdmins as $admin): ?>
                                <div class="page-admin-item">
                                    <span class="page-admin-name">
                                        <?= htmlspecialchars(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (!empty($admin['phone'])): ?>
                                        <div class="page-admin-actions">
                                            <a href="https://wa.me/<?= preg_replace('/\D/', '', $admin['phone']) ?>" target="_blank" class="admin-action-btn whatsapp" title="שלח וואטסאפ">
                                                <img src="/public/assets/files/phone-call.svg" alt="WhatsApp" style="height: 16px; filter: invert(1);">
                                            </a>
                                            <button onclick='downloadVCard(<?= json_encode($admin) ?>)' class="admin-action-btn vcard" title="הוסף לאנשי קשר">
                                                <img src="/public/assets/files/user-add.svg" alt="Add Contact" style="height: 16px;">
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="page-footer-right">
                    <?php if (!$isPageAdmin): ?>
                        <a href="/login" class="btn btn-login">התחברות</a>
                    <?php endif; ?>
            <button class="btn btn-share" onclick="sharePage()">
                <?= htmlspecialchars($i18n->t('share_page'), ENT_QUOTES, 'UTF-8') ?>
            </button>
                </div>
            </div>
        </footer>
    </div>

    <?php if ($isPageAdmin): ?>
        <!-- Floating Add Button Menu for Page Admins -->
        <div class="floating-add-menu">
            <button class="floating-add-block" onclick="toggleAddMenu()" title="הוסף">
                <img src="/assets/files/cross.svg" alt="הוסף" id="floating-add-icon" class="floating-add-icon">
            </button>
            <div id="floating-add-menu-items" class="floating-add-menu-items">
                <div class="floating-add-menu-overlay"></div>
                <div class="floating-add-menu-content">
                    <div class="modal-header-unified">
                        <h3>הוסף תוכן</h3>
                        <button class="modal-close-btn" onclick="toggleAddMenu()">
                            <img src="/assets/files/cross.svg" alt="סגור" style="height: 24px;">
                        </button>
                    </div>
                    <div class="block-options-card">
                    <div class="block-options-section">
                        <h3 class="block-options-section-title">ערוך בלוקים</h3>
                        <div class="block-options-list">
                            <?php 
                            // Map block types to display names
                            $blockTypeNames = [
                                'schedule' => '📅 מערכת שעות',
                                'contacts' => '👤 אנשי קשר חשובים',
                                'whatsapp' => '💬 קבוצות וואטסאפ',
                                'links' => '✈️ קישורים שימושיים',
                                'calendar' => '🏊 לוח חופשות וחגים',
                                'contact_page' => '📞 דף קשר'
                            ];
                            
                            // Required blocks that should always appear
                            $requiredBlockTypes = ['schedule', 'calendar', 'whatsapp', 'links', 'contact_page', 'contacts'];
                            
                            // Create a map of existing blocks by type
                            $existingBlocksByType = [];
                            foreach ($blocks as $block) {
                                $existingBlocksByType[$block['type']] = $block;
                            }
                            
                            // Display required blocks first, then other blocks
                            foreach ($requiredBlockTypes as $requiredType):
                                if (isset($existingBlocksByType[$requiredType])):
                                    $block = $existingBlocksByType[$requiredType];
                                    $blockTitle = $block['title'] ?? ($blockTypeNames[$requiredType] ?? $requiredType);
                                    $blockId = $block['id'];
                            ?>
                            <button class="block-option-item" onclick="editBlock(<?= $blockId ?>); toggleAddMenu();" title="<?= htmlspecialchars($blockTitle, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($blockTitle, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <?php 
                                endif;
                            endforeach;
                            
                            // Display other blocks that are not required
                            foreach ($blocks as $block):
                                if (!in_array($block['type'], $requiredBlockTypes, true)):
                                    $blockType = $block['type'];
                                    $blockTitle = $block['title'] ?? ($blockTypeNames[$blockType] ?? $blockType);
                                    $blockId = $block['id'];
                            ?>
                            <button class="block-option-item" onclick="editBlock(<?= $blockId ?>); toggleAddMenu();" title="<?= htmlspecialchars($blockTitle, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($blockTitle, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <?php 
                                endif;
                            endforeach;
                            
                            if (empty($blocks)):
                            ?>
                            <p style="color: #666; padding: 1rem; text-align: center;">אין בלוקים זמינים</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="block-options-section">
                        <h3 class="block-options-section-title">תוכן</h3>
                        <div class="block-options-list">
                            <button class="block-option-item" onclick="openQuickAddModal(); toggleAddMenu();" title="הוספה מהירה עם AI">
                                הוספה מהירה
                            </button>
                            <button class="block-option-item" onclick="openAddAnnouncementModal(); toggleAddMenu();" title="הוסף הודעה">
                                הוסף הודעה
                            </button>
                            <button class="block-option-item" onclick="openAddEventModal(); toggleAddMenu();" title="הוסף אירוע">
                                הוסף אירוע
                            </button>
                            <button class="block-option-item" onclick="openAddHomeworkModal(); toggleAddMenu();" title="הוסף שיעורי בית">
                                הוסף שיעורי בית
                            </button>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Add Modal -->
        <?php include __DIR__ . '/quick-add-modal.php'; ?>
        
        
        <!-- Edit Page Title Modal -->
        <div id="editPageTitleModal" class="modal" onclick="if(event.target === this) closeEditPageTitleModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header-unified">
                    <h3>ערוך כותרת הדף</h3>
                    <button class="modal-close-btn" onclick="closeEditPageTitleModal()">
                        <img src="/assets/files/cross.svg" alt="סגור" style="height: 24px;">
                    </button>
                </div>
                <div class="modal-body">
                <div id="editPageTitleMessage" class="message"></div>
                <form id="editPageTitleForm">
                    <div class="form-group">
                        <label>שם בית הספר</label>
                        <input type="text" id="editSchoolName" name="school_name" placeholder="שם בית הספר" required>
                    </div>
                    <div class="form-group">
                        <label>שם העיר</label>
                        <?php if (!empty($cities)): ?>
                            <select id="editCityName" name="city_name" class="city-select-input" required>
                                <option value="">בחר עיר</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>" 
                                            <?= (isset($page['city']) && $page['city'] === $city) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" id="editCityNameCustom" name="city_name_custom" placeholder="או הזן עיר אחרת" class="city-custom-input" style="display: none; margin-top: 0.5rem;">
                        <?php else: ?>
                            <input type="text" id="editCityName" name="city_name" placeholder="שם העיר" value="<?= htmlspecialchars($page['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>כיתה</label>
                        <select id="editClassGrade" name="class_grade" required>
                            <option value="">בחר כיתה</option>
                            <?php foreach(['א','ב','ג','ד','ה','ו','ז','ח','ט','י','יא','יב'] as $g): ?>
                                <option value="<?= $g ?>" <?= (isset($page['class_type']) && $page['class_type'] === $g) ? 'selected' : '' ?>><?= $g ?>'</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>מספר כיתה</label>
                        <input type="number" id="editClassNumber" name="class_number" placeholder="מספר כיתה" min="1" required>
                    </div>
                    <div class="modal-button-group">
                        <button type="button" class="btn btn-secondary" onclick="closeEditPageTitleModal()">ביטול</button>
                        <button type="submit" class="btn btn-primary">שמור</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Announcement Modal -->
        <div id="announcementModal" class="announcement-modal-fullscreen" onclick="if(event.target === this || event.target.classList.contains('announcement-modal-overlay')) closeAnnouncementModal()">
            <div class="announcement-modal-container" onclick="event.stopPropagation()">
                <div class="modal-header-unified">
                    <h2 id="announcementModalTitle">הוסף הודעה חדשה</h2>
                    <button class="modal-close-btn" onclick="closeAnnouncementModal()" title="סגור">
                        <img src="/assets/files/cross.svg" alt="סגור" style="height: 24px;">
                    </button>
                </div>
                <div class="announcement-modal-body">
                    <div id="announcementModalMessage" class="announcement-edit-message"></div>
                    <form id="announcementForm">
                        <div class="announcement-form-group">
                            <input type="text" id="announcementTitle" name="title" placeholder="כותרת ההודעה" class="announcement-title-input">
                        </div>
                        <div class="announcement-form-group">
                            <div id="announcementEditor" class="announcement-editor-wrapper"></div>
                        </div>
                        <div class="announcement-form-group announcement-date-type-group">
                            <div class="announcement-date-type-options">
                                <label class="announcement-date-type-label">
                                    <input type="radio" name="dateType" value="day" id="announcementDateTypeDay" onchange="toggleAnnouncementDateType()">
                                    <span>בחר יום</span>
                                </label>
                                <label class="announcement-date-type-label">
                                    <input type="radio" name="dateType" value="date" id="announcementDateTypeDate" onchange="toggleAnnouncementDateType()">
                                    <span>בחר תאריך</span>
                                </label>
                                <label class="announcement-date-type-label">
                                    <input type="radio" name="dateType" value="none" id="announcementDateTypeNone" checked onchange="toggleAnnouncementDateType()">
                                    <span>תמיד מוצג</span>
                                </label>
                            </div>
                        </div>
                        <div class="announcement-form-group announcement-selector-hidden" id="announcementDaySelector">
                            <select id="announcementDay" name="day" class="announcement-select-input">
                                <option value="">בחר יום</option>
                                <option value="0">יום ראשון</option>
                                <option value="1">יום שני</option>
                                <option value="2">יום שלישי</option>
                                <option value="3">יום רביעי</option>
                                <option value="4">יום חמישי</option>
                                <option value="5">יום שישי</option>
                                <option value="6">יום שבת</option>
                            </select>
                        </div>
                        <div class="announcement-form-group announcement-selector-hidden" id="announcementDateSelector">
                            <input type="date" id="announcementDate" name="date" class="announcement-date-input">
                        </div>
                    
                        <div class="announcement-form-group announcement-upload-group">
                            <label class="announcement-upload-label">העלה תמונה/מסמך (אופציונלי)</label>
                            <div class="upload-area" onclick="document.getElementById('announcementDocumentUpload').click()">
                                <p>לחץ להעלאת תמונה או גרור לכאן</p>
                                <input type="file" id="announcementDocumentUpload" accept="image/*" onchange="handleAnnouncementDocumentFileSelect(this)">
                            </div>
                            <div id="announcementDocumentPreview" class="document-preview"></div>
                            <div id="announcementDocumentProcessingStatus" class="document-processing-status">
                                <div class="spinner-container">
                                    <div class="spinner spinner-small"></div>
                                    <span class="spinner-text">מעבד תמונה... זה עשוי לקחת כמה שניות</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="announcement-modal-footer">
                    <button type="button" class="btn-announcement-edit-cancel" onclick="closeAnnouncementModal()">ביטול</button>
                    <button type="button" class="btn-announcement-edit-save" onclick="document.getElementById('announcementForm')?.requestSubmit()">שמירה</button>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Event Modal -->
        <div id="eventModal" class="announcement-modal-fullscreen" onclick="if(event.target === this || event.target.classList.contains('announcement-modal-overlay')) closeEventModal()">
            <div class="announcement-modal-container" onclick="event.stopPropagation()">
                <div class="modal-header-unified">
                    <h2 id="eventModalTitle">הוסף אירוע חדש</h2>
                    <button class="modal-close-btn" onclick="closeEventModal()" title="סגור">
                        <img src="/assets/files/cross.svg" alt="סגור" style="height: 24px;">
                    </button>
                </div>
                <div class="announcement-modal-body">
                    <div id="eventModalMessage" class="announcement-edit-message"></div>
                    <form id="eventForm">
                        <div class="announcement-form-group">
                            <input type="text" id="eventName" name="name" placeholder="שם האירוע *" class="announcement-title-input" required>
                        </div>
                        <div class="announcement-form-group">
                            <div class="datetime-input-wrapper">
                                <input type="date" id="eventDate" name="date" class="datetime-date-input" required>
                                <input type="time" id="eventTime" name="time" class="datetime-time-input">
                        </div>
                        </div>
                        <div class="announcement-form-group">
                            <input type="text" id="eventLocation" name="location" placeholder="מיקום האירוע (אופציונלי)" class="announcement-title-input">
                        </div>
                        <div class="announcement-form-group">
                            <textarea id="eventDescription" name="description" placeholder="תיאור האירוע" class="announcement-textarea-input"></textarea>
                        </div>
                        <div class="announcement-form-group">
                            <label class="announcement-checkbox-label">
                                <input type="checkbox" id="eventPublished" name="published" class="announcement-checkbox">
                                <span>פרסם אירוע (יאפשר הוספה ליומן)</span>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="announcement-modal-footer">
                    <button type="button" class="btn-announcement-edit-cancel" onclick="closeEventModal()">ביטול</button>
                    <button type="button" class="btn-announcement-edit-save" onclick="document.getElementById('eventForm')?.requestSubmit()">שמירה</button>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Homework Modal -->
        <div id="homeworkModal" class="announcement-modal-fullscreen" onclick="if(event.target === this || event.target.classList.contains('announcement-modal-overlay')) closeHomeworkModal()">
            <div class="announcement-modal-container" onclick="event.stopPropagation()">
                <div class="modal-header-unified">
                    <h2 id="homeworkModalTitle">הוסף שיעורי בית חדש</h2>
                    <button class="modal-close-btn" onclick="closeHomeworkModal()" title="סגור">
                        <img src="/assets/files/cross.svg" alt="סגור" style="height: 24px;">
                    </button>
                </div>
                <div class="announcement-modal-body">
                    <div id="homeworkModalMessage" class="announcement-edit-message"></div>
                    <form id="homeworkForm">
                        <div class="announcement-form-group">
                            <input type="text" id="homeworkTitle" name="title" placeholder="כותרת שיעורי הבית (אופציונלי)" class="announcement-title-input">
                        </div>
                        <div class="announcement-form-group">
                            <input type="date" id="homeworkDate" name="date" class="datetime-date-input" required>
                        </div>
                        <div class="announcement-form-group">
                            <div id="homeworkEditor" class="announcement-editor-wrapper"></div>
                        </div>
                        <div class="announcement-form-group announcement-upload-group">
                            <label class="announcement-upload-label">העלה תמונה/מסמך לעיבוד ב-AI (אופציונלי)</label>
                            <div class="upload-area" onclick="document.getElementById('homeworkDocumentUpload').click()">
                                <p>לחץ להעלאת תמונה או גרור לכאן</p>
                                <input type="file" id="homeworkDocumentUpload" accept="image/*" onchange="handleHomeworkDocumentFileSelect(this)">
                            </div>
                            <div id="homeworkDocumentPreview" class="document-preview"></div>
                            <div id="homeworkDocumentProcessingStatus" class="document-processing-status">
                                <div class="spinner-container">
                                    <div class="spinner spinner-small"></div>
                                    <span class="spinner-text">מעבד תמונה... זה עשוי לקחת כמה שניות</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="announcement-modal-footer">
                    <button type="button" class="btn-announcement-edit-cancel" onclick="closeHomeworkModal()">ביטול</button>
                    <button type="button" class="btn-announcement-edit-save" onclick="document.getElementById('homeworkForm')?.requestSubmit()">שמירה</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Announcement View Modal (Bottom Sheet) -->
    <div id="announcementFullViewModal" class="announcement-modal-fullscreen" onclick="if(event.target === this || event.target.classList.contains('announcement-modal-overlay')) closeAnnouncementFullViewModal()">
        <div class="announcement-modal-container" onclick="event.stopPropagation()">
            <div class="modal-header-unified">
                <h2 id="announcementFullViewModalTitle"></h2>
                <button class="modal-close-btn" onclick="closeAnnouncementFullViewModal()" title="סגור">
                    <img src="/assets/files/cross.svg" alt="סגור" style="height: 24px;">
                </button>
            </div>
            <div class="announcement-modal-body" id="announcementFullViewModalBody">
                <!-- Full announcement content will be loaded here -->
            </div>
            <div class="announcement-modal-footer">
                <button type="button" class="btn-announcement-edit-cancel" onclick="closeAnnouncementFullViewModal()">סגור</button>
            </div>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?= BASE_URL ?? '/' ?>';
        window.pageId = <?= $page['unique_numeric_id'] ?>;
        window.pageDbId = <?= $page['id'] ?>;
        window.isPageAdmin = <?= $isPageAdmin ? 'true' : 'false' ?>;
        window.csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
        const homework = <?= json_encode($homework ?? [], JSON_UNESCAPED_UNICODE) ?>;
        const pageData = <?= json_encode([
            'school_name' => $page['school_name'] ?? '',
            'city_name' => $page['city'] ?? '',
            'class_grade' => $page['class_type'] ?? '',
            'class_number' => $page['class_number'] ?? '',
            'class_title' => $page['class_title'] ?? ''
        ], JSON_UNESCAPED_UNICODE) ?>;
        <?php if ($hasSchedule && $scheduleData): ?>
        const scheduleDataForJS = <?= json_encode($scheduleData['schedule'], JSON_UNESCAPED_UNICODE) ?>;
        const defaultScheduleDay = '<?= htmlspecialchars($displayDayKey, ENT_QUOTES, 'UTF-8') ?>';
        <?php else: ?>
        const scheduleDataForJS = null;
        const defaultScheduleDay = null;
        <?php endif; ?>
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1/dist/confetti.browser.min.js"></script>
    <?php if ($isPageAdmin): ?>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">
        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <?php endif; ?>
    <script src="/public/assets/js/public.js"></script>
    <?php if ($isPageAdmin): ?>
        <script src="/public/assets/js/quick-add.js"></script>
    <?php endif; ?>
    <script>
        function downloadVCard(admin) {
            const firstName = admin.first_name || '';
            const lastName = admin.last_name || '';
            const phone = admin.phone || '';
            const email = admin.email || '';
            
            const vcard = `BEGIN:VCARD
VERSION:3.0
FN:${firstName} ${lastName}
N:${lastName};${firstName};;;
TEL;TYPE=CELL:${phone}
EMAIL;TYPE=INTERNET:${email}
END:VCARD`;

            const blob = new Blob([vcard], { type: 'text/vcard' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${firstName}_${lastName}.vcf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        function toggleAnnouncement(id) {
            // Close any open menus
            document.querySelectorAll('.announcement-menu-popup').forEach(menu => {
                menu.classList.remove('active');
            });
            
            const content = document.getElementById('announcement-content-' + id);
            const btn = document.querySelector('.read-more-btn[data-id="' + id + '"]');
            
            // Only toggle if both content and button exist
            if (!content || !btn) return;
            
            if (content.style.display === 'none' || content.classList.contains('hidden')) {
                content.style.display = 'block';
                content.classList.remove('hidden');
                if (btn) btn.textContent = 'סגור ×';
            } else {
                content.style.display = 'none';
                content.classList.add('hidden');
                if (btn) btn.textContent = 'קרא עוד ›';
            }
        }
    </script>
</body>
</html>

