
<?php

function getReportDateRange(): array
{
    $today = date('Y-m-d');
    $defaultStart = date('Y-m-d', strtotime('-29 days'));

    $range = $_GET['range'] ?? '';

    if ($range === '7') {
        $start = date('Y-m-d', strtotime('-6 days'));
        $end = $today;
        $label = 'Last 7 Days';
    } elseif ($range === '14') {
        $start = date('Y-m-d', strtotime('-13 days'));
        $end = $today;
        $label = 'Last 14 Days';
    } elseif ($range === '30') {
        $start = date('Y-m-d', strtotime('-29 days'));
        $end = $today;
        $label = 'Last 30 Days';
    } else {
        $start = $_GET['start_date'] ?? $defaultStart;
        $end = $_GET['end_date'] ?? $today;

        $startObj = DateTime::createFromFormat('Y-m-d', $start);
        $endObj = DateTime::createFromFormat('Y-m-d', $end);

        if (!$startObj || $startObj->format('Y-m-d') !== $start) {
            $start = $defaultStart;
        }

        if (!$endObj || $endObj->format('Y-m-d') !== $end) {
            $end = $today;
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $label = $start . ' to ' . $end;
        $range = '';
    }

    return [
        'start_date' => $start,
        'end_date' => $end,
        'label' => $label,
        'range' => $range
    ];
}

function getPreviousDateRange(string $startDate, string $endDate): array
{
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);

    $days = $start->diff($end)->days + 1;

    $prevEnd = clone $start;
    $prevEnd->modify('-1 day');

    $prevStart = clone $prevEnd;
    $prevStart->modify('-' . ($days - 1) . ' days');

    return [
        'start_date' => $prevStart->format('Y-m-d'),
        'end_date' => $prevEnd->format('Y-m-d')
    ];
}

function formatDelta(float $current, float $previous): array
{
    if ($previous <= 0) {
        return [
            'text' => '—',
            'class' => '',
            'raw' => null
        ];
    }

    $delta = round((($current - $previous) / $previous) * 100, 1);

    if ($delta > 0) {
        return [
            'text' => '↑ ' . $delta . '%',
            'class' => 'kpi-up',
            'raw' => $delta
        ];
    }

    if ($delta < 0) {
        return [
            'text' => '↓ ' . abs($delta) . '%',
            'class' => 'kpi-down',
            'raw' => $delta
        ];
    }

    return [
        'text' => '0%',
        'class' => 'kpi-flat',
        'raw' => 0
    ];
}

function activeRangeClass(string $current, string $value): string
{
    return $current === $value ? 'quick-range-btn active' : 'quick-range-btn';
}
?>
