<?php
session_start();

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent = trim($_POST['agent']);
    $agentsMap = include 'includes/agents.php';

    if (isset($agentsMap[$agent])) {
        $_SESSION['agent'] = $agent;
        header('Location: agent/index.php');
        exit;
    } else {
        $erreur = "Nom d'agent invalide. Veuillez réessayer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion Agent — RecettesPro</title>
  <link rel="stylesheet" href="includes/rpro.css">
  <style>
    .login-deco { background: var(--clr-primary); }
    .deco-circles {
      position: absolute; inset: 0; pointer-events: none;
    }
    .deco-circles circle { fill: rgba(255,255,255,.04); }
    .role-icon {
      width: 64px; height: 64px;
      background: rgba(255,255,255,.1);
      border-radius: var(--radius-xl);
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      margin-bottom: 28px;
      border: 1px solid rgba(255,255,255,.15);
    }
  </style>
</head>
<body>
<div class="login-wrap">

  <!-- Panneau décoratif -->
  <div class="login-deco">
    <svg class="deco-circles" viewBox="0 0 400 600" preserveAspectRatio="xMidYMid slice">
      <circle cx="350" cy="80"  r="180"/>
      <circle cx="60"  cy="520" r="220"/>
      <circle cx="200" cy="300" r="90"/>
    </svg>
    <div class="login-deco-inner">
      <div class="login-logo">R-<span>Pro</span></div>
      <p class="login-tagline">Système de collecte et centralisation des recettes publiques des marchés de Kinshasa.</p>

      <div class="role-icon" style="margin-top:44px;">📋</div>
      <div class="login-role-badge">
        <span>●</span> Espace Agent de Terrain
      </div>

      <div class="login-deco-stats">
        <div class="login-deco-stat">
          <strong>21</strong>
          <span>Communes</span>
        </div>
        <div class="login-deco-stat">
          <strong>63+</strong>
          <span>Agents actifs</span>
        </div>
        <div class="login-deco-stat">
          <strong>∞</strong>
          <span>Tickets/jour</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Formulaire -->
  <div class="login-form-panel">
    <div style="max-width:380px; width:100%; margin:auto;">
      <p class="login-form-sub" style="margin-bottom:4px; font-size:.8rem; letter-spacing:.1em; text-transform:uppercase; color:var(--clr-primary-md); font-weight:600;">RecettesPro 1.0</p>
      <h1 class="login-form-title">Connexion Agent</h1>
      <p class="login-form-sub">Entrez votre nom pour accéder à votre espace de collecte.</p>

      <?php if ($erreur): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="agent">Nom de l'agent</label>
          <input
            type="text"
            id="agent"
            name="agent"
            placeholder="Ex : Benito, Matata…"
            autocomplete="username"
            required
            value="<?= htmlspecialchars($_POST['agent'] ?? '') ?>"
          >
        </div>
        <button type="submit" class="btn-login">Accéder à mon espace →</button>
      </form>

      <div class="login-footer">
        <div style="display:flex;justify-content:center;gap:24px;margin-bottom:8px;">
          <a href="login_bourg.php" style="color:var(--clr-text-lt);text-decoration:none;font-size:.8rem;">Espace Bourgmestre</a>
          <a href="login_gouv.php"  style="color:var(--clr-text-lt);text-decoration:none;font-size:.8rem;">Espace Gouverneur</a>
        </div>
        Powered by <strong>AmeriKin LLC, USA</strong>, 2025
      </div>
    </div>
  </div>

</div>
</body>
</html>
