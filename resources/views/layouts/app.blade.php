<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', '在庫管理システム') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    
    <style>
        body {
            background-color: #f4f7f6;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.08);
            border-radius: 0.75rem;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .kpi-card .card-body {
            display: flex;
            align-items: center;
        }
        .kpi-card .kpi-icon {
            font-size: 2.5rem;
            padding: 1rem;
            border-radius: 50%;
            margin-right: 1.5rem;
            color: #fff;
        }
    </style>
</head>
<body>
    @include('layouts.navigation')

    <main class="container-fluid my-4 px-4">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ja.js"></script>
    
    @stack('scripts')
</body>
</html>