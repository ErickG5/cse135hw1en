<?php

function getTrafficReportData(PDO $pdo, string $startDate, string $endDate): array
{
    $sessionsStmt = $pdo->prepare("
        SELECT
            DATE(created_at) AS day,
            COUNT(DISTINCT session_id) AS session_count
        FROM events
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $sessionsStmt->execute([$startDate, $endDate]);
    $sessionsRaw = $sessionsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $sessionsByDay = [];
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);

    while ($current <= $end) {
        $day = $current->format('Y-m-d');
        $sessionsByDay[$day] = (int)($sessionsRaw[$day] ?? 0);
        $current->modify('+1 day');
    }

    $uaStmt = $pdo->prepare("
        SELECT
            JSON_UNQUOTE(
                COALESCE(
                    JSON_EXTRACT(payload, '$.technographics.userAgent'),
                    JSON_EXTRACT(payload, '$.userAgent')
                )
            ) AS user_agent
        FROM events e1
        WHERE event_type = 'capability'
          AND DATE(created_at) BETWEEN ? AND ?
          AND (
                JSON_EXTRACT(payload, '$.technographics.userAgent') IS NOT NULL
             OR JSON_EXTRACT(payload, '$.userAgent') IS NOT NULL
          )
          AND id = (
                SELECT MIN(id)
                FROM events e2
                WHERE e2.session_id = e1.session_id
                  AND e2.event_type = 'capability'
                  AND (
                        JSON_EXTRACT(e2.payload, '$.technographics.userAgent') IS NOT NULL
                     OR JSON_EXTRACT(e2.payload, '$.userAgent') IS NOT NULL
                  )
          )
    ");
    $uaStmt->execute([$startDate, $endDate]);
    $uaRows = $uaStmt->fetchAll(PDO::FETCH_ASSOC);

    $browserCounts = [
        'Chrome'  => 0,
        'Firefox' => 0,
        'Edge'    => 0,
        'Safari'  => 0,
        'Other'   => 0,
    ];

    foreach ($uaRows as $row) {
        $ua = $row['user_agent'] ?? '';
        if ($ua === '' || $ua === 'null') {
            continue;
        }

        if (stripos($ua, 'Edg/') !== false || stripos($ua, 'EdgA/') !== false) {
            $browser = 'Edge';
        } elseif (stripos($ua, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Safari') !== false) {
            $browser = 'Safari';
        } else {
            $browser = 'Other';
        }

        $browserCounts[$browser]++;
    }

    $topPagesStmt = $pdo->prepare("
        SELECT
            page,
            COUNT(*) AS event_count,
            COUNT(DISTINCT session_id) AS session_count
        FROM events
        WHERE DATE(created_at) BETWEEN ? AND ?
          AND page IS NOT NULL
          AND page <> ''
        GROUP BY page
        ORDER BY session_count DESC, event_count DESC
        LIMIT 10
    ");
    $topPagesStmt->execute([$startDate, $endDate]);
    $topPages = $topPagesStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'sessions_by_day' => $sessionsByDay,
        'browser_counts' => $browserCounts,
        'top_pages' => $topPages,
        'notes' => 'Traffic patterns are best interpreted using the sessions trend, browser share, and top pages together. Browser distribution helps prioritize testing, while top pages show which content receives the most attention.'
    ];
}

function getTrafficKpis(PDO $pdo, string $startDate, string $endDate): array
{
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT session_id) AS total_sessions,
            COUNT(*) AS total_events,
            COUNT(DISTINCT page) AS unique_pages
        FROM events
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total_sessions' => (int)($row['total_sessions'] ?? 0),
        'total_events' => (int)($row['total_events'] ?? 0),
        'unique_pages' => (int)($row['unique_pages'] ?? 0),
    ];
}



function getPerformanceReportData(PDO $pdo, string $startDate, string $endDate): array
{
    $loadStmt = $pdo->prepare("
        SELECT
            CAST(JSON_EXTRACT(payload, '$.timing.loadEvent') AS DECIMAL(10,2)) AS load_ms,
            page,
            DATE(created_at) AS day
        FROM events
        WHERE event_type = 'capability'
          AND JSON_CONTAINS_PATH(payload, 'one', '$.timing.loadEvent')
          AND CAST(JSON_EXTRACT(payload, '$.timing.loadEvent') AS DECIMAL(10,2)) > 0
          AND DATE(created_at) BETWEEN ? AND ?
        ORDER BY id DESC
        LIMIT 1000
    ");
    $loadStmt->execute([$startDate, $endDate]);
    $rows = $loadStmt->fetchAll(PDO::FETCH_ASSOC);

    $loadBuckets = [
        '0-200ms' => 0,
        '200-400ms' => 0,
        '400-600ms' => 0,
        '600-800ms' => 0,
        '800ms-1s' => 0,
        '1s+' => 0,
    ];

    $pageTotals = [];
    $pageCounts = [];
    $dayTotals = [];
    $dayCounts = [];

    foreach ($rows as $row) {
        $ms = (float)($row['load_ms'] ?? 0);
        $page = trim((string)($row['page'] ?? ''));
        $day = (string)($row['day'] ?? '');

        if ($ms < 200) $loadBuckets['0-200ms']++;
        elseif ($ms < 400) $loadBuckets['200-400ms']++;
        elseif ($ms < 600) $loadBuckets['400-600ms']++;
        elseif ($ms < 800) $loadBuckets['600-800ms']++;
        elseif ($ms < 1000) $loadBuckets['800ms-1s']++;
        else $loadBuckets['1s+']++;

        if ($page !== '') {
            $pageTotals[$page] = ($pageTotals[$page] ?? 0) + $ms;
            $pageCounts[$page] = ($pageCounts[$page] ?? 0) + 1;
        }

        if ($day !== '') {
            $dayTotals[$day] = ($dayTotals[$day] ?? 0) + $ms;
            $dayCounts[$day] = ($dayCounts[$day] ?? 0) + 1;
        }
    }

    $slowPages = [];
    foreach ($pageTotals as $page => $total) {
        $slowPages[] = [
            'page' => $page,
            'avg_load_ms' => round($total / max(1, $pageCounts[$page]), 2),
            'samples' => $pageCounts[$page],
        ];
    }
    usort($slowPages, fn($a, $b) => $b['avg_load_ms'] <=> $a['avg_load_ms']);
    $slowPages = array_slice($slowPages, 0, 10);

    $avgByDay = [];
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);

    while ($current <= $end) {
        $day = $current->format('Y-m-d');
        if (!empty($dayCounts[$day])) {
            $avgByDay[$day] = round($dayTotals[$day] / $dayCounts[$day], 2);
        } else {
            $avgByDay[$day] = 0;
        }
        $current->modify('+1 day');
    }

    return [
        'load_buckets' => $loadBuckets,
        'slow_pages' => $slowPages,
        'avg_load_by_day' => $avgByDay,
        'notes' => 'Performance should be judged by both distribution and trend. Slow-page rankings identify the highest-value optimization targets, while daily averages show whether performance is improving or regressing over time.'
    ];
}


function getBehaviorReportData(PDO $pdo, string $startDate, string $endDate): array
{
    $behaviorStmt = $pdo->prepare("
        SELECT event_type, COUNT(*) AS total
        FROM events
        WHERE event_type IN ('activity', 'activity_batch', 'pageview', 'page_exit')
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY event_type
        ORDER BY total DESC
    ");
    $behaviorStmt->execute([$startDate, $endDate]);
    $behaviorRows = $behaviorStmt->fetchAll(PDO::FETCH_ASSOC);

    $eventCounts = [];
    foreach ($behaviorRows as $row) {
        $eventCounts[$row['event_type']] = (int)$row['total'];
    }

    $topPagesStmt = $pdo->prepare("
        SELECT
            page,
            COUNT(*) AS event_count,
            COUNT(DISTINCT session_id) AS session_count
        FROM events
        WHERE event_type IN ('activity', 'activity_batch', 'pageview', 'page_exit')
          AND DATE(created_at) BETWEEN ? AND ?
          AND page IS NOT NULL
          AND page <> ''
        GROUP BY page
        ORDER BY event_count DESC
        LIMIT 10
    ");
    $topPagesStmt->execute([$startDate, $endDate]);
    $topPages = $topPagesStmt->fetchAll(PDO::FETCH_ASSOC);

    $pagesPerSessionStmt = $pdo->prepare("
        SELECT
            session_id,
            COUNT(DISTINCT page) AS page_count
        FROM events
        WHERE DATE(created_at) BETWEEN ? AND ?
          AND page IS NOT NULL
          AND page <> ''
        GROUP BY session_id
    ");
    $pagesPerSessionStmt->execute([$startDate, $endDate]);
    $sessionRows = $pagesPerSessionStmt->fetchAll(PDO::FETCH_ASSOC);

    $sessionBuckets = [
        '1 page' => 0,
        '2 pages' => 0,
        '3 pages' => 0,
        '4+ pages' => 0,
    ];

    foreach ($sessionRows as $row) {
        $count = (int)$row['page_count'];
        if ($count <= 1) $sessionBuckets['1 page']++;
        elseif ($count === 2) $sessionBuckets['2 pages']++;
        elseif ($count === 3) $sessionBuckets['3 pages']++;
        else $sessionBuckets['4+ pages']++;
    }

    return [
        'event_counts' => $eventCounts,
        'top_pages' => $topPages,
        'session_buckets' => $sessionBuckets,
        'notes' => 'Behavior analytics is most useful when it combines interaction counts with depth of engagement. Pages-per-session distribution helps reveal whether users explore the site or leave quickly.'
    ];
}


function getErrorReportData(PDO $pdo, string $startDate, string $endDate): array
{
    $errorStmt = $pdo->prepare("
        SELECT DATE(created_at) AS day, COUNT(*) AS total
        FROM events
        WHERE event_type = 'error'
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $errorStmt->execute([$startDate, $endDate]);
    $errorRows = $errorStmt->fetchAll(PDO::FETCH_ASSOC);

    $errorsByDay = [];
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);

    while ($current <= $end) {
        $day = $current->format('Y-m-d');
        $errorsByDay[$day] = 0;
        $current->modify('+1 day');
    }

    foreach ($errorRows as $row) {
        $errorsByDay[$row['day']] = (int)$row['total'];
    }

    $errorPagesStmt = $pdo->prepare("
        SELECT
            page,
            COUNT(*) AS error_count
        FROM events
        WHERE event_type = 'error'
          AND DATE(created_at) BETWEEN ? AND ?
          AND page IS NOT NULL
          AND page <> ''
        GROUP BY page
        ORDER BY error_count DESC
        LIMIT 10
    ");
    $errorPagesStmt->execute([$startDate, $endDate]);
    $topErrorPages = $errorPagesStmt->fetchAll(PDO::FETCH_ASSOC);

    $errorTypesStmt = $pdo->prepare("
        SELECT
            COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(payload, '$.error.type')),
                'unknown'
            ) AS error_type,
            COUNT(*) AS error_count
        FROM events
        WHERE event_type = 'error'
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.error.type')), 'unknown')
        ORDER BY error_count DESC
        LIMIT 10
    ");
    $errorTypesStmt->execute([$startDate, $endDate]);
    $topErrorTypes = $errorTypesStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'errors_by_day' => $errorsByDay,
        'top_error_pages' => $topErrorPages,
        'top_error_types' => $topErrorTypes,
        'notes' => 'Error reports should be prioritized by where failures occur and how often they recur. The most valuable pages to investigate first are those with the highest user-facing error counts.'
    ];
}


?>
