<?php

namespace App\Services;

class TocService
{
    private array $sections = [];
    private int $currentPage = 1;

    /**
     * Add a section to the table of contents
     */
    public function addSection(string $title, int $level = 1, ?int $pageNumber = null): void
    {
        $this->sections[] = [
            'title' => $title,
            'level' => $level,
            'page' => $pageNumber ?? $this->currentPage,
        ];
    }

    /**
     * Set the current page number
     */
    public function setCurrentPage(int $page): void
    {
        $this->currentPage = $page;
    }

    /**
     * Increment the current page number
     */
    public function incrementPage(int $increment = 1): void
    {
        $this->currentPage += $increment;
    }

    /**
     * Get all sections
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Generate HTML for table of contents
     */
    public function generateHtml(): string
    {
        if (empty($this->sections)) {
            return '';
        }

        $html = '<div class="table-of-contents">';
        $html .= '<h1>Table of Contents</h1>';
        $html .= '<div class="toc-entries">';

        foreach ($this->sections as $section) {
            $indent = ($section['level'] - 1) * 20;
            $html .= sprintf(
                '<div class="toc-entry level-%d" style="margin-left: %dpx;">',
                $section['level'],
                $indent
            );
            $html .= '<span class="toc-title">' . htmlspecialchars($section['title']) . '</span>';
            $html .= '<span class="toc-dots"></span>';
            $html .= '<span class="toc-page">' . $section['page'] . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Reset the TOC
     */
    public function reset(): void
    {
        $this->sections = [];
        $this->currentPage = 1;
    }

    /**
     * Get total number of TOC pages (estimate)
     */
    public function estimateTocPages(): int
    {
        $entriesPerPage = 40; // Approximate
        return max(1, (int) ceil(count($this->sections) / $entriesPerPage));
    }

    /**
     * Adjust page numbers by offset (for when TOC is inserted at beginning)
     */
    public function adjustPageNumbers(int $offset): void
    {
        foreach ($this->sections as &$section) {
            $section['page'] += $offset;
        }
    }
}
