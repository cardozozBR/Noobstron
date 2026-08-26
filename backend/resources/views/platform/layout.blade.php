<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', __('platform.brand'))
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #111827;
        }

        .platform-header {
            background: #111827;
            color: white;
            padding: 18px 32px;
        }

        .platform-header__inner,
        .platform-main {
            width: min(1180px, 92%);
            margin: 0 auto;
        }

        .platform-header__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .platform-brand {
            font-size: 18px;
            font-weight: 700;
            flex: 0 0 auto;
        }

        .platform-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            flex: 1 1 auto;
            flex-wrap: wrap;
        }

        .platform-navigation__link {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 8px 10px;
            border-radius: 8px;
            color: #d1d5db;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .platform-navigation__link:hover,
        .platform-navigation__link:focus-visible {
            background: rgba(255, 255, 255, .1);
            color: white;
        }

        .platform-navigation__link.is-active {
            background: white;
            color: #111827;
        }

        .platform-navigation__logout {
            margin: 0;
            flex: 0 0 auto;
        }

        .platform-breadcrumbs {
            margin-bottom: 18px;
        }

        .platform-breadcrumbs__list {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
            color: #6b7280;
            font-size: 13px;
        }

        .platform-breadcrumbs__item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .platform-breadcrumbs__item:not(:last-child)::after {
            content: '/';
            color: #9ca3af;
        }

        .platform-breadcrumbs__link {
            color: #4b5563;
            text-decoration: none;
        }

        .platform-breadcrumbs__link:hover,
        .platform-breadcrumbs__link:focus-visible {
            color: #111827;
            text-decoration: underline;
        }

        .platform-breadcrumbs__current {
            color: #111827;
            font-weight: 600;
        }

        .platform-main {
            margin-top: 36px;
            margin-bottom: 48px;
        }

        .platform-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .04);
        }

        .metric-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .metric-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
        }

        .metric-label {
            color: #6b7280;
            font-size: 14px;
        }

        .metric-value {
            margin-top: 8px;
            font-size: 32px;
            font-weight: 700;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .button {
            border: 0;
            border-radius: 8px;
            padding: 11px 18px;
            background: #111827;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .logout-button {
            border: 0;
            background: transparent;
            color: white;
            cursor: pointer;
        }

        .error {
            color: #b91c1c;
            margin-top: 6px;
        }

        .platform-brand {
            color: inherit;
            text-decoration: none;
        }

        .platform-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 22px;
        }

        .platform-muted {
            color: #6b7280;
        }

        .platform-card--table {
            margin-top: 20px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .platform-table {
            width: 100%;
            border-collapse: collapse;
        }

        .platform-table th,
        .platform-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .platform-table th {
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
        }

        .status-badge--neutral {
            background: #f3f4f6;
            color: #374151;
        }

        .status-badge--success {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge--warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge--danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge--info {
            background: #eef2ff;
            color: #3730a3;
        }

        .button-secondary {
            background: white;
            color: #111827;
            border: 1px solid #d1d5db;
            text-decoration: none;
        }

        .filter-grid {
            display: grid;
            grid-template-columns:
                minmax(220px, 2fr)
                minmax(180px, 1fr)
                auto;
            gap: 18px;
            align-items: end;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            padding-bottom: 18px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .detail-list {
            display: grid;
            gap: 16px;
            margin: 0;
        }

        .detail-list div {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 16px;
        }

        .detail-list dt {
            color: #6b7280;
            font-weight: 600;
        }

        .detail-list dd {
            margin: 0;
        }

        .pagination-wrap {
            margin-top: 20px;
        }
        @media (max-width: 640px) {
            .platform-header {
                padding: 16px 20px;
            }

            .platform-header__inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .platform-navigation {
                width: 100%;
                justify-content: flex-start;
            }

            .platform-navigation__link {
                min-height: 36px;
            }

            .platform-main {
                width: min(94%, 1180px);
                margin-top: 24px;
            }

            .platform-toolbar {
                flex-direction: column;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                padding-bottom: 0;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

.platform-error-state {
    margin-bottom: 20px;
    padding: 16px 18px;
    border: 1px solid #fecaca;
    border-radius: 12px;
    background: #fef2f2;
    color: #991b1b;
}

.platform-error-state strong {
    display: block;
}

.platform-error-state ul {
    margin: 8px 0 0;
    padding-left: 20px;
}

.platform-error-state li + li {
    margin-top: 4px;
}

.platform-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 96px;
    padding: 24px;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    color: #64748b;
    text-align: center;
}

.platform-empty-state strong {
    color: #334155;
}

.platform-empty-state p {
    margin: 0;
}

.platform-empty-cell {
    padding: 28px !important;
    color: #64748b;
    text-align: center !important;
}
</style>
</head>
<body>
    @yield('body')
</body>
</html>
