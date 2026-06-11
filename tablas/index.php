<?php
declare(strict_types=1);
ob_start();

require_once __DIR__ . "/../rene/conexion3.php";
require_once __DIR__ . "/../middlewares/AuthMiddleware.php";
require_once __DIR__ . "/../services/UserService.php";
require_once __DIR__ . "/../services/TablaService.php";

AuthMiddleware::initSession();
AuthMiddleware::checkAuth('../login/login.php');
AuthMiddleware::handleLogout('../login/login.php');
AuthMiddleware::generateCsrf();

$user_id    = AuthMiddleware::validateUser('../login/login.php');
$userService  = new UserService($conec);
$tablaService = new TablaService($conec);

$userInfo  = $userService->getUserInfo($user_id);
$usuario   = $userInfo['username'];
$oficina   = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);

// ── Datos ─────────────────────────────────────────────────────────────────
$globalTotals  = $tablaService->getGlobalTotals();
$serieData     = $tablaService->getSerieCount();
$serieRows     = $serieData['rows']     ?? [];
$grandTotal    = $serieData['grand_total'] ?? 0;
$seriesUnicas  = $tablaService->countSeriesUnicas();
$byDependencia = $tablaService->getSerieByDependencia();
$byUsuario     = $tablaService->getSerieByUsuario();

// Vista activa: 'global' | 'dependencia' | 'usuario'
$vista = $_GET['vista'] ?? 'global';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Módulo Tablas – Estadísticas y conteo de tipos documentales registrados en el índice documental.">
    <title>Tablas – Doroti</title>
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
    <style>
        /* ── Design Tokens ─────────────────────────────────────────── */
        :root {
            --clr-bg: #f0f4f8;
            --clr-surface: #ffffff;
            --clr-primary: #1a56db;
            --clr-primary2: #1e40af;
            --clr-accent: #06b6d4;
            --clr-success: #10b981;
            --clr-warning: #f59e0b;
            --clr-danger: #ef4444;
            --clr-muted: #64748b;
            --clr-border: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 10px;
            --shadow-card: 0 4px 24px rgba(26, 86, 219, .08);
            --shadow-hover: 0 12px 40px rgba(26, 86, 219, .15);
            --transition: all .28s cubic-bezier(.4, 0, .2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--clr-bg);
            color: #1e293b;
            padding-top: 72px;
        }

        /* ── Page header ───────────────────────────────────────────── */
        .page-hero {
            background: linear-gradient(135deg, var(--clr-primary) 0%, var(--clr-accent) 100%);
            color: #fff;
            padding: 2.5rem 2rem 3.5rem;
            margin-bottom: -2rem;
        }

        .page-hero h1 {
            font-weight: 700;
            letter-spacing: -.5px;
        }

        .page-hero p {
            opacity: .85;
            font-size: .95rem;
        }

        /* ── KPI Cards ─────────────────────────────────────────────── */
        .kpi-row {
            position: relative;
            z-index: 2;
        }

        .kpi-card {
            background: var(--clr-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 1.5rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
            border: 1px solid var(--clr-border);
        }

        .kpi-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }

        .kpi-icon {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        .kpi-icon.blue {
            background: #eff6ff;
            color: var(--clr-primary);
        }

        .kpi-icon.cyan {
            background: #ecfeff;
            color: var(--clr-accent);
        }

        .kpi-icon.green {
            background: #ecfdf5;
            color: var(--clr-success);
        }

        .kpi-icon.amber {
            background: #fffbeb;
            color: var(--clr-warning);
        }

        .kpi-icon.purple {
            background: #f5f3ff;
            color: #8b5cf6;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .kpi-label {
            font-size: .8rem;
            color: var(--clr-muted);
            margin-top: .2rem;
        }

        /* ── Toggle vistas ─────────────────────────────────────────── */
        .vista-toggle {
            display: flex;
            gap: .5rem;
        }

        .vista-btn {
            padding: .45rem 1.1rem;
            border-radius: 50px;
            font-size: .85rem;
            font-weight: 500;
            border: 1.5px solid var(--clr-primary);
            color: var(--clr-primary);
            background: transparent;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }

        .vista-btn.active,
        .vista-btn:hover {
            background: var(--clr-primary);
            color: #fff;
        }

        /* ── Search box ────────────────────────────────────────────── */
        .search-wrap {
            position: relative;
            max-width: 300px;
        }

        .search-wrap i {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--clr-muted);
            pointer-events: none;
        }

        .search-wrap input {
            padding-left: 2.4rem;
            border-radius: 50px;
            border: 1.5px solid var(--clr-border);
            font-size: .88rem;
            transition: var(--transition);
        }

        .search-wrap input:focus {
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, .12);
        }

        /* ── Table card ────────────────────────────────────────────── */
        .table-card {
            background: var(--clr-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--clr-border);
            overflow: hidden;
        }

        .table-card .card-header-custom {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid var(--clr-border);
            background: #fafbfd;
        }

        .tbl {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .tbl thead th {
            background: #f8fafc;
            color: var(--clr-muted);
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            padding: .75rem 1.25rem;
            border-bottom: 1px solid var(--clr-border);
            white-space: nowrap;
        }

        .tbl tbody tr {
            transition: background .15s;
        }

        .tbl tbody tr:hover {
            background: #f0f6ff;
        }

        .tbl tbody td {
            padding: .8rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: .9rem;
            vertical-align: middle;
        }

        .tbl tbody tr:last-child td {
            border-bottom: none;
        }

        /* rank badge */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: .78rem;
            font-weight: 700;
        }

        .rank-1 {
            background: #fef9c3;
            color: #92400e;
        }

        .rank-2 {
            background: #f1f5f9;
            color: #475569;
        }

        .rank-3 {
            background: #fff0f0;
            color: #b91c1c;
        }

        .rank-n {
            background: #f8fafc;
            color: #94a3b8;
        }

        /* progress bar inline */
        .pct-bar {
            height: 6px;
            border-radius: 6px;
            background: #e2e8f0;
            overflow: hidden;
            min-width: 80px;
        }

        .pct-fill {
            height: 100%;
            border-radius: 6px;
            background: linear-gradient(90deg, var(--clr-primary), var(--clr-accent));
            transition: width .6s ease;
        }

        /* pill count */
        .count-pill {
            display: inline-block;
            background: #eff6ff;
            color: var(--clr-primary);
            font-weight: 600;
            font-size: .82rem;
            border-radius: 50px;
            padding: .2rem .75rem;
        }

        /* ── Dependencia accordion ─────────────────────────────────── */
        .dep-accordion .accordion-button {
            font-weight: 600;
            font-size: .93rem;
            background: #fafbfd;
            color: #1e293b;
        }

        .dep-accordion .accordion-button:not(.collapsed) {
            background: #eff6ff;
            color: var(--clr-primary);
            box-shadow: none;
        }

        .dep-badge {
            background: var(--clr-primary);
            color: #fff;
            border-radius: 50px;
            padding: .15rem .7rem;
            font-size: .78rem;
            font-weight: 600;
        }

        /* ── Responsive ────────────────────────────────────────────── */
        @media (max-width: 576px) {
            .page-hero {
                padding: 1.5rem 1rem 2.5rem;
            }

            .kpi-value {
                font-size: 1.5rem;
            }
        }

        /* ── Fade-in animation ─────────────────────────────────────── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp .45s ease both;
        }

        .fade-up:nth-child(2) {
            animation-delay: .08s;
        }

        .fade-up:nth-child(3) {
            animation-delay: .16s;
        }

        .fade-up:nth-child(4) {
            animation-delay: .24s;
        }

        .fade-up:nth-child(5) {
            animation-delay: .32s;
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════════════════════════════ NAVBAR -->
    <?php
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    $basePath = '../';
    $activePage = 'tablas';
    require_once "../components/navbar.php";
    ?>


    <!-- ══════════════════════════════════════════════════════ HERO -->
    <div class="page-hero">
        <div class="container-fluid" style="max-width:1280px;">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0" style="font-size:.83rem;opacity:.8;">
                    <li class="breadcrumb-item">
                        <a href="../index.php" class="text-white text-decoration-none">Inicio</a>
                    </li>
                    <li class="breadcrumb-item active text-white">Tablas</li>
                </ol>
            </nav>
            <h1 class="mb-1"><i class="bi bi-table me-2"></i>Tablas</h1>
            <p class="mb-0">Estadísticas y conteo de tipos documentales registrados en el Índice Documental</p>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ MAIN -->
    <main class="container-fluid py-4" style="max-width:1280px;">

        <!-- KPI row -->
        <div class="row g-3 mb-4 kpi-row">
            <div class="col-6 col-md-4 col-xl fade-up">
                <div class="kpi-card">
                    <div class="kpi-icon blue"><i class="bi bi-files"></i></div>
                    <div>
                        <div class="kpi-value"><?= number_format((int)$globalTotals['total_docs']) ?></div>
                        <div class="kpi-label">Documentos</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl fade-up">
                <div class="kpi-card">
                    <div class="kpi-icon cyan"><i class="bi bi-file-text"></i></div>
                    <div>
                        <div class="kpi-value"><?= number_format((int)$globalTotals['total_paginas']) ?></div>
                        <div class="kpi-label">Folios</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl fade-up">
                <div class="kpi-card">
                    <div class="kpi-icon green"><i class="bi bi-tags"></i></div>
                    <div>
                        <div class="kpi-value"><?= $seriesUnicas ?></div>
                        <div class="kpi-label">Series</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl fade-up">
                <div class="kpi-card">
                    <div class="kpi-icon amber"><i class="bi bi-building"></i></div>
                    <div>
                        <div class="kpi-value"><?= count($byDependencia) ?></div>
                        <div class="kpi-label">Dependencias</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl fade-up">
                <div class="kpi-card">
                    <div class="kpi-icon purple"><i class="bi bi-folder2-open"></i></div>
                    <div>
                        <div class="kpi-value"><?= number_format((int)$globalTotals['total_carpetas']) ?></div>
                        <div class="kpi-label">Carpetas</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controles: vista + búsqueda -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="vista-toggle">
                <a href="?vista=global"
                    class="vista-btn <?= $vista === 'global' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line me-1"></i>Global
                </a>
                <a href="?vista=dependencia"
                    class="vista-btn <?= $vista === 'dependencia' ? 'active' : '' ?>">
                    <i class="bi bi-diagram-3 me-1"></i>Por Dependencia
                </a>
                <a href="?vista=usuario"
                    class="vista-btn <?= $vista === 'usuario' ? 'active' : '' ?>">
                    <i class="bi bi-person me-1"></i>Por Usuario
                </a>
            </div>
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="inputBuscar" class="form-control form-control-sm"
                    placeholder="Buscar tipo documental…" autocomplete="off">
            </div>
        </div>

        <?php if ($vista === 'global'): ?>
            <!-- ══════════════ VISTA GLOBAL ══════════════ -->
            <div class="table-card fade-up">
                <div class="card-header-custom d-flex align-items-center justify-content-between">
                    <span class="fw-semibold fs-6">
                        <i class="bi bi-list-ul me-2 text-primary"></i>
                        Tipos Documentales – Vista Global
                    </span>
                    <span class="badge bg-primary rounded-pill"><?= count($serieRows) ?> Series</span>
                </div>
                <div class="table-responsive">
                    <table class="tbl" id="tablaGlobal">
                        <thead>
                            <tr>
                                <th style="width:48px;">#</th>
                                <th>Tipo Documental (Serie)</th>
                                <th class="text-end">Documentos</th>
                                <th class="text-end">Folios</th>
                                <th style="min-width:140px;">Participación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serieRows as $i => $row):
                                $rank  = $i + 1;
                                $pct   = (float)$row['pct'];
                                switch ($rank) {
                                    case 1:
                                        $badgeClass = 'rank-1';
                                        break;
                                    case 2:
                                        $badgeClass = 'rank-2';
                                        break;
                                    case 3:
                                        $badgeClass = 'rank-3';
                                        break;
                                    default:
                                        $badgeClass = 'rank-n';
                                        break;
                                }
                            ?>
                                <tr class="tbl-row">
                                    <td>
                                        <span class="rank-badge <?= $badgeClass ?>"><?= $rank ?></span>
                                    </td>
                                    <td class="fw-medium tbl-serie"><?= ResponseHelper::h($row['serie']) ?></td>
                                    <td class="text-end">
                                        <span class="count-pill"><?= number_format((int)$row['total']) ?></span>
                                    </td>
                                    <td class="text-end text-muted">
                                        <?= number_format((int)$row['total_paginas']) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="pct-bar flex-grow-1">
                                                <div class="pct-fill" style="width:<?= min($pct * 2, 100) ?>%"></div>
                                            </div>
                                            <span style="font-size:.78rem;color:var(--clr-muted);white-space:nowrap;">
                                                <?= $pct ?>%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0f6ff;">
                                <td colspan="2" class="fw-bold ps-4">TOTAL</td>
                                <td class="text-end fw-bold text-primary">
                                    <?= number_format((int)$globalTotals['total_docs']) ?>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    <?= number_format((int)$globalTotals['total_paginas']) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div id="sinResultados" class="text-center py-4 text-muted d-none">
                    <i class="bi bi-search fs-3 d-block mb-2"></i>
                    No se encontraron tipos documentales que coincidan.
                </div>
            </div>

        <?php elseif ($vista === 'dependencia'): ?>
            <!-- ══════════════ VISTA POR DEPENDENCIA ══════════════ -->
            <div class="accordion dep-accordion" id="accordionDep">
                <?php $depIdx = 0;
                foreach ($byDependencia as $depNombre => $depData):
                    $depId = 'dep' . $depIdx++;
                ?>
                    <div class="accordion-item border-0 mb-2 rounded overflow-hidden shadow-sm dep-item"
                        data-dep="<?= strtolower(ResponseHelper::h($depNombre)) ?>">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#<?= $depId ?>">
                                <i class="bi bi-building me-2 text-primary"></i>
                                <span style="flex-grow:1;"><?= ResponseHelper::h($depNombre) ?></span>
                                <span style="display:flex;gap:.4rem;margin-right:.5rem;">
                                    <span class="dep-badge"><?= number_format($depData['subtotal']) ?> docs</span>
                                    <span class="dep-badge" style="background:#8b5cf6;"><?= number_format($depData['carpetas']) ?> carpetas</span>
                                </span>
                            </button>
                        </h2>
                        <div id="<?= $depId ?>" class="accordion-collapse collapse"
                            data-bs-parent="#accordionDep">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="tbl dep-tbl">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tipo Documental</th>
                                                <th class="text-end">Documentos</th>
                                                <th class="text-end">Folios</th>
                                                <th style="min-width:130px;">% en oficina</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $subtotal = $depData['subtotal'];
                                            foreach ($depData['series'] as $j => $sr):
                                                $pctDep = $subtotal > 0
                                                    ? round(((int)$sr['total'] / $subtotal) * 100, 1)
                                                    : 0;
                                            ?>
                                                <tr class="dep-row">
                                                    <td><span class="rank-badge rank-n"><?= $j + 1 ?></span></td>
                                                    <td class="fw-medium dep-serie"><?= ResponseHelper::h($sr['serie']) ?></td>
                                                    <td class="text-end">
                                                        <span class="count-pill"><?= number_format((int)$sr['total']) ?></span>
                                                    </td>
                                                    <td class="text-end text-muted">
                                                        <?= number_format((int)$sr['total_paginas']) ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="pct-bar flex-grow-1">
                                                                <div class="pct-fill" style="width:<?= min($pctDep, 100) ?>%"></div>
                                                            </div>
                                                            <span style="font-size:.78rem;color:var(--clr-muted);white-space:nowrap;">
                                                                <?= $pctDep ?>%
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($vista === 'usuario'): ?>
            <!-- ══════════════ VISTA POR USUARIO ══════════════ -->
            <div class="accordion usu-accordion" id="accordionUsu">
                <?php $usuIdx = 0;
                foreach ($byUsuario as $usuNombre => $usuData):
                    $usuId = 'usu' . $usuIdx++;
                    $usuAvatar = $userService->getUserAvatar($usuData['user_id'] ?? 0);
                ?>
                    <div class="accordion-item border-0 mb-2 rounded overflow-hidden shadow-sm usu-item"
                        data-usu="<?= strtolower(ResponseHelper::h($usuNombre)) ?>">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $usuId ?>">
                                <img src="../<?= ResponseHelper::h($usuAvatar) ?>" class="rounded-circle me-2" width="24" height="24" style="object-fit:cover; border:1px solid var(--clr-primary);" alt="Avatar">
                                <span style="flex-grow:1;"><?= ResponseHelper::h($usuNombre) ?></span>
                                <span style="display:flex;gap:.4rem;margin-right:.5rem;">
                                    <span class="dep-badge"><?= number_format($usuData['subtotal']) ?> docs</span>
                                    <span class="dep-badge" style="background:#8b5cf6;"><?= number_format($usuData['carpetas']) ?> carpetas</span>
                                </span>
                            </button>
                        </h2>
                        <div id="<?= $usuId ?>" class="accordion-collapse collapse"
                            data-bs-parent="#accordionUsu">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="tbl usu-tbl">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tipo Documental</th>
                                                <th class="text-end">Documentos</th>
                                                <th class="text-end">Folios</th>
                                                <th style="min-width:130px;">% del usuario</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $subtotal = $usuData['subtotal'];
                                            foreach ($usuData['series'] as $j => $sr):
                                                $pctUsu = $subtotal > 0
                                                    ? round(((int)$sr['total'] / $subtotal) * 100, 1)
                                                    : 0;
                                            ?>
                                                <tr class="usu-row">
                                                    <td><span class="rank-badge rank-n"><?= $j + 1 ?></span></td>
                                                    <td class="fw-medium usu-serie"><?= ResponseHelper::h($sr['serie']) ?></td>
                                                    <td class="text-end">
                                                        <span class="count-pill"><?= number_format((int)$sr['total']) ?></span>
                                                    </td>
                                                    <td class="text-end text-muted">
                                                        <?= number_format((int)$sr['total_paginas']) ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="pct-bar flex-grow-1">
                                                                <div class="pct-fill" style="width:<?= min($pctUsu, 100) ?>%"></div>
                                                            </div>
                                                            <span style="font-size:.78rem;color:var(--clr-muted);white-space:nowrap;">
                                                                <?= $pctUsu ?>%
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ── Buscador en tabla global ─────────────────────────────────── */
        const inputBuscar = document.getElementById('inputBuscar');

        inputBuscar.addEventListener('input', () => {
            const term = inputBuscar.value.trim().toLowerCase();

            /* Vista global */
            const rows = document.querySelectorAll('#tablaGlobal .tbl-row');
            if (rows.length) {
                let visible = 0;
                rows.forEach(tr => {
                    const serie = tr.querySelector('.tbl-serie')?.textContent.toLowerCase() ?? '';
                    const show = serie.includes(term);
                    tr.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                const noResult = document.getElementById('sinResultados');
                if (noResult) noResult.classList.toggle('d-none', visible > 0);
                return;
            }

            /* Vista por dependencia */
            const depItems = document.querySelectorAll('.dep-item');
            if (depItems.length) {
                depItems.forEach(item => {
                    const depName = item.dataset.dep ?? '';
                    const serieRows = item.querySelectorAll('.dep-row');
                    let anyVisible = false;

                    serieRows.forEach(tr => {
                        const serie = tr.querySelector('.dep-serie')?.textContent.toLowerCase() ?? '';
                        const show = serie.includes(term) || depName.includes(term);
                        tr.style.display = show ? '' : 'none';
                        if (show) anyVisible = true;
                    });

                    item.style.display = anyVisible ? '' : 'none';
                });
                return;
            }

            /* Vista por usuario */
            const usuItems = document.querySelectorAll('.usu-item');
            if (usuItems.length) {
                usuItems.forEach(item => {
                    const usuName = item.dataset.usu ?? '';
                    const serieRows = item.querySelectorAll('.usu-row');
                    let anyVisible = false;

                    serieRows.forEach(tr => {
                        const serie = tr.querySelector('.usu-serie')?.textContent.toLowerCase() ?? '';
                        const show = serie.includes(term) || usuName.includes(term);
                        tr.style.display = show ? '' : 'none';
                        if (show) anyVisible = true;
                    });

                    item.style.display = anyVisible ? '' : 'none';
                });
                return;
            }
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>