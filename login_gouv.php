<?php
session_start();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom  = $_POST['nom'];
    $code = $_POST['code'];

    $fichier = fopen('data/gouverneur.csv', 'r');
    if ($fichier) {
        while (($ligne = fgetcsv($fichier)) !== false) {
            if ($ligne[0] === $nom && $ligne[1] === $code) {
                $_SESSION['gouvernorat'] = $nom;
                header("Location: gouv/gouv.php");
                exit();
            }
        }
        fclose($fichier);
    }
    $message = "Identifiants incorrects. Veuillez réessayer.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion Gouverneur — RecettesPro</title>
  <link rel="stylesheet" href="includes/rpro.css">
  <style>
    .login-deco {
      background: linear-gradient(145deg, #081c12 0%, #0c3222 50%, #0f4c35 100%);
    }
    .govr-emblem {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, var(--clr-accent), var(--clr-accent-lt));
      border-radius: var(--radius-xl);
      display: flex; align-items: center; justify-content: center;
      font-size: 2.2rem;
      margin-bottom: 28px;
      box-shadow: 0 8px 32px rgba(201,162,39,.4);
    }
    .stat-band {
      margin-top: 60px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .stat-line {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .stat-line-label { color: rgba(255,255,255,.5); font-size: .82rem; }
    .stat-line-val {
      font-family: var(--font-display);
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--clr-accent-lt);
    }
    .stat-line-bar {
      flex: 1; margin: 0 16px; height: 3px;
      background: rgba(255,255,255,.08);
      border-radius: 99px; overflow: hidden;
    }
    .stat-line-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--clr-accent), var(--clr-primary-lt));
      border-radius: 99px;
    }
    .seal {
      position: absolute;
      bottom: 40px; right: 40px;
      width: 90px; height: 90px;
      border: 2px solid rgba(201,162,39,.2);
      border-radius: 50%;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      font-family: var(--font-display);
      color: rgba(201,162,39,.4);
      font-size: .65rem;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: .06em;
    }
  </style>
</head>
<body>
<div class="login-wrap">

  <!-- Panneau décoratif -->
  <div class="login-deco">
    <div class="login-deco-inner">
      <div class="login-logo">R-<span>Pro</span></div>
      <p class="login-tagline">Vue globale sur l'ensemble des recettes de la ville de Kinshasa. Toutes les communes, en un coup d'œil.</p>

      <div style="margin-top: 44px;">
        <div class="govr-emblem">🏛️</div>
        <div class="login-role-badge" style="background:rgba(201,162,39,.15); border-color:rgba(201,162,39,.3); color:var(--clr-accent-lt);">
          <span>★</span> Accès Gouverneur — Niveau Global
        </div>
      </div>

      <div class="stat-band">
        <div class="stat-line">
          <span class="stat-line-label">Communes couvertes</span>
          <div class="stat-line-bar"><div class="stat-line-bar-fill" style="width:100%"></div></div>
          <span class="stat-line-val">21</span>
        </div>
        <div class="stat-line">
          <span class="stat-line-label">Marchés supervisés</span>
          <div class="stat-line-bar"><div class="stat-line-bar-fill" style="width:85%"></div></div>
          <span class="stat-line-val">80+</span>
        </div>
        <div class="stat-line">
          <span class="stat-line-label">Agents terrain</span>
          <div class="stat-line-bar"><div class="stat-line-bar-fill" style="width:60%"></div></div>
          <span class="stat-line-val">63+</span>
        </div>
      </div>
    </div>

    <div class="seal">
      <div style="font-size:1.4rem;">⚖️</div>
      <div>Ville de<br>Kinshasa</div>
    </div>
  </div>

  <!-- Formulaire -->
  <div class="login-form-panel">
    <div style="max-width:380px; width:100%; margin:auto;">
      <p class="login-form-sub" style="margin-bottom:4px; font-size:.8rem; letter-spacing:.1em; text-transform:uppercase; color:var(--clr-accent); font-weight:600;">Accès restreint</p>
      <h1 class="login-form-title">Tableau de Bord<br>Gouverneur</h1>
      <p class="login-form-sub">Authentification requise pour accéder à la vue globale.</p>

      <?php if ($message): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="nom">Identifiant</label>
          <input
            type="text"
            id="nom"
            name="nom"
            placeholder="Identifiant gouverneur"
            autocomplete="username"
            required
          >
        </div>
        <div class="form-group">
          <label for="code">Code d'accès</label>
          <input
            type="password"
            id="code"
            name="code"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          >
        </div>
        <button type="submit" class="btn-login" style="background:var(--clr-dark);">
          Accéder au tableau de bord →
        </button>
      </form>

      <div class="login-footer">
        <div style="display:flex;justify-content:center;gap:24px;margin-bottom:8px;">
          <a href="login_agent.php" style="color:var(--clr-text-lt);text-decoration:none;font-size:.8rem;">Espace Agent</a>
          <a href="login_bourg.php" style="color:var(--clr-text-lt);text-decoration:none;font-size:.8rem;">Espace Bourgmestre</a>
        </div>
        Powered by <strong>AmeriKin LLC, USA</strong>, 2025
      </div>
    </div>
  </div>

</div>
</body>
</html>
