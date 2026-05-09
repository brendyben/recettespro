<?php
session_start();
if (!isset($_SESSION['agent'])) {
    header("Location: ../login_agent.php");
    exit;
}
$agentNom = $_SESSION['agent'];

$agentsMap    = include '../includes/agents.php';
$agentCommune = $agentsMap[$agentNom] ?? '';

// Charger les marchés de la commune
$marches = [];
if (($handle = fopen('../data/marches.csv', 'r')) !== false) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== false) {
        if (trim($data[0]) === $agentCommune) {
            $marches[] = $data[1];
        }
    }
    fclose($handle);
}

include_once('../includes/functions.php');
require_once('../includes/sync_to_sheet.php');

$date        = date('Y-m-d');
$heureAffich = date('H:i');
$lastTicket  = '';
$lastData    = null;
$syncFeedback = null;

if (isset($_POST['generate'])) {
    $marche   = $_POST['marche'];
    $montant  = intval($_POST['montant']);
    $ticketNum = generateTicketNumber($agentCommune);
    $heure    = date('H:i:s');

    $bufferFile = '../data/tickets_buffer.csv';
    $line = [$date, $heure, $agentNom, $agentCommune, $marche, $montant, $ticketNum];
    $fp = fopen($bufferFile, 'a');
    fputcsv($fp, $line);
    fclose($fp);

    $lastTicket = $ticketNum;
    $lastData   = ['marche' => $marche, 'montant' => $montant, 'heure' => $heure];
}

if (isset($_POST['manual_sync'])) {
    $syncFeedback = syncBufferToGoogleSheet(
        '../data/tickets_buffer.csv',
        realpath('../includes/client_secret.json'),
        '1aUP3-D7L57AzSp_XGedWdlqBXhhgXPTBwkjP9Afbf4g'
    );
}

// Compter les tickets du buffer du jour
$countToday = 0;
$totalToday = 0;
if (file_exists('../data/tickets_buffer.csv')) {
    $rows = file('../data/tickets_buffer.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($rows as $row) {
        $cols = str_getcsv($row);
        if (isset($cols[0]) && $cols[0] === $date && isset($cols[2]) && $cols[2] === $agentNom) {
            $countToday++;
            $totalToday += intval($cols[5] ?? 0);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agent — <?= htmlspecialchars($agentNom) ?> | RecettesPro</title>
  <link rel="stylesheet" href="../includes/rpro.css">
  <style>
    .agent-header-bar {
      background: var(--clr-primary);
      color: #fff;
      padding: 0 32px;
      height: 60px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .agent-logo {
      font-family: var(--font-display);
      font-size: 1.4rem;
      font-weight: 700;
      color: #fff;
    }
    .agent-logo span { color: var(--clr-accent-lt); }
    .agent-info-pill {
      display: flex; align-items: center; gap: 10px;
      background: rgba(255,255,255,.1);
      border-radius: 99px;
      padding: 6px 16px;
      font-size: .83rem;
      color: rgba(255,255,255,.9);
    }
    .agent-commune-tag {
      background: var(--clr-accent);
      color: var(--clr-dark);
      border-radius: 99px;
      padding: 2px 10px;
      font-size: .72rem;
      font-weight: 700;
    }
    .agent-main {
      max-width: 960px;
      margin: 40px auto;
      padding: 0 24px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }
    @media (max-width: 680px) {
      .agent-main { grid-template-columns: 1fr; }
      .agent-header-bar { padding: 0 16px; }
    }
    .section-label {
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--clr-text-lt);
      margin-bottom: 14px;
    }
    .montant-input-wrap { position: relative; }
    .montant-input-wrap input { padding-left: 52px; font-size: 1.1rem; font-weight: 600; }
    .montant-prefix {
      position: absolute; left: 16px; top: 50%;
      transform: translateY(-50%);
      font-weight: 700; color: var(--clr-text-md); font-size: .9rem;
    }
    .quick-amounts { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
    .quick-amt {
      padding: 5px 12px;
      border: 1.5px solid var(--clr-border);
      border-radius: 99px;
      font-size: .78rem;
      font-weight: 600;
      color: var(--clr-text-md);
      cursor: pointer;
      transition: all var(--transition);
      background: var(--clr-surface);
    }
    .quick-amt:hover { border-color: var(--clr-primary-md); color: var(--clr-primary); }

    .ticket-placeholder {
      border: 2px dashed var(--clr-border);
      border-radius: var(--radius-lg);
      height: 280px;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--clr-text-lt);
      font-size: .88rem;
      text-align: center; gap: 8px;
    }
    .ticket-placeholder .big { font-size: 2.5rem; }

    .today-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid var(--clr-border);
      font-size: .88rem;
    }
    .today-row:last-child { border-bottom: none; }
    .today-row-label { color: var(--clr-text-md); }
    .today-row-val { font-weight: 700; font-family: var(--font-mono); color: var(--clr-text); }
  </style>
</head>
<body style="background:var(--clr-bg); min-height:100vh;">

<!-- Top Bar -->
<div class="agent-header-bar">
  <div class="agent-logo">R-<span>Pro</span> <span style="font-size:.75rem;font-weight:400;opacity:.6;font-family:var(--font-body);">| RecettesPro 1.0</span></div>
  <div style="display:flex;align-items:center;gap:12px;">
    <div class="agent-info-pill">
      👤 <?= htmlspecialchars($agentNom) ?>
      <span class="agent-commune-tag"><?= htmlspecialchars($agentCommune) ?></span>
    </div>
    <a href="logout_agent.php" style="color:rgba(255,255,255,.6);font-size:.8rem;text-decoration:none;">🔒 Quitter</a>
  </div>
</div>

<!-- Main -->
<div class="agent-main">

  <!-- Colonne gauche : Formulaire -->
  <div class="fade-up">
    <div class="section-label">📋 Enregistrement de ticket</div>
    <div class="card">
      <div class="card-body">
        <?php if ($syncFeedback): ?>
          <div class="alert <?= $syncFeedback['success'] ? 'alert-success' : 'alert-danger' ?>" style="margin-bottom:18px;">
            <?= $syncFeedback['success'] ? '✅' : '❌' ?> <?= htmlspecialchars($syncFeedback['message']) ?>
          </div>
        <?php endif; ?>

        <form method="post" id="ticketForm">
          <div class="field">
            <label for="marche">Marché</label>
            <select name="marche" id="marche" required>
              <?php foreach ($marches as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>"
                  <?= (isset($lastData) && $lastData['marche'] === $m) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label for="montant">Montant collecté</label>
            <div class="montant-input-wrap">
              <span class="montant-prefix">FC</span>
              <input type="number" id="montant" name="montant" min="0" step="100" required
                placeholder="0" value="<?= isset($lastData) ? $lastData['montant'] : '' ?>">
            </div>
            <div class="quick-amounts">
              <?php foreach ([500, 1000, 2000, 5000, 10000] as $amt): ?>
                <span class="quick-amt" onclick="setMontant(<?= $amt ?>)"><?= number_format($amt,0,',',' ') ?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <button type="submit" name="generate" class="btn btn-primary btn-lg btn-block" style="margin-top:8px;">
            💾 Générer le ticket
          </button>
        </form>

        <!-- Sync -->
        <form method="post" style="margin-top:16px;">
          <input type="hidden" name="manual_sync" value="1">
          <button type="submit" class="sync-btn">
            🔄 Synchroniser vers Google Sheets
          </button>
        </form>
      </div>
    </div>

    <!-- Stats du jour -->
    <div style="margin-top:20px;">
      <div class="section-label">📊 Ma journée</div>
      <div class="card">
        <div class="card-body" style="padding:16px 20px;">
          <div class="today-row">
            <span class="today-row-label">📅 Date</span>
            <span class="today-row-val"><?= $date ?></span>
          </div>
          <div class="today-row">
            <span class="today-row-label">🕐 Heure</span>
            <span class="today-row-val"><?= $heureAffich ?></span>
          </div>
          <div class="today-row">
            <span class="today-row-label">🧾 Tickets émis</span>
            <span class="today-row-val"><?= $countToday ?></span>
          </div>
          <div class="today-row">
            <span class="today-row-label">💰 Total collecté</span>
            <span class="today-row-val"><?= number_format($totalToday, 0, ',', ' ') ?> FC</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Colonne droite : Ticket généré -->
  <div class="fade-up fade-up-d1">
    <div class="section-label">🧾 Aperçu du dernier ticket</div>

    <?php if ($lastTicket && $lastData): ?>
      <div class="ticket-receipt">
        <div class="ticket-header">
          <div style="font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:var(--clr-text-lt);margin-bottom:6px;">RecettesPro — Ville de Kinshasa</div>
          <div class="ticket-number"><?= htmlspecialchars($lastTicket) ?></div>
        </div>
        <div class="ticket-row">
          <span class="ticket-row-label">👤 Agent</span>
          <span class="ticket-row-value"><?= htmlspecialchars($agentNom) ?></span>
        </div>
        <div class="ticket-row">
          <span class="ticket-row-label">🏘️ Commune</span>
          <span class="ticket-row-value"><?= htmlspecialchars($agentCommune) ?></span>
        </div>
        <div class="ticket-row">
          <span class="ticket-row-label">🛒 Marché</span>
          <span class="ticket-row-value"><?= htmlspecialchars($lastData['marche']) ?></span>
        </div>
        <div class="ticket-row">
          <span class="ticket-row-label">📅 Date</span>
          <span class="ticket-row-value"><?= $date ?></span>
        </div>
        <div class="ticket-row">
          <span class="ticket-row-label">🕒 Heure</span>
          <span class="ticket-row-value"><?= $lastData['heure'] ?></span>
        </div>
        <div class="ticket-amount">
          <div class="ticket-amount-value"><?= number_format($lastData['montant'], 0, ',', ' ') ?> FC</div>
          <div class="ticket-amount-label">Montant collecté</div>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:14px;">
        <button onclick="printTicket()" class="btn btn-outline" style="flex:1;">🖨️ Imprimer</button>
        <button onclick="resetForm()" class="btn btn-primary" style="flex:1;">➕ Nouveau ticket</button>
      </div>

      <div id="printTarget" style="display:none;">
        <pre style="font-family:monospace;font-size:12px;line-height:1.7;">
================================
     RECETTES PRO — KINSHASA
================================
Ticket : <?= $lastTicket ?>

Agent  : <?= $agentNom ?>

Commune: <?= $agentCommune ?>

Marché : <?= $lastData['marche'] ?>

Date   : <?= $date ?>

Heure  : <?= $lastData['heure'] ?>

--------------------------------
MONTANT: <?= number_format($lastData['montant'], 0, ',', ' ') ?> FC
================================
        </pre>
      </div>

    <?php else: ?>
      <div class="ticket-placeholder">
        <div class="big">🧾</div>
        <div>Le ticket généré apparaîtra ici.</div>
        <div style="font-size:.78rem;">Sélectionnez un marché et saisissez un montant.</div>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
function setMontant(v) {
  document.getElementById('montant').value = v;
  document.getElementById('montant').focus();
}
function printTicket() {
  const w = window.open('','_blank','width=340,height=480');
  w.document.write(document.getElementById('printTarget').innerHTML);
  w.document.close(); w.focus(); w.print(); w.close();
}
function resetForm() {
  document.getElementById('montant').value = '';
  document.getElementById('montant').focus();
}
</script>
</body>
</html>
