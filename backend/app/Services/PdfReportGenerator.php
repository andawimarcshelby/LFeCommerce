<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;

class PdfReportGenerator
{
    private int $rowsPerSection;
    private string $tempDir;

    public function __construct()
    {
        $this->rowsPerSection = config('app.report_pdf_max_rows_per_section', 2000);
        $this->tempDir = storage_path('app/temp');

        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Generate PDF report from data
     */
    public function generate(string $reportType, array $data, array $metadata, callable $progressCallback = null): string
    {
        // For per-entity reports, generate with TOC
        $includeToc = $reportType === 'per_student' && count($data) > 10;

        $html = $this->buildHtml($reportType, $data, $metadata, $includeToc);

        $filename = 'report_' . time() . '_' . uniqid() . '.pdf';
        $outputPath = storage_path('app/exports/' . $filename);

        // Ensure exports directory exists
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Generate PDF using Browsershot with enhanced options
        Browsershot::html($html)
            ->setNodeBinary(config('app.browsershot_node_binary', '/usr/bin/node'))
            ->setChromePath(config('app.puppeteer_executable_path', '/usr/bin/chromium'))
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->pages('1-') // Generate all pages
            ->noSandbox() // Required for Docker
            ->setOption('args', ['--disable-dev-shm-usage', '--disable-gpu'])
            ->save($outputPath);

        if ($progressCallback) {
            $progressCallback(count($data), count($data), 'PDF generated');
        }

        return $outputPath;
    }

    /**
     * Build HTML for PDF
     */
    private function buildHtml(string $reportType, array $data, array $metadata, bool $includeToc = false): string
    {
        $title = $metadata['title'] ?? 'LMS Activity Report';
        $generatedAt = now()->format('Y-m-d H:i:s');

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10pt;
                    margin: 0;
                    padding: 20px;
                }
                h1 {
                    color: #1e40af;
                    font-size: 18pt;
                    margin-bottom: 10px;
                }
                h2 {
                    color: #3b82f6;
                    font-size: 14pt;
                    margin-top: 20px;
                    margin-bottom: 10px;
                    page-break-after: avoid;
                }
                .toc {
                    page-break-after: always;
                    margin-bottom: 30px;
                }
                .toc h2 {
                    border-bottom: 2px solid #3b82f6;
                    padding-bottom: 10px;
                }
                .toc-item {
                    padding: 8px 0;
                    border-bottom: 1px dotted #ccc;
                }
                .toc-item a {
                    color: #1e40af;
                    text-decoration: none;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                thead {
                    display: table-header-group; /* Repeat headers on each page */
                }
                th {
                    background-color: #3b82f6;
                    color: white;
                    padding: 8px;
                    text-align: left;
                    font-weight: bold;
                }
                td {
                    padding: 6px 8px;
                    border-bottom: 1px solid #e5e7eb;
                }
                tr:nth-child(even) {
                    background-color: #f9fafb;
                }
                .header {
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #1e40af;
                }
                .metadata {
                    font-size: 9pt;
                    color: #6b7280;
                    margin-bottom: 15px;
                }
                .page-break {
                    page-break-before: always;
                }
                @page {
                    margin: 1cm;
                    @bottom-right {
                        content: 'Page ' counter(page) ' of ' counter(pages);
                    }
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$title}</h1>
                <div class='metadata'>
                    Generated: {$generatedAt} | 
                    Report Type: " . ucfirst($reportType) . " | 
                    Total Records: " . number_format(count($data)) . "
                </div>
            </div>
        ";

        // Add Table of Contents for per-entity reports
        if ($includeToc && $reportType === 'per_student') {
            $html .= $this->buildTableOfContents($data);
        }

        // Render based on report type
        if ($reportType === 'per_student') {
            $html .= $this->buildPerStudentHtml($data);
        } else {
            $html .= $this->buildTableHtml($data);
        }

        $html .= "</body></html>";

        return $html;
    }

    /**
     * Build table HTML for standard reports
     */
    private function buildTableHtml(array $data): string
    {
        if (empty($data)) {
            return "<p>No data available.</p>";
        }

        $html = "<table><thead><tr>";

        // Table headers
        $firstRow = $data[0];
        foreach (array_keys($firstRow) as $column) {
            $html .= "<th>" . ucwords(str_replace('_', ' ', $column)) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        // Table rows
        foreach ($data as $row) {
            $html .= "<tr>";
            foreach ($row as $value) {
                $html .= "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";

        return $html;
    }

    /**
     * Build HTML for per-student report (with sections)
     */
    private function buildPerStudentHtml(array $students): string
    {
        $html = '';

        foreach ($students as $index => $student) {
            if ($index > 0) {
                $html .= "<div class='page-break'></div>";
            }

            $studentId = $student['student_number'] ?? $index;
            $html .= "
                <div id='student-{$studentId}'>
                    <h2>Student: {$student['first_name']} {$student['last_name']} ({$student['student_number']})</h2>
                    <p><strong>Program:</strong> {$student['program']} | <strong>Year:</strong> {$student['year_level']}</p>
                </div>
            ";

            // Add student activity summary (would be fetched from related data)
            $html .= "<p><em>Activity data would be included here in full implementation.</em></p>";
        }

        return $html;
    }

    /**
     * Build Table of Contents for per-entity reports
     */
    private function buildTableOfContents(array $students): string
    {
        $html = "<div class='toc'>";
        $html .= "<h2>Table of Contents</h2>";

        foreach ($students as $index => $student) {
            $studentId = $student['student_number'] ?? $index;
            $studentName = "{$student['first_name']} {$student['last_name']}";
            $program = $student['program'] ?? 'N/A';

            $html .= "
                <div class='toc-item'>
                    <a href='#student-{$studentId}'>{$studentName} ({$studentId}) - {$program}</a>
                </div>
            ";
        }

        $html .= "</div>";

        return $html;
    }
}
