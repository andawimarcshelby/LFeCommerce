<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Table of Contents</title>
    <style>
        @page {
            margin: 40px 60px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
        }

        h1 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }

        .toc-section {
            margin-bottom: 30px;
        }

        .toc-section-title {
            font-size: 20px;
            font-weight: 700;
            color: #764ba2;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-left: 4px solid #764ba2;
        }

        .toc-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .toc-item {
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px dashed #e0e0e0;
        }

        .toc-item:hover {
            background: #f8f9fa;
        }

        .toc-item-name {
            font-size: 14px;
            color: #555;
            flex: 1;
        }

        .toc-item-dots {
            flex: 1;
            border-bottom: 2px dotted #ccc;
            margin: 0 15px;
            height: 1px;
        }

        .toc-item-page {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            min-width: 40px;
            text-align: right;
        }

        .toc-summary {
            font-size: 14px;
            color: #777;
            font-style: italic;
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <h1>Table of Contents</h1>

    @foreach($sections as $section)
        <div class="toc-section">
            <div class="toc-section-title">{{ $section['title'] }}</div>

            <ul class="toc-items">
                @foreach($section['items'] as $item)
                    <li class="toc-item">
                        <span class="toc-item-name">{{ $item['name'] }}</span>
                        <span class="toc-item-dots"></span>
                        <span class="toc-item-page">{{ $item['page'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    @if(isset($totalPages))
        <div class="toc-summary">
            <strong>Report Summary:</strong> This report contains {{ $totalPages }} pages
            @if(isset($totalStudents))
                covering {{ number_format($totalStudents) }} student(s)
            @endif
            @if(isset($dateRange))
                for the period {{ $dateRange }}
            @endif
        </div>
    @endif
</body>

</html>