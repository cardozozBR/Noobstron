<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', __('ui.app_name'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --bg: #f5f7fb;
            --surface: #ffffff;
            --surface-muted: #f9fafb;
            --border: #e5e7eb;
            --text: #111827;
            --text-muted: #6b7280;
            --primary: var(--tenant-primary-color);
            --primary-hover: #1f2937;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
            --radius: 12px;
            --radius-lg: 16px;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }

        a {
            color: var(--primary);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        header {
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .brand {
            font-size: 20px;
            font-weight: 700;
            white-space: nowrap;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
            white-space: nowrap;
        }

        .user-area form {
            margin: 0;
        }

        .logout {
            border: 0;
            background: transparent;
            color: white;
            cursor: pointer;
            font-size: 14px;
            padding: 0;
        }

        .logout:hover {
            text-decoration: underline;
        }

        main {
            width: min(1100px, 92%);
            margin: 32px auto 48px;
        }

        .card {
            background: var(--surface);
            padding: 28px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }

        h1,
        h2,
        h3 {
            color: var(--text);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        h2 {
            margin: 0 0 16px;
            font-size: 20px;
        }

        p {
            color: var(--text-muted);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin-bottom: 4px;
        }

        .page-header p {
            margin: 0;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: var(--surface-muted);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-hover);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            color: var(--text);
            font-size: 15px;
        }

        .form-control:focus {
            outline: none;
            border-color: #6b7280;
            box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.08);
        }

        .form-help {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 13px;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .alert {
            margin-bottom: 20px;
            padding: 12px 16px;
            border-radius: 8px;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid var(--border);
            color: var(--text);
            font-size: 14px;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--text);
        }

        .table tbody tr:hover {
            background: var(--surface-muted);
        }

        .empty-state {
            padding: 32px;
            text-align: center;
            color: var(--text-muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-card {
            background: var(--surface-muted);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 30px;
            font-weight: 700;
            color: var(--text);
        }

        .stat-description {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 13px;
        }

        .eyebrow {
            display: block;
            margin-bottom: 6px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .section-header p {
            margin: 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid var(--border);
            color: var(--text);
            font-size: 14px;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--text);
            vertical-align: top;
        }

        .data-table tbody tr:hover {
            background: var(--surface-muted);
        }

        .nowrap {
            white-space: nowrap;
        }

        .action-code {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 6px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            font-size: 12px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .quick-action {
            display: block;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface-muted);
            color: var(--text);
        }

        .quick-action:hover {
            text-decoration: none;
            background: var(--surface);
        }

        .quick-action strong,
        .quick-action span {
            display: block;
        }

        .quick-action span {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 14px;
        }

        @media (max-width: 800px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
            }
        }

        @media (max-width: 640px) {
            header {
                flex-wrap: wrap;
                padding: 16px 20px;
            }

            nav {
                order: 3;
                width: 100%;
                overflow-x: auto;
                padding-top: 4px;
            }

            .user-area {
                margin-left: auto;
            }

            main {
                width: min(100% - 24px, 1100px);
                margin-top: 20px;
            }

            .card {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .actions {
                width: 100%;
            }

            .actions .btn {
                flex: 1;
                text-align: center;
            }

            .stat-value {
                font-size: 26px;
            }
        }

        .checkbox-list {
            margin: 24px 0;
        }

        .checkbox-item {
            display: block;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .checkbox-item:last-child {
            border-bottom: 0;
        }

        .checkbox-item strong {
            margin-left: 6px;
        }

        hr {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 28px 0;
        }

        @media (max-width: 800px) {
            header {
                flex-wrap: wrap;
                padding: 16px 20px;
                gap: 16px;
            }

            nav {
                order: 3;
                width: 100%;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .user-area {
                margin-left: auto;
            }

            main {
                width: min(94%, 1100px);
                margin-top: 24px;
            }

            .card {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 520px) {
            .brand {
                font-size: 18px;
            }

            .user-area span {
                display: none;
            }

            h1 {
                font-size: 24px;
            }

            .actions,
            .form-actions {
                width: 100%;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 640px) {
            header {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 12px;
                padding: 14px 16px;
            }

            .brand {
                align-self: center;
            }

            .user-area {
                margin-left: 0;
                justify-self: end;
            }

            nav {
                grid-column: 1 / -1;
                order: initial;
                width: 100%;
                display: flex;
                gap: 8px;
                overflow-x: auto;
                padding: 8px 0 2px;
                scrollbar-width: thin;
            }

            nav a {
                flex: 0 0 auto;
                padding: 6px 8px;
                white-space: nowrap;
            }
        }
        /* NAV-REDESIGN-V1:START */
        header {
            position: sticky;
            top: 0;
            z-index: 50;
            gap: 22px;
            padding: 14px 26px;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .brand {
            flex: 0 0 auto;
        }

        nav {
            min-width: 0;
            gap: 4px;
            overflow-x: auto;
            padding: 0 2px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.35) transparent;
        }

        nav::-webkit-scrollbar {
            height: 4px;
        }

        nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.35);
            border-radius: 999px;
        }

        nav a {
            position: relative;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 7px 9px;
            border-radius: 8px;
            color: #ffffff !important;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.15;
            white-space: nowrap;
            text-decoration: none !important;
            opacity: 0.94;
            transition:
                background-color 160ms ease,
                opacity 160ms ease,
                transform 160ms ease;
        }

        nav a:not(.nav-pill)::after {
            content: "—";
            position: absolute;
            left: 9px;
            right: 9px;
            bottom: 2px;
            height: 2px;
            border-radius: 999px;
            background: #ffffff;
            transform: scaleX(0);
            transform-origin: center;
            transition: transform 180ms ease;
        }

        nav a:not(.nav-pill):hover,
        nav a:not(.nav-pill).is-active {
            background: rgba(255, 255, 255, 0.08);
            opacity: 1;
        }

        nav a:not(.nav-pill):hover::after,
        nav a:not(.nav-pill).is-active::after {
            transform: scaleX(1);
        }

        nav a.nav-pill {
            margin-left: 3px;
            padding: 7px 11px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.13);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            font-weight: 600;
            opacity: 1;
        }

        nav a.nav-pill:hover,
        nav a.nav-pill.is-active {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.28);
            transform: translateY(-1px);
        }

        nav a.nav-pill::before {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            margin-right: 6px;
            border: 1px solid rgba(255, 255, 255, 0.34);
            border-radius: 999px;
            font-size: 11px;
            line-height: 1;
        }

        nav a.nav-financial::before {
            content: "\25A3";
        }

        nav a.nav-inbox::before {
            content: "\2709";
        }

        nav a.nav-email::before {
            content: "\2709";
        }

        nav a.nav-whatsapp::before {
            content: "\25CC";
        }

        .user-area {
            flex: 0 0 auto;
            gap: 10px;
            padding-left: 14px;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-area > span {
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
        }

        .user-area > a {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 6px 9px;
            border-radius: 8px;
            color: #ffffff !important;
            text-decoration: none !important;
            transition: background-color 160ms ease;
        }

        .user-area > a:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .notification-link {
            white-space: nowrap;
        }

        .notification-badge {
            display: inline-block;
            min-width: 20px;
            padding: 1px 6px;
            margin-left: 3px;
            border-radius: 999px;
            background: #ffffff;
            color: var(--primary);
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }

        .logout {
            min-height: 34px;
            padding: 6px 8px;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 160ms ease;
        }

        .logout:hover {
            background: rgba(255, 255, 255, 0.12);
            text-decoration: none;
        }

        @media (max-width: 1100px) {
            header {
                gap: 14px;
                padding-inline: 18px;
            }

            nav a {
                padding-inline: 7px;
                font-size: 12px;
            }

            nav a.nav-pill {
                padding-inline: 9px;
            }

            .user-area > span {
                display: none;
            }
        }

        @media (max-width: 800px) {
            header {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 10px 14px;
            }

            nav {
                grid-column: 1 / -1;
                order: initial;
                width: 100%;
                padding: 6px 0 2px;
            }

            .user-area {
                justify-self: end;
                padding-left: 0;
                border-left: 0;
            }
        }

        @media (max-width: 520px) {
            nav a {
                min-height: 36px;
                font-size: 12px;
            }

            nav a.nav-pill::before {
                display: none;
            }
        }
        /* NAV-REDESIGN-V1:END */

        /* NAV-MORE-V1:START */
        nav {
            overflow: visible !important;
            flex-wrap: nowrap;
        }

        nav > a {
            flex: 0 0 auto;
        }

        .nav-more {
            position: relative;
            flex: 0 0 auto;
            display: none;
            align-items: center;
        }

        .nav-more.is-ready {
            display: inline-flex;
        }

        .nav-more-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 38px;
            padding: 7px 11px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.10);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: background-color 160ms ease, border-color 160ms ease;
        }

        .nav-more-button:hover,
        .nav-more.has-active .nav-more-button,
        .nav-more.is-open .nav-more-button {
            background: rgba(255, 255, 255, 0.20);
            border-color: rgba(255, 255, 255, 0.30);
        }

        .nav-more-chevron {
            font-size: 10px;
            transition: transform 160ms ease;
        }

        .nav-more.is-open .nav-more-chevron {
            transform: rotate(180deg);
        }

        .nav-more-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 100;
            display: none;
            width: min(310px, calc(100vw - 32px));
            max-height: min(70vh, 520px);
            overflow-y: auto;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
        }

        .nav-more.is-open .nav-more-menu {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3px;
        }

        .nav-more-menu a,
        .nav-more-menu a.nav-pill {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 40px;
            margin: 0;
            padding: 9px 11px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            box-shadow: none;
            color: #374151 !important;
            font-size: 13px;
            font-weight: 600;
            opacity: 1;
            transform: none;
        }

        .nav-more-menu a:hover,
        .nav-more-menu a.is-active,
        .nav-more-menu a.nav-pill:hover,
        .nav-more-menu a.nav-pill.is-active {
            background: #f3f4f6;
            color: #111827 !important;
            transform: none;
        }

        .nav-more-menu a::after {
            display: none !important;
        }

        .nav-more-menu a.nav-pill::before {
            color: var(--primary);
            border-color: #dbeafe;
            background: #eff6ff;
        }

        @media (max-width: 1500px) and (min-width: 801px) {
            header {
                display: flex !important;
                flex-wrap: nowrap !important;
                gap: 14px;
                padding-inline: 20px;
            }

            .brand {
                flex: 0 0 auto;
            }

            nav {
                min-width: 0;
                flex: 1 1 auto;
                gap: 2px;
                padding: 0;
            }

            nav > a {
                padding-inline: 6px;
                font-size: 12px;
            }

            .user-area {
                flex: 0 0 auto;
                margin-left: auto;
            }
        }

        @media (max-width: 1100px) and (min-width: 801px) {
            nav > a {
                padding-inline: 5px;
                font-size: 11.5px;
            }

            .user-area > span {
                display: none;
            }
        }

        @media (max-width: 800px) {
            header {
                display: grid !important;
                grid-template-columns: 1fr auto;
                gap: 10px 12px;
            }

            nav {
                grid-column: 1 / -1;
                width: 100%;
                flex-wrap: wrap;
                overflow: visible !important;
                gap: 4px;
            }

            .nav-more {
                position: static;
            }

            .nav-more-menu {
                left: 0;
                right: auto;
                width: min(360px, calc(100vw - 32px));
            }
        }
        /* NAV-MORE-V1:END */

        /* NAV-ICON-FIX-V1:START */
        .nav-more-menu a.nav-pill::before {
            content: "" !important;
            display: inline-block !important;
            width: 18px !important;
            height: 18px !important;
            margin-right: 8px !important;
            border: 0 !important;
            border-radius: 0 !important;
            background-color: transparent !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: 18px 18px !important;
            flex: 0 0 18px;
        }

        .nav-more-menu a.nav-financial::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23111827' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='8.5'/%3E%3Cpath d='M14.5 8.7c-.6-.6-1.5-.9-2.5-.9-1.4 0-2.5.7-2.5 1.8 0 1.2 1 1.6 2.6 2 1.6.4 2.7.8 2.7 2.1 0 1.2-1.2 2-2.8 2-1.1 0-2.2-.4-2.9-1.1M12 6.5v11'/%3E%3C/svg%3E") !important;
        }

        .nav-more-menu a.nav-inbox::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23111827' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z'/%3E%3Cpath d='M4.5 13h4l1.5 2h4l1.5-2h4'/%3E%3C/svg%3E") !important;
        }

        .nav-more-menu a.nav-email::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23111827' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3.5' y='5.5' width='17' height='13' rx='2.2'/%3E%3Cpath d='m4.5 7 6.2 4.4a2.2 2.2 0 0 0 2.6 0L19.5 7'/%3E%3C/svg%3E") !important;
        }

        .nav-more-menu a.nav-whatsapp::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23111827' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19.5 11.6a7.5 7.5 0 0 1-11.1 6.6L4 19.5l1.3-4.1a7.5 7.5 0 1 1 14.2-3.8Z'/%3E%3Cpath d='M9 8.8c.3 2.4 2.2 4.4 4.7 5.2M14.1 13.9l1.2-1.1M9 8.8l1-1.1'/%3E%3C/svg%3E") !important;
        }

        .nav-more-menu a.is-active.nav-pill::before {
            filter: none !important;
        }

        /* active communication item: icon follows the same neutral visual language */
        .nav-more-menu a.is-active.nav-inbox::before,
        .nav-more-menu a.is-active.nav-email::before,
        .nav-more-menu a.is-active.nav-whatsapp::before,
        .nav-more-menu a.is-active.nav-financial::before {
            opacity: 1;
        }
        /* NAV-ICON-FIX-V1:END */
</style>
    @php
        $brandingTenant = request()->attributes->get('tenant');
        $tenantPrimaryColor = $brandingTenant
            ? $brandingTenant->effectiveBrandPrimaryColor()
            : '#2563EB';
    @endphp

    <style>
        :root {
            --tenant-primary-color: {{ $tenantPrimaryColor }};
        }
    </style>
</head>

<body>

<header>
    <div class="brand">
    @if ($brandingTenant?->hasLogo())
        <img
            src="{{ asset('storage/' . $brandingTenant->logo_path) }}"
            alt="{{ $brandingTenant->name }}"
            style="max-height: 40px; max-width: 180px;"
        >
    @else
        {{ $brandingTenant?->name ?? __('ui.app_name') }}
    @endif
</div>

    <nav>
    <a href="{{ route('dashboard') }}">
        {{ __('ui.navigation.dashboard') }}
    </a>

    <a href="{{ route('profile.edit') }}">
        {{ __('ui.navigation.profile') }}
    </a>

    @if (
        auth()->user()->hasPermission(
            \App\Enums\Permission::USERS_VIEW
        )
    )
        <a href="{{ route('users.index') }}">
            {{ __('ui.navigation.users') }}
        </a>
    @endif

    @if (
        auth()->user()->hasPermission(
            \App\Enums\Permission::LEADS_VIEW
        )
        && app(
            \App\Support\TenantCapabilities::class
        )->enabled(
            app(
                \App\Services\TenantContext::class
            )->get(),
            \App\Enums\Feature::LEADS
        )
    )
        <a href="{{ route('leads.index') }}">
            {{ __('ui.navigation.leads') }}
        </a>
    @endif

    @if (
        auth()->user()->hasPermission(
            \App\Enums\Permission::CUSTOMERS_VIEW
        )
        && app(
            \App\Support\TenantCapabilities::class
        )->enabled(
            app(
                \App\Services\TenantContext::class
            )->get(),
            \App\Enums\Feature::CUSTOMERS
        )
    )
        <a href="{{ route('customers.index') }}">
            {{ __('ui.navigation.customers') }}
        </a>
    @endif

    @if (
        auth()->user()->hasPermission(
            \App\Enums\Permission::AUDIT_VIEW
        )
    )
        <a href="{{ route('audit.index') }}" class="nav-overflow">
            {{ __('ui.navigation.audit') }}
        </a>
    @endif

    @if (
        auth()->user()->hasPermission(
            \App\Enums\Permission::SETTINGS_UPDATE
        )
    )
        <a href="{{ route('settings.edit') }}" class="nav-overflow">
            {{ __('ui.navigation.settings') }}
        </a>
    @endif

        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::IMPORTS_VIEW
            )
            && app(
                \App\Support\TenantCapabilities::class
            )->enabled(
                app(
                    \App\Services\TenantContext::class
                )->get(),
                \App\Enums\Feature::IMPORTS
            )
        )
            <a href="{{ route('imports.index') }}" class="nav-overflow">
                {{ __('imports.navigation') }}
            </a>
        @endif

        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::OPPORTUNITIES_VIEW
            )
            && app(
                \App\Support\TenantCapabilities::class
            )->enabled(
                app(
                    \App\Services\TenantContext::class
                )->get(),
                \App\Enums\Feature::OPPORTUNITIES
            )
        )
            <a href="{{ route('opportunities.index') }}">
                {{ __('opportunities.navigation') }}
            </a>
        @endif
        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::ACTIVITIES_VIEW
            )
            && app(
                \App\Support\TenantCapabilities::class
            )->enabled(
                app(
                    \App\Services\TenantContext::class
                )->get(),
                \App\Enums\Feature::ACTIVITIES
            )
        )
            <a href="{{ route('activities.index') }}">
                {{ __('activities.navigation') }}
            </a>
        @endif
        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::PIPELINES_VIEW
            )
            && app(
                \App\Support\TenantCapabilities::class
            )->enabled(
                app(
                    \App\Services\TenantContext::class
                )->get(),
                \App\Enums\Feature::PIPELINES
            )
        )
            <a href="{{ route('pipelines.index') }}">
                {{ __('pipelines.navigation') }}
            </a>
        @endif
        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::CATALOG_VIEW
            )
            && app(
                \App\Support\TenantCapabilities::class
            )->enabled(
                app(
                    \App\Services\TenantContext::class
                )->get(),
                \App\Enums\Feature::CATALOG
            )
        )
            <a href="{{ route('catalog.index') }}">
                {{ __('catalog.navigation') }}
            </a>
        @endif
        @if (
            auth()->user()?->hasPermission(
                \App\Enums\Permission::PROPOSALS_VIEW
            )
            && app(
                \App\Support\TenantCapabilities::class
            )->enabled(
                app(
                    \App\Services\TenantContext::class
                )->get(),
                \App\Enums\Feature::PROPOSALS
            )
        )
            <a href="{{ route('proposals.index') }}">
                {{ __('proposals.navigation') }}
            </a>
                @if (
                    app(\App\Support\TenantCapabilities::class)
                        ->enabled(
                            app(\App\Services\TenantContext::class)->get(),
                            \App\Enums\Feature::SALES
                        )
                    &&
                    auth()->user()->hasPermission(
                        \App\Enums\Permission::SALES_VIEW
                    )
                )
                    <a
                        href="{{ route('sales.index') }}" class="nav-overflow"
                    >
                        {{ __('sales.navigation') }}
                    </a>
                @endif
                @if (
                    app(\App\Support\TenantCapabilities::class)
                        ->enabled(
                            app(
                                \App\Services\TenantContext::class
                            )->get(),
                            \App\Enums\Feature::RECEIVABLES
                        )
                    &&
                    auth()->user()->hasPermission(
                        \App\Enums\Permission::RECEIVABLES_VIEW
                    )
                )
                    <a
                        href="{{ route('receivables.index') }}" class="nav-overflow"
                    >
                        {{ __('receivables.navigation') }}
                    </a>
                @endif
                @if (
                    app(\App\Support\TenantCapabilities::class)
                        ->enabled(
                            app(
                                \App\Services\TenantContext::class
                            )->get(),
                            \App\Enums\Feature::CHARGES
                        )
                    &&
                    auth()->user()->hasPermission(
                        \App\Enums\Permission::CHARGES_VIEW
                    )
                )
                    <a
                        href="{{ route('charges.index') }}" class="nav-overflow"
                    >
                        {{ __('charges.navigation') }}
                    </a>
                @endif
                @if (
                    app(\App\Support\TenantCapabilities::class)
                        ->enabled(
                            app(
                                \App\Services\TenantContext::class
                            )->get(),
                            \App\Enums\Feature::FINANCIAL_INDICATORS
                        )
                    &&
                    auth()->user()->hasPermission(
                        \App\Enums\Permission::FINANCIAL_INDICATORS_VIEW
                    )
                )
                    <a
                        href="{{ route('financial-indicators.index') }}" class="nav-pill nav-financial nav-overflow"
                    >
                        {{ __('financial_indicators.navigation') }}
                    </a>
                @endif
        @endif

                @if (
                    auth()->check()
                    && auth()->user()?->hasPermission(
                        \App\Enums\Permission::INBOX_VIEW
                    )
                    && app(
                        \App\Support\TenantCapabilities::class
                    )->enabled(
                        app(
                            \App\Services\TenantContext::class
                        )->get(),
                        \App\Enums\Feature::INBOX
                    )
                )
                    <a
                        href="{{ route('inbox.index') }}" class="nav-pill nav-inbox nav-overflow"
                    >
                        {{ __('inbox.nav') }}
                    </a>
                @endif
                @if (
                    auth()->check()
                    && auth()->user()?->hasPermission(
                        \App\Enums\Permission::EMAIL_VIEW
                    )
                    && app(
                        \App\Support\TenantCapabilities::class
                    )->enabled(
                        app(
                            \App\Services\TenantContext::class
                        )->get(),
                        \App\Enums\Feature::EMAIL
                    )
                )
                    <a
                        href="{{ route('email.index') }}" class="nav-pill nav-email nav-overflow"
                    >
                        {{ __('email.nav') }}
                    </a>
                @endif
                @if (
                    auth()->check()
                    && auth()->user()?->hasPermission(
                        \App\Enums\Permission::WHATSAPP_VIEW
                    )
                    && app(
                        \App\Support\TenantCapabilities::class
                    )->enabled(
                        app(
                            \App\Services\TenantContext::class
                        )->get(),
                        \App\Enums\Feature::WHATSAPP
                    )
                )
                    <a
                        href="{{ route('whatsapp.index') }}" class="nav-pill nav-whatsapp nav-overflow"
                    >
                        {{ __('whatsapp.title') }}
                    </a>
                @endif
    <!-- NAV-MORE-MENU-V1 -->
    <div class="nav-more" id="nav-more">
        <button
            type="button"
            class="nav-more-button"
            id="nav-more-button"
            aria-haspopup="true"
            aria-expanded="false"
        >
            Mais
            <span class="nav-more-chevron" aria-hidden="true">&#9660;</span>
        </button>

        <div
            class="nav-more-menu"
            id="nav-more-menu"
            role="menu"
        ></div>
    </div>
</nav>

    <div class="user-area">
        <span>{{ auth()->user()->name }}</span>

        @php
            $unreadNotificationCount = auth()
                ->user()
                ->unreadNotifications()
                ->count();
        @endphp

        <a
            href="{{ route('notifications.index') }}"
            class="notification-link"
        >
            {{ __('notifications.navigation') }}

            @if ($unreadNotificationCount > 0)
                <strong class="notification-badge">
                    {{ $unreadNotificationCount }}
                </strong>
            @endif
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button class="logout" type="submit">
                {{ __('ui.navigation.logout') }}
            </button>
        </form>
    </div>
</header>

<main>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')

</main>

<script>
/* NAV-ACTIVE-V1:START */
document.addEventListener('DOMContentLoaded', function () {
    const links = Array.from(
        document.querySelectorAll('header nav a[href]')
    );

    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';

    let bestMatch = null;
    let bestLength = -1;

    links.forEach(function (link) {
        let path;

        try {
            path = new URL(link.href, window.location.origin)
                .pathname
                .replace(/\/+$/, '') || '/';
        } catch (error) {
            return;
        }

        const exact = currentPath === path;
        const nested = path !== '/' && currentPath.startsWith(path + '/');

        if ((exact || nested) && path.length > bestLength) {
            bestMatch = link;
            bestLength = path.length;
        }
    });

    if (bestMatch) {
        bestMatch.classList.add('is-active');
        bestMatch.setAttribute('aria-current', 'page');
    }
});
/* NAV-ACTIVE-V1:END */
</script>
<script>
/* NAV-MORE-JS-V1:START */
document.addEventListener('DOMContentLoaded', function () {
    const nav = document.querySelector('header nav');
    const more = document.getElementById('nav-more');
    const button = document.getElementById('nav-more-button');
    const menu = document.getElementById('nav-more-menu');

    if (!nav || !more || !button || !menu) {
        return;
    }

    const links = Array.from(
        nav.querySelectorAll(':scope > a.nav-overflow')
    );

    links.forEach(function (link) {
        menu.appendChild(link);
    });

    if (links.length > 0) {
        more.classList.add('is-ready');
    }

    if (menu.querySelector('a.is-active')) {
        more.classList.add('has-active');
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        const open = !more.classList.contains('is-open');
        more.classList.toggle('is-open', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    menu.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        more.classList.remove('is-open');
        button.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            more.classList.remove('is-open');
            button.setAttribute('aria-expanded', 'false');
            button.focus();
        }
    });
});
/* NAV-MORE-JS-V1:END */
</script>
</body>
</html>

