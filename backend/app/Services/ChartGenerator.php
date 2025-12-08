<?php

namespace App\Services;

/**
 * Helper service for generating QuickChart.io URLs
 */
class ChartGenerator
{
    private const QUICKCHART_API = 'https://quickchart.io/chart';

    /**
     * Generate bar chart URL
     */
    public function generateBarChart(array $data, string $title, array $options = []): string
    {
        $labels = array_column($data, 'label');
        $values = array_column($data, 'value');

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => $options['datasetLabel'] ?? $title,
                        'data' => $values,
                        'backgroundColor' => $options['backgroundColor'] ?? 'rgba(102, 126, 234, 0.8)',
                        'borderColor' => $options['borderColor'] ?? 'rgba(102, 126, 234, 1)',
                        'borderWidth' => 2,
                    ]
                ],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => $title,
                    'fontSize' => 18,
                    'fontColor' => '#333',
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ]
                    ],
                ],
                'legend' => [
                    'display' => false,
                ],
            ],
        ];

        return $this->buildChartUrl($config, $options);
    }

    /**
     * Generate line chart URL
     */
    public function generateLineChart(array $data, string $title, array $options = []): string
    {
        $labels = array_column($data, 'label');
        $values = array_column($data, 'value');

        $config = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => $options['datasetLabel'] ?? $title,
                        'data' => $values,
                        'fill' => $options['fill'] ?? false,
                        'borderColor' => $options['borderColor'] ?? 'rgba(102, 126, 234, 1)',
                        'backgroundColor' => $options['backgroundColor'] ?? 'rgba(102, 126, 234, 0.2)',
                        'tension' => 0.4,
                        'borderWidth' => 3,
                    ]
                ],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => $title,
                    'fontSize' => 18,
                    'fontColor' => '#333',
                ],
                'scales' => [
                    'yAxes' => [
                        [
                            'ticks' => [
                                'beginAtZero' => true,
                            ],
                        ]
                    ],
                ],
            ],
        ];

        return $this->buildChartUrl($config, $options);
    }

    /**
     * Generate pie chart URL
     */
    public function generatePieChart(array $data, string $title, array $options = []): string
    {
        $labels = array_column($data, 'label');
        $values = array_column($data, 'value');

        $config = [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $values,
                        'backgroundColor' => $options['colors'] ?? [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(118, 75, 162, 0.8)',
                            'rgba(255, 222, 99, 0.8)',
                            'rgba(255, 188, 76, 0.8)',
                            'rgba(94, 138, 255, 0.8)',
                        ],
                    ]
                ],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => $title,
                    'fontSize' => 18,
                    'fontColor' => '#333',
                ],
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ];

        return $this->buildChartUrl($config, $options);
    }

    /**
     * Generate doughnut chart URL
     */
    public function generateDoughnutChart(array $data, string $title, array $options = []): string
    {
        $labels = array_column($data, 'label');
        $values = array_column($data, 'value');

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $values,
                        'backgroundColor' => $options['colors'] ?? [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(118, 75, 162, 0.8)',
                            'rgba(255, 222, 99, 0.8)',
                            'rgba(255, 188, 76, 0.8)',
                        ],
                    ]
                ],
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => $title,
                    'fontSize' => 18,
                    'fontColor' => '#333',
                ],
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ];

        return $this->buildChartUrl($config, $options);
    }

    /**
     * Build QuickChart.io URL from configuration
     */
    private function buildChartUrl(array $config, array $options = []): string
    {
        $width = $options['width'] ?? 600;
        $height = $options['height'] ?? 400;
        $format = $options['format'] ?? 'png';

        $params = [
            'c' => json_encode($config),
            'width' => $width,
            'height' => $height,
            'format' => $format,
            'backgroundColor' => $options['chartBackgroundColor'] ?? 'white',
        ];

        return self::QUICKCHART_API . '?' . http_build_query($params);
    }

    /**
     * Generate multiple charts at once for summary reports
     */
    public function generateSummaryCharts(array $summaryData): array
    {
        $charts = [];

        // Events by Type (Pie Chart)
        if (isset($summaryData['eventsByType'])) {
            $charts[] = [
                'title' => 'Events by Type Distribution',
                'url' => $this->generatePieChart(
                    $summaryData['eventsByType'],
                    'Events by Type',
                    ['width' => 500, 'height' => 350]
                ),
                'description' => 'Breakdown of all course events by type.',
            ];
        }

        // Daily Activity Trend (Line Chart)
        if (isset($summaryData['dailyActivity'])) {
            $charts[] = [
                'title' => 'Daily Activity Trend',
                'url' => $this->generateLineChart(
                    $summaryData['dailyActivity'],
                    'Daily Activity Over Time',
                    ['width' => 700, 'height' => 350, 'fill' => true]
                ),
                'description' => 'Trend of student activity across the reporting period.',
            ];
        }

        // Top Courses (Bar Chart)
        if (isset($summaryData['topCourses'])) {
            $charts[] = [
                'title' => 'Top 10 Most Active Courses',
                'url' => $this->generateBarChart(
                    array_slice($summaryData['topCourses'], 0, 10),
                    'Top Courses by Activity',
                    ['width' => 700, 'height' => 400]
                ),
                'description' => 'Courses with the highest student engagement.',
            ];
        }

        return $charts;
    }
}
