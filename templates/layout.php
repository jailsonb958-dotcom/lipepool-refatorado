<?php
use LipePool\Auth\AuthContext;
use LipePool\Security\Csrf;
use LipePool\Support\Flash;
use LipePool\Support\View;
$user = AuthContext::user();
$flashes = Flash::consume();
$title = $title ?? 'LipePool';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#063b4a">
    <meta name="description" content="Sistema LipePool para conhecer os serviços e solicitar agendamentos de manutenção de piscinas.">
    <title><?= View::escape($title) ?></title>
    <link rel="icon" href="/assets/logo-lipepool.png" type="image/png">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<a class="skip-link" href="#main">Pular para o conteúdo</a>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="/" aria-label="LipePool — início"><img src="/assets/logo-lipepool.png" alt="LipePool — manutenção de piscinas" width="140" height="40"></a>
        <nav aria-label="Navegação principal">
            <a href="/servicos">Serviços</a>
            <a href="/sobre">Sobre</a>
            <a href="/contato">Contato</a>
            <?php if ($user && $user['role'] === 'admin'): ?>
                <a href="/admin/agenda">Administração</a>
                <a href="/admin/clientes">Clientes</a>
                <a href="/admin/servicos">Gerenciar serviços</a>
            <?php elseif ($user): ?>
                <a href="/conta">Minha conta</a>
                <a href="/agendar">Agendar</a>
            <?php else: ?>
                <a href="/login">Entrar</a>
                <a class="nav-cta" href="/cadastro">Criar conta</a>
            <?php endif; ?>
        </nav>
        <?php if ($user): ?>
            <form class="logout-form" method="post" action="/sair">
                <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
                <button class="button button-quiet" type="submit">Sair</button>
            </form>
        <?php endif; ?>
    </div>
</header>
<main id="main" class="main-content">
    <div class="container">
        <?php foreach ($flashes as $flash): ?>
            <?php $kind = in_array($flash['type'] ?? '', ['success', 'error'], true) ? $flash['type'] : 'info'; ?>
            <div class="alert alert-<?= View::escape($kind) ?>" role="status"><?= View::escape($flash['message'] ?? '') ?></div>
        <?php endforeach; ?>
    </div>
    <?= $content ?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <span>© <?= date('Y') ?> LipePool</span>
        <span>Manutenção de piscinas</span>
    </div>
</footer>
</body>
</html>
