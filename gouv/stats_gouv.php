<?php
session_start();
if (!isset($_SESSION['gouvernorat'])) {
    header("Location: ../login_gouv.php");
    exit;
}

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-d');

require_once '../includes/vendor/autoload.php';
use Google\Client;
use Google\Service\Sheets;

$jsonKeyPath   = realpath('../includes/client_secret.json');
$spreadsheetId = '1aUP3-D7L57AzSp_XGedWdlqBXhhgXPTBwkjP9Afbf4g';
$sheetName     = 'Sheet1';

$client = new Client();
$client->setApplicationName('TicketsKin Gouv Stats');
$client->setScopes([Sheets::SPREADSHEETS_READONLY]);
$client->setAuthConfig($jsonKeyPath);
$service = new Sheets($client);

$response = $service->spreadsheets_values->get($spreadsheetId, "$sheetName!A2:H");
$rows     = $response->getValues();

$communes = [];
$agents   = [];
$marches  = [];
$daily    = [];
$total    = 0;
$count    = 0;

foreach ($rows as $row) {
    if (count($row) < 7) continue;
    list($ticket, $marche, $commune, $montant, $date, $heure, $agent) = $row;
    if ($date < $start || $date > $end) continue;
    $montant = intval($montant);
    $total  += $montant;
    $count++;
    $communes[$commune] = ($communes[$commune] ?? 0) + $montant;
    $agents[$agent]     = ($agents[$agent]     ?? 0) + $montant;
    $marches[$marche]   = ($marches[$marche]   ?? 0) + $montant;
    $daily[$date]       = ($daily[$date]       ?? 0) + $montant;
}

arsort($communes);
arsort($agents);
arsort($marches);
ksort($daily);

$avgDay     = count($daily) > 0 ? intval($total / count($daily)) : 0;
$topCommune = $communes ? array_key_first($communes) : '—';
$topAgent   = $agents   ? array_key_first($agents)   : '—';
$topMarche  = $marches  ? array_key_first($marches)  : '—';
$maxCommune = $communes ? max(array_values($communes)) : 1;
$maxAgent   = $agents   ? max(array_values($agents))   : 1;
$maxMarche  = $marches  ? max(array_values($marches))  : 1;
$nbJours    = count($daily);
$avgTicket  = $count > 0 ? intval($total / $count) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Statistiques Globales | RecettesPro</title>
  <link rel="stylesheet" href="../includes/rpro.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>

    /* ── HERO TOTAL ──────────────────────────── */
    .hero-total {
      background: var(--clr-primary);
      border-radius: var(--radius-lg);
      padding: 36px 40px;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
    }
    .hero-total::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse 60% 100% at 90% 50%, rgba(201,162,39,.18) 0%, transparent 60%),
        radial-gradient(ellipse 40% 80% at 10% 0%,  rgba(34,160,90,.2)   0%, transparent 55%);
    }
    .hero-total::after {
      content: '';
      position: absolute; inset: 0;
      background-image: repeating-linear-gradient(
        45deg, rgba(255,255,255,.025) 0, rgba(255,255,255,.025) 1px,
        transparent 0, transparent 50%);
      background-size: 20px 20px;
    }
    .hero-left  { position: relative; z-index: 1; }
    .hero-right { position: relative; z-index: 1; display:flex; flex-direction:column; gap:10px; }
    .hero-label {
      font-size: .72rem; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: rgba(255,255,255,.5); margin-bottom: 8px;
    }
    .hero-value {
      font-family: var(--font-display);
      font-size: 3.4rem; font-weight: 700;
      color: #fff; line-height: 1; letter-spacing: -.5px;
    }
    .hero-value span { color: var(--clr-accent-lt); }
    .hero-period { margin-top: 10px; font-size: .82rem; color: rgba(255,255,255,.55); }
    .hero-chips  { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
    .hero-chip {
      display: flex; align-items: center; gap: 7px;
      padding: 7px 14px;
      background: rgba(255,255,255,.09);
      border: 1px solid rgba(255,255,255,.14);
      border-radius: 99px; font-size: .8rem; color: rgba(255,255,255,.8);
    }
    .hero-chip strong { color: var(--clr-accent-lt); }
    .hero-badge {
      background: rgba(201,162,39,.12);
      border: 1px solid rgba(201,162,39,.25);
      border-radius: var(--radius-md);
      padding: 14px 18px; text-align: center; min-width: 150px;
    }
    .hero-badge-label { font-size: .68rem; color: rgba(255,255,255,.45); text-transform:uppercase; letter-spacing:.08em; margin-bottom:5px; }
    .hero-badge-val   { font-family:var(--font-display); font-size:1.4rem; font-weight:700; color:var(--clr-accent-lt); }
    @media(max-width:640px) {
      .hero-total { flex-direction:column; }
      .hero-right { flex-direction: row; flex-wrap:wrap; }
      .hero-value { font-size:2.4rem; }
    }

    /* ── KPI CARDS v2 ────────────────────────── */
    .kpi-grid-v2 {
      display: grid;
      grid-template-columns: repeat(4,1fr);
      gap: 16px; margin-bottom: 24px;
    }
    @media(max-width:900px) { .kpi-grid-v2 { grid-template-columns:repeat(2,1fr); } }
    @media(max-width:480px) { .kpi-grid-v2 { grid-template-columns:1fr; } }

    .kpi2 {
      background: var(--clr-surface);
      border: 1px solid var(--clr-border);
      border-radius: var(--radius-lg);
      padding: 20px 22px;
      display: flex; align-items: center; gap: 16px;
      transition: box-shadow var(--transition), transform var(--transition);
    }
    .kpi2:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
    .kpi2-icon  {
      width: 48px; height: 48px; flex-shrink: 0;
      border-radius: var(--radius-md);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem;
    }
    .kpi2-val   { font-family:var(--font-display); font-size:1.7rem; font-weight:700; color:var(--clr-primary); line-height:1; }
    .kpi2-label { font-size:.72rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:var(--clr-text-lt); margin-top:4px; }
    .kpi2-sub   { font-size:.75rem; color:var(--clr-text-md); margin-top:3px; }

    /* ── CHARTS ──────────────────────────────── */
    .charts-row {
      display: grid; grid-template-columns: 3fr 2fr;
      gap: 20px; margin-bottom: 20px;
    }
    @media(max-width:860px) { .charts-row { grid-template-columns:1fr; } }

    .chart-card { background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:var(--radius-lg); overflow:hidden; }
    .chart-card-header {
      padding: 15px 22px 12px;
      border-bottom: 1px solid var(--clr-border);
      display: flex; align-items:center; justify-content:space-between;
    }
    .chart-card-title { font-size:.8rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--clr-text-md); }
    .chart-card-meta  { font-size:.72rem; color:var(--clr-text-lt); }
    .chart-card-body  { padding: 20px 22px; }

    /* ── RANK TABLES ─────────────────────────── */
    .rank-grid {
      display: grid; grid-template-columns: repeat(3,1fr);
      gap: 20px; margin-bottom: 20px;
    }
    @media(max-width:900px) { .rank-grid { grid-template-columns:1fr; } }

    .rank-table { width:100%; border-spacing:0; border-collapse:collapse; }
    .rank-table thead th {
      padding: 9px 16px; font-size:.68rem; font-weight:700;
      letter-spacing:.09em; text-transform:uppercase;
      color:var(--clr-text-lt); border-bottom:1px solid var(--clr-border); text-align:left;
    }
    .rank-table tbody tr:hover td { background:var(--clr-bg); }
    .rank-table tbody td { padding:0; border-bottom:1px solid var(--clr-border); }
    .rank-table tbody tr:last-child td { border-bottom:none; }

    .rank-row-inner { padding:11px 16px 6px; display:flex; align-items:center; gap:10px; }
    .rank-medal { font-size:1rem; flex-shrink:0; width:22px; text-align:center; }
    .rank-num {
      width:22px; height:22px; border-radius:50%;
      background:var(--clr-bg);
      display:flex; align-items:center; justify-content:center;
      font-size:.7rem; font-weight:700; color:var(--clr-text-lt); flex-shrink:0;
    }
    .rank-name  { font-size:.86rem; font-weight:500; color:var(--clr-text); flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .rank-amt   { font-family:var(--font-mono); font-size:.82rem; font-weight:700; color:var(--clr-primary); white-space:nowrap; }
    .rank-bar   { height:4px; background:var(--clr-bg); border-radius:99px; overflow:hidden; margin:0 16px 10px; }
    .rank-bar-fill { height:100%; border-radius:99px; }
    .bar-green  { background: linear-gradient(90deg, var(--clr-primary-lt), var(--clr-primary-md)); }
    .bar-gold   { background: linear-gradient(90deg, var(--clr-accent), var(--clr-accent-lt)); }
    .bar-blue   { background: linear-gradient(90deg, #1d6fa4, #4aa3d4); }

    /* ── SECTION DIVIDER ─────────────────────── */
    .section-divider {
      display:flex; align-items:center; gap:12px; margin-bottom:16px;
    }
    .section-divider-text {
      font-size:.7rem; font-weight:700; letter-spacing:.1em;
      text-transform:uppercase; color:var(--clr-text-lt); white-space:nowrap;
    }
    .section-divider-line { flex:1; height:1px; background:var(--clr-border); }
  </style>
</head>
<body>
<div class="dash-layout">

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-text">R-<span>Pro</span></div>
      <div class="sidebar-logo-sub">RecettesPro 1.0</div>
    </div>
    <div class="sidebar-user">
      <div class="sidebar-user-avatar">🏛️</div>
      <div>
        <div class="sidebar-user-name">Gouverneur</div>
        <div class="sidebar-user-role">Vue globale — Kinshasa</div>
      </div>
    </div>
    <div class="sidebar-nav">
      <div class="sidebar-section-label">Navigation</div>
      <a href="gouv.php"        class="sidebar-link"><span class="icon">🗺️</span> Tableau de bord</a>
      <a href="stats_gouv.php"  class="sidebar-link active"><span class="icon">📈</span> Statistiques</a>
      <a href="export_gouv.php" class="sidebar-link"><span class="icon">📥</span> Exporter CSV</a>
    </div>
    <div class="sidebar-bottom">
      <a href="logout_gouv.php" class="sidebar-logout">🔒 Déconnexion</a>
    </div>
  </nav>

  <!-- MAIN -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📊 Statistiques Globales — Kinshasa</div>
      <div class="topbar-actions">
        <button onclick="window.print()" class="btn btn-outline no-print">🖨️ Imprimer</button>
      </div>
    </div>

    <div class="page-body">

      <!-- Filtre période -->
      <div class="chart-card fade-up no-print" style="margin-bottom:22px;">
        <div class="chart-card-body" style="padding:16px 22px;">
          <form method="get" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;">
            <div class="field" style="margin:0;flex:1;min-width:130px;">
              <label>📅 Du</label>
              <input type="date" name="start" value="<?= $start ?>">
            </div>
            <div class="field" style="margin:0;flex:1;min-width:130px;">
              <label>📅 Au</label>
              <input type="date" name="end" value="<?= $end ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="height:44px;padding:0 24px;align-self:flex-end;">
              🔄 Actualiser
            </button>
          </form>
        </div>
      </div>

      <!-- HERO -->
      <div class="hero-total fade-up">
        <div class="hero-left">
          <div class="hero-label">Total collecté sur la période</div>
          <div class="hero-value"><?= number_format($total,0,',',' ') ?> <span>FC</span></div>
          <div class="hero-period">📅 <?= $start ?> → <?= $end ?> · <?= $nbJours ?> jour<?= $nbJours>1?'s':'' ?> actif<?= $nbJours>1?'s':'' ?></div>
          <div class="hero-chips">
            <div class="hero-chip">🧾 <strong><?= number_format($count,0,',',' ') ?></strong> tickets</div>
            <div class="hero-chip">🏘️ <strong><?= count($communes) ?></strong> communes</div>
            <div class="hero-chip">🛒 <strong><?= count($marches) ?></strong> marchés</div>
            <div class="hero-chip">👤 <strong><?= count($agents) ?></strong> agents</div>
          </div>
        </div>
        <div class="hero-right">
          <div class="hero-badge">
            <div class="hero-badge-label">Moyenne / jour</div>
            <div class="hero-badge-val"><?= number_format($avgDay,0,',',' ') ?> FC</div>
          </div>
          <div class="hero-badge">
            <div class="hero-badge-label">🏆 Commune leader</div>
            <div class="hero-badge-val" style="font-size:1.1rem;"><?= htmlspecialchars($topCommune) ?></div>
          </div>
          <div class="hero-badge">
            <div class="hero-badge-label">Moy. / ticket</div>
            <div class="hero-badge-val" style="font-size:1.1rem;"><?= number_format($avgTicket,0,',',' ') ?> FC</div>
          </div>
        </div>
      </div>

      <!-- KPI v2 -->
      <div class="kpi-grid-v2 fade-up fade-up-d1">
        <div class="kpi2">
          <div class="kpi2-icon" style="background:rgba(201,162,39,.12);">💰</div>
          <div>
            <div class="kpi2-val"><?= number_format($total,0,',',' ') ?></div>
            <div class="kpi2-label">FC Total</div>
            <div class="kpi2-sub">Toute la période</div>
          </div>
        </div>
        <div class="kpi2">
          <div class="kpi2-icon" style="background:rgba(25,125,75,.1);">🧾</div>
          <div>
            <div class="kpi2-val"><?= number_format($count,0,',',' ') ?></div>
            <div class="kpi2-label">Tickets émis</div>
            <div class="kpi2-sub">Moy. <?= $nbJours>0?number_format(intval($count/$nbJours),0,',',' '):0 ?>/jour</div>
          </div>
        </div>
        <div class="kpi2">
          <div class="kpi2-icon" style="background:rgba(29,111,164,.1);">📅</div>
          <div>
            <div class="kpi2-val"><?= number_format($avgDay,0,',',' ') ?></div>
            <div class="kpi2-label">FC / jour moy.</div>
            <div class="kpi2-sub"><?= $nbJours ?> jour<?= $nbJours>1?'s':'' ?> enregistré<?= $nbJours>1?'s':'' ?></div>
          </div>
        </div>
        <div class="kpi2">
          <div class="kpi2-icon" style="background:rgba(139,92,246,.1);">🏘️</div>
          <div>
            <div class="kpi2-val"><?= count($communes) ?><span style="font-size:1rem;color:var(--clr-text-lt);"> /21</span></div>
            <div class="kpi2-label">Communes actives</div>
            <div class="kpi2-sub">🏆 <?= htmlspecialchars($topCommune) ?></div>
          </div>
        </div>
      </div>

      <!-- CHARTS ligne + donut -->
      <div class="charts-row fade-up fade-up-d2">
        <div class="chart-card">
          <div class="chart-card-header">
            <span class="chart-card-title">📈 Évolution quotidienne</span>
            <span class="chart-card-meta"><?= $nbJours ?> point<?= $nbJours>1?'s':'' ?></span>
          </div>
          <div class="chart-card-body">
            <canvas id="chartDaily" height="140"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-card-header">
            <span class="chart-card-title">🏘️ Répartition communes</span>
            <span class="chart-card-meta"><?= count($communes) ?> actives</span>
          </div>
          <div class="chart-card-body">
            <canvas id="chartCommPie" height="180"></canvas>
          </div>
        </div>
      </div>

      <!-- BAR horizontal communes -->
      <div class="chart-card fade-up fade-up-d2" style="margin-bottom:24px;">
        <div class="chart-card-header">
          <span class="chart-card-title">🏘️ Classement des communes</span>
          <span class="chart-card-meta">Recettes décroissantes</span>
        </div>
        <div class="chart-card-body">
          <canvas id="chartComm" height="<?= max(80, count($communes)*22) ?>"></canvas>
        </div>
      </div>

      <!-- RANKING TABLES -->
      <div class="section-divider fade-up">
        <div class="section-divider-text">🏆 Classements détaillés</div>
        <div class="section-divider-line"></div>
      </div>

      <div class="rank-grid fade-up fade-up-d3">

        <!-- Communes -->
        <div class="chart-card">
          <div class="chart-card-header">
            <span class="chart-card-title">🏘️ Top Communes</span>
            <span class="chart-card-meta"><?= count($communes) ?> total</span>
          </div>
          <table class="rank-table">
            <thead><tr><th colspan="2">Commune</th><th>FC</th></tr></thead>
            <tbody>
            <?php $r=0; foreach($communes as $c=>$v): $r++; if($r>10) break; ?>
              <tr>
                <td colspan="3">
                  <div class="rank-row-inner">
                    <?php if($r<=3): ?><span class="rank-medal"><?= ['🥇','🥈','🥉'][$r-1] ?></span>
                    <?php else: ?><div class="rank-num"><?= $r ?></div><?php endif; ?>
                    <span class="rank-name"><?= htmlspecialchars($c) ?></span>
                    <span class="rank-amt"><?= number_format($v,0,',',' ') ?></span>
                  </div>
                  <div class="rank-bar">
                    <div class="rank-bar-fill bar-green" style="width:<?= round(($v/$maxCommune)*100) ?>%"></div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Marchés -->
        <div class="chart-card">
          <div class="chart-card-header">
            <span class="chart-card-title">🛒 Top Marchés</span>
            <span class="chart-card-meta"><?= count($marches) ?> total</span>
          </div>
          <table class="rank-table">
            <thead><tr><th colspan="2">Marché</th><th>FC</th></tr></thead>
            <tbody>
            <?php $r=0; foreach($marches as $m=>$v): $r++; if($r>10) break; ?>
              <tr>
                <td colspan="3">
                  <div class="rank-row-inner">
                    <?php if($r<=3): ?><span class="rank-medal"><?= ['🥇','🥈','🥉'][$r-1] ?></span>
                    <?php else: ?><div class="rank-num"><?= $r ?></div><?php endif; ?>
                    <span class="rank-name"><?= htmlspecialchars($m) ?></span>
                    <span class="rank-amt"><?= number_format($v,0,',',' ') ?></span>
                  </div>
                  <div class="rank-bar">
                    <div class="rank-bar-fill bar-gold" style="width:<?= round(($v/$maxMarche)*100) ?>%"></div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Agents -->
        <div class="chart-card">
          <div class="chart-card-header">
            <span class="chart-card-title">👤 Top Agents</span>
            <span class="chart-card-meta"><?= count($agents) ?> actifs</span>
          </div>
          <table class="rank-table">
            <thead><tr><th colspan="2">Agent</th><th>FC</th></tr></thead>
            <tbody>
            <?php $r=0; foreach($agents as $a=>$v): $r++; if($r>10) break; ?>
              <tr>
                <td colspan="3">
                  <div class="rank-row-inner">
                    <?php if($r<=3): ?><span class="rank-medal"><?= ['🥇','🥈','🥉'][$r-1] ?></span>
                    <?php else: ?><div class="rank-num"><?= $r ?></div><?php endif; ?>
                    <span class="rank-name"><?= htmlspecialchars($a) ?></span>
                    <span class="rank-amt"><?= number_format($v,0,',',' ') ?></span>
                  </div>
                  <div class="rank-bar">
                    <div class="rank-bar-fill bar-blue" style="width:<?= round(($v/$maxAgent)*100) ?>%"></div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div><!-- /rank-grid -->

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div><!-- /dash-layout -->

<script>
const PALETTE = ['#0f4c35','#197d4b','#22a05a','#c9a227','#f0c84a',
                 '#1d6fa4','#8b5cf6','#e67e22','#c0392b','#2ecc71',
                 '#16a085','#d35400','#8e44ad','#2c3e50','#27ae60'];

new Chart(document.getElementById('chartDaily'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_keys($daily)) ?>,
    datasets: [{
      label: 'FC / jour',
      data: <?= json_encode(array_values($daily)) ?>,
      fill: true,
      borderColor: '#197d4b',
      backgroundColor: ctx => {
        const g = ctx.chart.ctx.createLinearGradient(0,0,0,200);
        g.addColorStop(0,'rgba(25,125,75,.15)');
        g.addColorStop(1,'rgba(25,125,75,.01)');
        return g;
      },
      borderWidth: 2.5,
      pointBackgroundColor: '#fff',
      pointBorderColor: '#197d4b',
      pointBorderWidth: 2,
      pointRadius: 5, pointHoverRadius: 7,
      tension: 0.45
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ' '+ctx.parsed.y.toLocaleString()+' FC' } }
    },
    scales: {
      x: { grid:{ display:false }, ticks:{ font:{size:10} } },
      y: { grid:{ color:'rgba(0,0,0,.04)' }, ticks:{ callback: v => v>=1000?(v/1000)+'k':v, font:{size:10} } }
    }
  }
});

new Chart(document.getElementById('chartCommPie'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_keys($communes)) ?>,
    datasets: [{ data: <?= json_encode(array_values($communes)) ?>,
      backgroundColor: PALETTE, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
  },
  options: {
    cutout: '62%',
    plugins: {
      legend: { position:'bottom', labels:{ boxWidth:10, padding:10, font:{size:10} } },
      tooltip: { callbacks: { label: ctx => ' '+ctx.label+': '+ctx.parsed.toLocaleString()+' FC' } }
    }
  }
});

new Chart(document.getElementById('chartComm'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_keys($communes)) ?>,
    datasets: [{ label:'FC', data: <?= json_encode(array_values($communes)) ?>,
      backgroundColor: PALETTE, borderRadius: 5, borderSkipped: false }]
  },
  options: {
    indexAxis: 'y',
    plugins: {
      legend:{ display:false },
      tooltip:{ callbacks:{ label: ctx => ' '+ctx.parsed.x.toLocaleString()+' FC' } }
    },
    scales: {
      x: { grid:{ color:'rgba(0,0,0,.04)' }, ticks:{ callback: v => v>=1000?(v/1000)+'k':v, font:{size:10} } },
      y: { grid:{ display:false }, ticks:{ font:{size:11} } }
    }
  }
});
</script>
</body>
</html>
