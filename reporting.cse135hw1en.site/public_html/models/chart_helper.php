<?php

function buildSimpleLineChartSvg(array $labels, array $values, int $width = 700, int $height = 260): string
{
    if (count($labels) === 0 || count($values) === 0) {
        return '<p>No chart data available.</p>';
    }

    $paddingLeft = 50;
    $paddingRight = 20;
    $paddingTop = 20;
    $paddingBottom = 40;

    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - $paddingTop - $paddingBottom;

    $maxValue = max($values);
    if ($maxValue <= 0) {
        $maxValue = 1;
    }

    $count = count($values);
    $stepX = ($count > 1) ? ($plotWidth / ($count - 1)) : 0;

    $points = [];
    foreach ($values as $i => $value) {
        $x = $paddingLeft + ($i * $stepX);
        $y = $paddingTop + $plotHeight - (($value / $maxValue) * $plotHeight);
        $points[] = round($x, 2) . ',' . round($y, 2);
    }

    $polylinePoints = implode(' ', $points);

    $gridLines = '';
    $yLabels = '';
    $ticks = 5;

    for ($i = 0; $i <= $ticks; $i++) {
        $tickValue = ($maxValue / $ticks) * $i;
        $y = $paddingTop + $plotHeight - (($tickValue / $maxValue) * $plotHeight);

        $gridLines .= '<line x1="' . $paddingLeft . '" y1="' . round($y, 2) . '" x2="' . ($paddingLeft + $plotWidth) . '" y2="' . round($y, 2) . '" stroke="#dddddd" stroke-width="1" />';
        $yLabels .= '<text x="' . ($paddingLeft - 8) . '" y="' . (round($y, 2) + 4) . '" font-size="10" text-anchor="end" fill="#555">' . (int)round($tickValue) . '</text>';
    }

    $xLabels = '';
    $labelStep = max(1, (int)ceil($count / 8));

    foreach ($labels as $i => $label) {
        if ($i % $labelStep !== 0 && $i !== $count - 1) {
            continue;
        }

        $x = $paddingLeft + ($i * $stepX);
        $safeLabel = htmlspecialchars(substr((string)$label, 5), ENT_QUOTES, 'UTF-8'); // show MM-DD
        $xLabels .= '<text x="' . round($x, 2) . '" y="' . ($paddingTop + $plotHeight + 18) . '" font-size="10" text-anchor="middle" fill="#555">' . $safeLabel . '</text>';
    }

    $circles = '';
    foreach ($values as $i => $value) {
        $x = $paddingLeft + ($i * $stepX);
        $y = $paddingTop + $plotHeight - (($value / $maxValue) * $plotHeight);
        $circles .= '<circle cx="' . round($x, 2) . '" cy="' . round($y, 2) . '" r="2.5" fill="#2563eb" />';
    }

    return '
<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
    <rect x="0" y="0" width="' . $width . '" height="' . $height . '" fill="#ffffff" />
    ' . $gridLines . '
    <line x1="' . $paddingLeft . '" y1="' . ($paddingTop + $plotHeight) . '" x2="' . ($paddingLeft + $plotWidth) . '" y2="' . ($paddingTop + $plotHeight) . '" stroke="#333" stroke-width="1" />
    <line x1="' . $paddingLeft . '" y1="' . $paddingTop . '" x2="' . $paddingLeft . '" y2="' . ($paddingTop + $plotHeight) . '" stroke="#333" stroke-width="1" />
    <polyline fill="none" stroke="#2563eb" stroke-width="2" points="' . $polylinePoints . '" />
    ' . $circles . '
    ' . $yLabels . '
    ' . $xLabels . '
</svg>';
}
?>
