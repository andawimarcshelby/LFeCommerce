<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table of Contents</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 40px;
            color: #191919;
        }

        .table-of-contents {
            page-break-after: always;
        }

        .table-of-contents h1 {
            font-size: 24pt;
            color: #750E21;
            margin-bottom: 30px;
            border-bottom: 3px solid #750E21;
            padding-bottom: 15px;
        }

        .toc-entries {
            margin-top: 20px;
        }

        .toc-entry {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ccc;
            align-items: baseline;
        }

        .toc-entry.level-1 {
            font-weight: 600;
            font-size: 11pt;
            margin-top: 10px;
        }

        .toc-entry.level-2 {
            font-size: 10pt;
            margin-left: 20px;
        }

        .toc-entry.level-3 {
            font-size: 9pt;
            margin-left: 40px;
            color: #666;
        }

        .toc-title {
            flex: 1;
            padding-right: 10px;
        }

        .toc-dots {
            flex: 1;
            border-bottom: 1px dotted #999;
            margin: 0 10px;
            height: 1px;
            align-self: flex-end;
            margin-bottom: 5px;
        }

        .toc-page {
            font-weight: 600;
            color: #750E21;
            min-width: 40px;
            text-align: right;
        }
    </style>
</head>

<body>
    {!! $tocHtml !!}
</body>

</html>