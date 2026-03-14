<?php

function generateChartPng(array $labels, array $values, string $baseName, string $datasetLabel = 'Value', string $type = 'line'): string
{
    $rootDir = realpath(__DIR__ . '/../../');
    $publicDir = realpath(__DIR__ . '/../');
    $outputDir = $publicDir . '/generated_charts';

    if (!is_dir($outputDir)) {
        throw new RuntimeException('Chart output directory does not exist.');
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    $timestamp = date('Ymd_His');

    $jsonPath = $outputDir . '/' . $safeBase . '_' . $timestamp . '.json';
    $pngPath = $outputDir . '/' . $safeBase . '_' . $timestamp . '.png';

    $payload = [
        'labels' => array_values($labels),
        'values' => array_values($values),
        'datasetLabel' => $datasetLabel,
        'type' => $type,
    ];

    file_put_contents($jsonPath, json_encode($payload, JSON_UNESCAPED_SLASHES));

    $scriptPath = $rootDir . '/render_line_chart.js';

    $command = sprintf(
        'node %s %s %s 2>&1',
        escapeshellarg($scriptPath),
        escapeshellarg($jsonPath),
        escapeshellarg($pngPath)
    );

    exec($command, $output, $exitCode);

    if ($exitCode !== 0 || !file_exists($pngPath)) {
        @unlink($jsonPath);
        throw new RuntimeException("Failed to generate chart image: " . implode("\n", $output));
    }

    @unlink($jsonPath);

    return '/generated_charts/' . basename($pngPath);
}

function generateLineChartPng(array $labels, array $values, string $baseName, string $datasetLabel = 'Value'): string
{
    return generateChartPng($labels, $values, $baseName, $datasetLabel, 'line');
}

function generateBarChartPng(array $labels, array $values, string $baseName, string $datasetLabel = 'Value'): string
{
    return generateChartPng($labels, $values, $baseName, $datasetLabel, 'bar');
}
?>
