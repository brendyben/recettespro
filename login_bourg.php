<?php
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = strtoupper(trim($_POST['matricule']));
    $nom = ucfirst(strtolower(trim($_POST['nom'])));

    $found = false;
    if (($handle = fopen(__DIR__ . '/data/bourgmestres.csv', 'r')) !== false) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            if ($data[0] === $matricule && $data[1] === $nom) {
                $_SESSION['bourg_matricule'] = $data[0];
                $_SESSION['bourg_nom']       = $data[1];
                $_SESSION['bourg_commune']   = $data[2];
                $found = true;
                break;
            }
        }
        fclose($handle);
    }

    if ($found) {
        header("Location: bourg/bourg.php");
        exit;
    } else {
        $error = "Identifiants incorrects. Veuillez réessayer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion Bourgmestre — RecettesPro</title>
  <link rel="stylesheet" href="includes/rpro.css">
  <style>
    .login-deco {
      background: linear-gradient(160deg, #0a2a1c 0%, #0f4c35 60%, #197d4b 100%);
    }
    .role-icon {
      width: 64px; height: 64px;
      background: rgba(255,255,255,.1);
      border-radius: var(--radius-xl);
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      margin-bottom: 28px;
      border: 1px solid rgba(255,255,255,.15);
    }
    .commune-grid {
      display: grid; grid-template-columns: repeat(3,1fr);
      gap: 8px; margin-top: 48px;
    }
    .commune-pill {
      background: rgba(255,255,255,.07);
      border-radius: 6px;
      padding: 5px 8px;
      font-size: .68rem;
      color: rgba(255,255,255,.55);
      text-align: center;
      border: 1px solid rgba(255,255,255,.08);
    }
  </style>
</head>
<body>
<div class="login-wrap">

  <!-- Panneau décoratif -->
  <div class="login-deco">
    <div class="login-deco-inner">
      <div class="login-logo">R-<span>Pro</span></div>
      <p class="login-tagline">Pilotage des recettes de votre commune en temps réel, depuis n'importe quel appareil.</p>

      <div style="margin-top:44px;">
        <div class="role-icon">🏛️</div>
        <div class="login-role-badge">
          <span>●</span> Espace Bourgmestre
        </div>
      </div>

      <div class="commune-grid">
        <?php foreach(['Ngaliema','Selembao','Barumbu','Bumbu','Kalamu','Kasa-Vubu',
                        'Kinshasa','Kintambo','Lemba','Limete','Lingwala','Makala',
                        'Masina','Matete','Ngaba','N\'Djili','Gombe','Maluku','Nsele','Mont Ngafula','Kitambo'] as $c): ?>
          <div class="commune-pill"><?= $c ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Formulaire -->
  <div class="login-form-panel">
    <div style="max-width:380px; width:100%; margin:auto;">
      <p class="login-form-sub" style="margin-bottom:4px; font-size:.8rem; letter-spacing:.1em; text-transform:uppercase; color:var(--clr-primary-md); font-weight:600;">RecettesPro 1.0</p>
      <h1 class="login-form-title">Connexion Bourgmestre</h1>
      <p class="login-form-sub">Accédez au tableau de bord de votre commune.</p>

      <?php if ($error): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="matricule">Matricule</label>
          <input
            type="text"
            id="matricule"
            name="matricule"
            placeholder="Ex : BRG-001"
            required
            value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>"
            style="text-transform:uppercase;"
          >
        </div>
        <div class="form-group">
          <label for="nom">Nom</label>
          <input
            type="text"
            id="nom"
            name="nom"
            placeholder="Votre nom complet"
            required
            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
          >
        </div>
        <button type="submit" class="btn-login">Accéder à mon tableau de bord →</button>
      </form>

      <div class="login-footer">
        <div style="display:flex;justify-content:center;gap:24px;margin-bottom:8px;">
          <a href="login_agent.php" style="color:var(--clr-text-lt);text-decoration:none;font-size:.8rem;">Espace Agent</a>
          <a href="login_gouv.php"  style="color:var(--clr-text-lt);text-decoration:none;font-size:.8rem;">Espace Gouverneur</a>
        </div>
        Powered by <strong>AmeriKin LLC, USA</strong>, 2025
      </div>
    </div>
  </div>

</div>
</body>
</html>
