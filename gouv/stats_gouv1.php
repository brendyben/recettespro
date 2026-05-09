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

$avgDay    = count($daily) > 0 ? intval($total / count($daily)) : 0;
$topCommune = $communes ? array_key_first($communes) : '—';
$topAgent   = $agents   ? array_key_first($agents)   : '—';
$topMarche  = $marches  ? array_key_first($marches)  : '—';
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
    .charts-grid-3 {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    @media (max-width: 800px) { .charts-grid-3 { grid-template-columns: 1fr; } }
    .top-table { width: 100%; border-collapse: collapse; }
    .top-table th {
      padding: 10px 16px; font-size: .72rem; font-weight: 700;
      letter-spacing: .07em; text-transform: uppercase;
      color: var(--clr-text-lt); border-bottom: 1px solid var(--clr-border); text-align: left;
    }
    .top-table td { padding: 11px 16px; font-size: .88rem; border-bottom: 1px solid var(--clr-border); }
    .top-table tr:last-child td { border-bottom: none; }
    .top-table tr:hover td { background: var(--clr-bg); }
    .td-rank { font-weight: 700; color: var(--clr-text-lt); width: 36px; }
    .td-name { color: var(--clr-text); font-weight: 500; }
    .td-amount { font-family: var(--font-mono); font-weight: 600; color: var(--clr-primary); }
    .pct { font-size: .75rem; color: var(--clr-text-lt); }
    .three-col { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-bottom: 20px; }
    @media (max-width: 768px) { .three-col { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<div class="dash-layout">

  <!-- Sidebar -->
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

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📊 Statistiques Globales — Kinshasa</div>
      <div class="topbar-actions">
        <button onclick="window.print()" class="btn btn-outline no-print">🖨️ Imprimer</button>
      </div>
    </div>

    <div class="page-body">

      <!-- Filtre période -->
      <div class="filter-bar fade-up">
        <form method="get" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;width:100%;">
          <div class="field">
            <label>📅 Du</label>
            <input type="date" name="start" value="<?= $start ?>">
          </div>
          <div class="field">
            <label>📅 Au</label>
            <input type="date" name="end" value="<?= $end ?>">
          </div>
          <button type="submit" class="btn btn-primary" style="height:44px;align-self:flex-end;">🔄 Actualiser</button>
        </form>
      </div>

      <!-- KPIs -->
      <div class="kpi-grid fade-up fade-up-d1">
        <div class="kpi-card" style="--kpi-accent:var(--clr-accent);">
          <span class="kpi-icon">💰</span>
          <div class="kpi-value"><?= number_format($total, 0, ',', ' ') ?></div>
          <div class="kpi-label">FC — Total période</div>
          <div class="kpi-sub"><?= $start ?> → <?= $end ?></div>
        </div>
        <div class="kpi-card" style="--kpi-accent:var(--clr-primary-lt);">
          <span class="kpi-icon">🧾</span>
          <div class="kpi-value"><?= number_format($count, 0, ',', ' ') ?></div>
          <div class="kpi-label">Tickets total</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:var(--clr-info);">
          <span class="kpi-icon">📅</span>
          <div class="kpi-value"><?= number_format($avgDay, 0, ',', ' ') ?></div>
          <div class="kpi-label">FC / jour (moy.)</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#8b5cf6;">
          <span class="kpi-icon">🏘️</span>
          <div class="kpi-value"><?= count($communes) ?></div>
          <div class="kpi-label">Communes actives</div>
          <div class="kpi-sub">🏆 <?= htmlspecialchars($topCommune) ?></div>
        </div>
      </div>

      <!-- Chart évolution + communes -->
      <div class="charts-grid-3 fade-up fade-up-d2">
        <div class="card">
          <div class="card-header"><span class="card-header-title">📈 Évolution quotidienne (global)</span></div>
          <div class="card-body">
            <canvas id="chartDaily" height="160"></canvas>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-header-title">🏘️ Part par commune</span></div>
          <div class="card-body">
            <canvas id="chartCommPie" height="200"></canvas>
          </div>
        </div>
      </div>

      <!-- Bar communes -->
      <div class="card fade-up fade-up-d2" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-header-title">🏘️ Classement des communes</span></div>
        <div class="card-body">
          <canvas id="chartComm" height="120"></canvas>
        </div>
      </div>

      <!-- Top tables -->
      <div class="three-col fade-up fade-up-d3">
        <div class="card">
          <div class="card-header"><span class="card-header-title">🏘️ Top Communes</span></div>
          <table class="top-table">
            <thead><tr><th>#</th><th>Commune</th><th>FC</th></tr></thead>
            <tbody>
              <?php $r=0; foreach ($communes as $c => $v): $r++; if($r>10) break; ?>
                <tr>
                  <td class="td-rank"><?= $r<=3?['🥇','🥈','🥉'][$r-1]:$r ?></td>
                  <td class="td-name"><?= htmlspecialchars($c) ?></td>
                  <td class="td-amount"><?= number_format($v,0,',',' ') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-header-title">🛒 Top Marchés</span></div>
          <table class="top-table">
            <thead><tr><th>#</th><th>Marché</th><th>FC</th></tr></thead>
            <tbody>
              <?php $r=0; foreach ($marches as $m => $v): $r++; if($r>10) break; ?>
                <tr>
                  <td class="td-rank"><?= $r<=3?['🥇','🥈','🥉'][$r-1]:$r ?></td>
                  <td class="td-name"><?= htmlspecialchars($m) ?></td>
                  <td class="td-amount"><?= number_format($v,0,',',' ') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-header-title">👤 Top Agents</span></div>
          <table class="top-table">
            <thead><tr><th>#</th><th>Agent</th><th>FC</th></tr></thead>
            <tbody>
              <?php $r=0; foreach ($agents as $a => $v): $r++; if($r>10) break; ?>
                <tr>
                  <td class="td-rank"><?= $r<=3?['🥇','🥈','🥉'][$r-1]:$r ?></td>
                  <td class="td-name"><?= htmlspecialchars($a) ?></td>
                  <td class="td-amount"><?= number_format($v,0,',',' ') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-body -->
  </div>
</div>

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
      borderColor: '#0f4c35',
      backgroundColor: 'rgba(15,76,53,.08)',
      borderWidth: 2.5,
      pointBackgroundColor: '#0f4c35',
      pointRadius: 4,
      tension: 0.4
    }]
  },
  options: { plugins:{legend:{display:false}}, scales:{y:{ticks:{callback:v=>v.toLocaleString()}}} }
});

new Chart(document.getElementById('chartCommPie'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_keys($communes)) ?>,
    datasets: [{ data: <?= json_encode(array_values($communes)) ?>,
      backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }]
  },
  options: {
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font:{size:10} } } },
    cutout: '55%'
  }
});

new Chart(document.getElementById('chartComm'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_keys($communes)) ?>,
    datasets: [{ label: 'FC', data: <?= json_encode(array_values($communes)) ?>,
      backgroundColor: PALETTE, borderRadius: 6, borderSkipped: false }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: { x: { ticks: { callback: v => v.toLocaleString() } } }
  }
});
</script>
</body>
</html>
