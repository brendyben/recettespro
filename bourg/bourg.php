<?php
session_start();
if (!isset($_SESSION['bourg_commune'])) {
    header("Location: ../login_bourg.php");
    exit;
}

$commune     = $_SESSION['bourg_commune'];
$nom         = $_SESSION['bourg_nom'];
$dateDuJour  = date('Y-m-d');

require_once '../includes/vendor/autoload.php';
use Google\Client;
use Google\Service\Sheets;

$jsonKeyPath   = realpath('../includes/client_secret.json');
$spreadsheetId = '1aUP3-D7L57AzSp_XGedWdlqBXhhgXPTBwkjP9Afbf4g';
$sheetName     = 'Sheet1';

$client = new Client();
$client->setApplicationName('TicketsKin - Bourg');
$client->setScopes([Sheets::SPREADSHEETS_READONLY]);
$client->setAuthConfig($jsonKeyPath);
$service = new Sheets($client);

$response = $service->spreadsheets_values->get($spreadsheetId, "$sheetName!A2:H");
$rows     = $response->getValues();

$marches    = [];
$total      = 0;
$countTickets = 0;
$agentsActifs = [];

foreach ($rows as $row) {
    if (count($row) < 7) continue;
    list($ticket, $marche, $rowCommune, $montant, $date, $heure, $agent) = $row;
    if ($rowCommune !== $commune || $date !== $dateDuJour) continue;

    $montant = intval($montant);
    $total  += $montant;
    $countTickets++;
    $agentsActifs[$agent] = true;

    if (!isset($marches[$marche])) $marches[$marche] = ["total" => 0, "agents" => [], "count" => 0];
    $marches[$marche]["total"]  += $montant;
    $marches[$marche]["count"]++;
    if (!isset($marches[$marche]["agents"][$agent]))
        $marches[$marche]["agents"][$agent] = 0;
    $marches[$marche]["agents"][$agent] += $montant;
}

uasort($marches, fn($a,$b) => $b['total'] <=> $a['total']);
$topMarche = array_key_first($marches);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bourgmestre — <?= htmlspecialchars($commune) ?> | RecettesPro</title>
  <link rel="stylesheet" href="../includes/rpro.css">
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
        <div class="sidebar-user-name"><?= htmlspecialchars($nom) ?></div>
        <div class="sidebar-user-role">Bourgmestre · <?= htmlspecialchars($commune) ?></div>
      </div>
    </div>
    <div class="sidebar-nav">
      <div class="sidebar-section-label">Navigation</div>
      <a href="bourg.php" class="sidebar-link active"><span class="icon">📊</span> Tableau de bord</a>
      <a href="stats_bourg.php" class="sidebar-link"><span class="icon">📈</span> Statistiques</a>
      <a href="export_csv.php" class="sidebar-link"><span class="icon">📥</span> Exporter CSV</a>
    </div>
    <div class="sidebar-bottom">
      <a href="logout_bourg.php" class="sidebar-logout">🔒 Déconnexion</a>
    </div>
  </nav>

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="topbar-title">📍 Commune de <?= htmlspecialchars($commune) ?></div>
        <div style="font-size:.78rem;color:var(--clr-text-lt);">📅 <?= $dateDuJour ?></div>
      </div>
      <div class="topbar-actions">
        <button onclick="window.print()" class="btn btn-outline no-print">🖨️ Imprimer</button>
        <a href="stats_bourg.php" class="btn btn-primary no-print">📊 Statistiques</a>
      </div>
    </div>

    <div class="page-body">

      <!-- KPIs -->
      <div class="kpi-grid fade-up">
        <div class="kpi-card" style="--kpi-accent:var(--clr-accent);">
          <span class="kpi-icon">💰</span>
          <div class="kpi-value"><?= number_format($total, 0, ',', ' ') ?></div>
          <div class="kpi-label">FC collectés aujourd'hui</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:var(--clr-primary-lt);">
          <span class="kpi-icon">🧾</span>
          <div class="kpi-value"><?= $countTickets ?></div>
          <div class="kpi-label">Tickets émis</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:var(--clr-info);">
          <span class="kpi-icon">🛒</span>
          <div class="kpi-value"><?= count($marches) ?></div>
          <div class="kpi-label">Marchés actifs</div>
        </div>
        <div class="kpi-card" style="--kpi-accent:#8b5cf6;">
          <span class="kpi-icon">👤</span>
          <div class="kpi-value"><?= count($agentsActifs) ?></div>
          <div class="kpi-label">Agents actifs</div>
        </div>
      </div>

      <?php if ($topMarche): ?>
      <div class="alert alert-success fade-up fade-up-d1" style="margin-bottom:24px;">
        🏆 Marché leader aujourd'hui : <strong><?= htmlspecialchars($topMarche) ?></strong>
        — <?= number_format($marches[$topMarche]['total'], 0, ',', ' ') ?> FC
      </div>
      <?php endif; ?>

      <!-- Liste marchés -->
      <div class="section-label fade-up fade-up-d1">🛒 Marchés — Détail du jour</div>

      <?php if (empty($marches)): ?>
        <div class="alert alert-info fade-up">ℹ️ Aucune donnée enregistrée pour aujourd'hui dans cette commune.</div>
      <?php endif; ?>

      <div class="fade-up fade-up-d2">
      <?php $maxTotal = $marches ? max(array_column($marches, 'total')) : 1; ?>
      <?php $rank = 0; foreach ($marches as $nomMarche => $infos): $rank++; ?>
        <div class="accord-item" id="ai-<?= $rank ?>">
          <div class="accord-trigger" onclick="toggleAccord('ai-<?= $rank ?>')">
            <div class="accord-trigger-left">
              <div class="accord-badge">
                <?php if ($rank === 1) echo '🥇'; elseif ($rank === 2) echo '🥈'; elseif ($rank === 3) echo '🥉'; else echo '🛒'; ?>
              </div>
              <div>
                <div class="accord-name"><?= htmlspecialchars($nomMarche) ?></div>
                <div style="font-size:.75rem;color:var(--clr-text-lt);"><?= $infos['count'] ?> ticket<?= $infos['count'] > 1 ? 's' : '' ?> · <?= count($infos['agents']) ?> agent<?= count($infos['agents']) > 1 ? 's' : '' ?></div>
                <div class="prog-bar" style="width:180px;">
                  <div class="prog-bar-fill" style="width:<?= round(($infos['total']/$maxTotal)*100) ?>%"></div>
                </div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
              <div class="accord-total"><?= number_format($infos['total'], 0, ',', ' ') ?> FC</div>
              <div class="accord-chevron">▼</div>
            </div>
          </div>
          <div class="accord-body">
            <?php arsort($infos['agents']); $isTop = true; foreach ($infos['agents'] as $agent => $somme): ?>
              <div class="agent-row <?= $isTop ? 'top' : '' ?>">
                <span class="agent-row-name"><?= $isTop ? '🏆 ' : '👤 ' ?><?= htmlspecialchars($agent) ?></span>
                <span class="agent-row-amount"><?= number_format($somme, 0, ',', ' ') ?> FC</span>
              </div>
            <?php $isTop = false; endforeach; ?>
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
