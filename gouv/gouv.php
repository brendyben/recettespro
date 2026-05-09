<?php
session_start();
if (!isset($_SESSION['gouvernorat'])) {
    header("Location: ../login_gouv.php");
    exit;
}

$nomGouv    = $_SESSION['gouvernorat'];
$dateDuJour = date('Y-m-d');

require_once '../includes/vendor/autoload.php';
use Google\Client;
use Google\Service\Sheets;

$jsonKeyPath   = realpath('../includes/client_secret.json');
$spreadsheetId = '1aUP3-D7L57AzSp_XGedWdlqBXhhgXPTBwkjP9Afbf4g';
$sheetName     = 'Sheet1';

$client = new Client();
$client->setApplicationName('TicketsKin Gouverneur');
$client->setScopes([Sheets::SPREADSHEETS_READONLY]);
$client->setAuthConfig($jsonKeyPath);
$service = new Sheets($client);

$response = $service->spreadsheets_values->get($spreadsheetId, "$sheetName!A2:H");
$rows     = $response->getValues();

$communes     = [];
$total        = 0;
$totalTickets = 0;
$agentsGlobal = [];

foreach ($rows as $row) {
    if (count($row) < 7) continue;
    list($ticket, $marche, $commune, $montant, $date, $heure, $agent) = $row;
    if ($date !== $dateDuJour) continue;

    $montant = intval($montant);
    $total  += $montant;
    $totalTickets++;
    $agentsGlobal[$agent] = true;

    if (!isset($communes[$commune])) {
        $communes[$commune] = ["total" => 0, "marches" => [], "count" => 0, "agents" => []];
    }
    $communes[$commune]["total"]  += $montant;
    $communes[$commune]["count"]++;
    $communes[$commune]["agents"][$agent] = true;
    $communes[$commune]["marches"][$marche] =
        ($communes[$commune]["marches"][$marche] ?? 0) + $montant;
}

uasort($communes, fn($a,$b) => $b["total"] <=> $a["total"]);
$topCommune = array_key_first($communes);
$maxCommune = $communes ? max(array_column($communes, 'total')) : 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gouverneur — Vue Globale | RecettesPro</title>
  <link rel="stylesheet" href="../includes/rpro.css">
  <style>
    .commune-rank-num {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: var(--clr-bg);
      display: flex; align-items: center; justify-content: center;
      font-size: .78rem; font-weight: 700;
      color: var(--clr-text-md);
      flex-shrink: 0;
    }
    .rank-1-bg { background: rgba(212,160,23,.15); color: #d4a017; }
    .rank-2-bg { background: rgba(158,172,180,.15); color: #7a9196; }
    .rank-3-bg { background: rgba(184,115,51,.15); color: #b87333; }
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
      <a href="gouv.php"       class="sidebar-link active"><span class="icon">🗺️</span> Tableau de bord</a>
      <a href="stats_gouv.php" class="sidebar-link"><span class="icon">📈</span> Statistiques</a>
      <a href="export_gouv.php" class="sidebar-link"><span class="icon">📥</span> Exporter CSV</a>
    </div>
    <div class="sidebar-bottom">
      <a href="logout_gouv.php" class="sidebar-logout">🔒 Déconnexion</a>
    </div>
  </nav>

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="topbar-title">🗺️ Vue Globale — Ville de Kinshasa</div>
        <div style="font-size:.78rem;color:var(--clr-text-lt);">📅 <?= $dateDuJour ?></div>
      </div>
      <div class="topbar-actions">
        <a href="export_gouv.php" class="btn btn-outline no-print">📥 Export CSV</a>
        <button onclick="window.print()" class="btn btn-outline no-print">🖨️ Imprimer</button>
        <a href="stats_gouv.php" class="btn btn-primary no-print">📊 Statistiques</a>
      </div>
    </div>

    <div class="page-body">

      <!-- KPIs globaux -->
      <div class="kpi-grid fade-up">
        <div class="kpi-card" style="--kpi-accent:var(--clr-accent);">
          <span class="kpi-icon">💰</span>
          <div class="kpi-value"><?= number_format($total, 0, ',', ' ') ?></div>
          <div class="kpi-label">FC — Total global du jour</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:var(--clr-primary-lt);">
          <span class="kpi-icon">🧾</span>
          <div class="kpi-value"><?= $totalTickets ?></div>
          <div class="kpi-label">Tickets émis</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:var(--clr-info);">
          <span class="kpi-icon">🏘️</span>
          <div class="kpi-value"><?= count($communes) ?></div>
          <div class="kpi-label">Communes actives</div>
          <div class="kpi-sub">sur 21 communes</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#8b5cf6;">
          <span class="kpi-icon">👤</span>
          <div class="kpi-value"><?= count($agentsGlobal) ?></div>
          <div class="kpi-label">Agents actifs</div>
        </div>
      </div>

      <?php if ($topCommune): ?>
      <div class="alert alert-success fade-up fade-up-d1" style="margin-bottom:24px;">
        🏆 Commune championne aujourd'hui : <strong><?= htmlspecialchars($topCommune) ?></strong>
        — <?= number_format($communes[$topCommune]['total'], 0, ',', ' ') ?> FC
        (<?= $communes[$topCommune]['count'] ?> tickets · <?= count($communes[$topCommune]['agents']) ?> agents)
      </div>
      <?php else: ?>
      <div class="alert alert-info fade-up" style="margin-bottom:24px;">
        ℹ️ Aucune donnée synchronisée pour aujourd'hui. Les agents doivent effectuer une synchronisation.
      </div>
      <?php endif; ?>

      <!-- Classement communes -->
      <div class="section-label fade-up fade-up-d1">🏘️ Classement des communes — Aujourd'hui</div>

      <div class="fade-up fade-up-d2">
      <?php $rank = 0; foreach ($communes as $commune => $infos): $rank++; ?>
        <div class="accord-item" id="ci-<?= $rank ?>">
          <div class="accord-trigger" onclick="toggleAccord('ci-<?= $rank ?>')">
            <div class="accord-trigger-left">
              <div class="commune-rank-num <?= $rank<=3 ? 'rank-'.$rank.'-bg' : '' ?>">
                <?= $rank <= 3 ? ['🥇','🥈','🥉'][$rank-1] : $rank ?>
              </div>
              <div>
                <div class="accord-name"><?= htmlspecialchars($commune) ?></div>
                <div style="font-size:.75rem;color:var(--clr-text-lt);">
                  <?= $infos['count'] ?> tickets · <?= count($infos['agents']) ?> agents · <?= count($infos['marches']) ?> marchés
                </div>
                <div class="prog-bar" style="width:220px;">
                  <div class="prog-bar-fill" style="width:<?= round(($infos['total']/$maxCommune)*100) ?>%"></div>
                </div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
              <div class="accord-total"><?= number_format($infos['total'], 0, ',', ' ') ?> FC</div>
              <div class="accord-chevron">▼</div>
            </div>
          </div>
          <div class="accord-body">
            <?php
              arsort($infos['marches']);
              $maxM = max(array_values($infos['marches']));
              $isFirst = true;
              foreach ($infos['marches'] as $marche => $somme):
            ?>
              <div class="agent-row <?= $isFirst ? 'top' : '' ?>">
                <span class="agent-row-name"><?= $isFirst ? '🛒' : '🛒' ?> <?= htmlspecialchars($marche) ?></span>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="prog-bar" style="width:100px;">
                    <div class="prog-bar-fill" style="width:<?= round(($somme/$maxM)*100) ?>%"></div>
                  </div>
                  <span class="agent-row-amount"><?= number_format($somme, 0, ',', ' ') ?> FC</span>
                </div>
              </div>
            <?php $isFirst = false; endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div><!-- /dash-layout -->

<script>
function toggleAccord(id) {
  const el = document.getElementById(id);
  el.classList.toggle('open');
}
</script>
</body>
</html>
