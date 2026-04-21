<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Process Path Print - Q{{ $quarter }} {{ $year }}</title>
    <style>
        :root {
            --ink: #0e1b3f;
            --muted: #6f7a99;
            --line: #d8deef;
            --accent: #1f54ff;
            --soft: #edf2ff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 28px;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: #f8faff;
        }

        .sheet {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 24px;
        }

        h1 {
            margin: 0;
            font-size: 28px;
        }

        p {
            margin: 6px 0 18px;
            color: var(--muted);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            padding: 11px 12px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: var(--soft);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .badge {
            display: inline-block;
            border-radius: 99px;
            padding: 4px 10px;
            background: rgba(31, 84, 255, 0.12);
            color: var(--accent);
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.04em;
        }

        .print-toolbar {
            margin: 0 auto 14px;
            max-width: 960px;
            display: flex;
            justify-content: flex-end;
        }

        button {
            border: 0;
            border-radius: 10px;
            background: var(--accent);
            color: white;
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .print-toolbar {
                display: none;
            }

            .sheet {
                border: 0;
                border-radius: 0;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <section class="sheet">
        <h1>ICQA Process Path Record</h1>
        <p>Quarter <strong>Q{{ $quarter }}</strong> - {{ $year }}</p>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Associate Name</th>
                    <th>P1</th>
                    <th>P2</th>
                    <th>P3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($associates as $index => $associate)
                    @php $row = $assignments->get($associate->id); @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $associate->name }}</td>
                        <td><span class="badge">{{ $row?->path_1 ?? $row?->start_path ?? '-' }}</span></td>
                        <td><span class="badge">{{ $row?->path_2 ?? $row?->end_path ?? '-' }}</span></td>
                        <td><span class="badge">{{ $row?->path_3 ?? '-' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</body>
</html>
