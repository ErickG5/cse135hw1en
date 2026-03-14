<?php

function generateTrafficInsight(int $sessions, int $prevSessions): string
{
    if ($prevSessions <= 0) {
        return 'No previous traffic data exists for comparison.';
    }

    $change = round((($sessions - $prevSessions) / $prevSessions) * 100, 1);

    if ($change > 20) {
        return "Traffic increased significantly by {$change}% compared to the previous period. This may indicate successful marketing campaigns or external referrals.";
    }

    if ($change > 5) {
        return "Traffic shows moderate growth of {$change}% compared to the previous period.";
    }

    if ($change > -5) {
        return "Traffic levels remain relatively stable with only {$change}% change.";
    }

    if ($change > -20) {
        return "Traffic decreased by " . abs($change) . "% compared to the previous period and should be monitored.";
    }

    return "Traffic dropped significantly by " . abs($change) . "%. This may indicate outages, SEO issues, or reduced user engagement.";
}

function generatePerformanceInsight(float $avgLoad, float $prevAvgLoad): string
{
    if ($prevAvgLoad <= 0) {
        if ($avgLoad <= 0) {
            return 'No previous performance data exists for comparison.';
        }
        return "Average load time for this period is {$avgLoad} ms.";
    }

    $change = round((($avgLoad - $prevAvgLoad) / $prevAvgLoad) * 100, 1);

    if ($change > 20) {
        return "Average load time worsened significantly by {$change}% compared to the previous period. Performance should be investigated.";
    }

    if ($change > 5) {
        return "Average load time is moderately slower by {$change}% compared to the previous period.";
    }

    if ($change > -5) {
        return "Average load time is relatively stable with only {$change}% change compared to the previous period.";
    }

    if ($change > -20) {
        return "Average load time improved by " . abs($change) . "% compared to the previous period.";
    }

    return "Average load time improved significantly by " . abs($change) . "% compared to the previous period.";
}

function generateBehaviorInsight(int $events, int $prevEvents): string
{
    if ($prevEvents <= 0) {
        return 'No previous behavior data exists for comparison.';
    }

    $change = round((($events - $prevEvents) / $prevEvents) * 100, 1);

    if ($change > 20) {
        return "Interaction volume increased significantly by {$change}% compared to the previous period, suggesting stronger engagement.";
    }

    if ($change > 5) {
        return "Interaction volume increased moderately by {$change}% compared to the previous period.";
    }

    if ($change > -5) {
        return "Behavior activity remains relatively stable with only {$change}% change.";
    }

    if ($change > -20) {
        return "Interaction volume decreased by " . abs($change) . "% compared to the previous period and should be monitored.";
    }

    return "Interaction volume dropped significantly by " . abs($change) . "% compared to the previous period, which may indicate weaker engagement.";
}

function generateErrorInsight(int $errors, int $prevErrors): string
{
    if ($prevErrors <= 0) {
        if ($errors === 0) {
            return 'No previous error data exists for comparison.';
        }
        return "This period recorded {$errors} errors with no previous comparison baseline.";
    }

    $change = round((($errors - $prevErrors) / $prevErrors) * 100, 1);

    if ($change > 20) {
        return "Error volume increased significantly by {$change}% compared to the previous period and should be investigated.";
    }

    if ($change > 5) {
        return "Error volume increased moderately by {$change}% compared to the previous period.";
    }

    if ($change > -5) {
        return "Error volume is relatively stable with only {$change}% change compared to the previous period.";
    }

    if ($change > -20) {
        return "Error volume improved by " . abs($change) . "% compared to the previous period.";
    }

    return "Error volume improved significantly by " . abs($change) . "% compared to the previous period.";
}
?>
