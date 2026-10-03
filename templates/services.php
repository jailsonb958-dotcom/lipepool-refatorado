<?php use LipePool\Support\View; ?>
<section class="page-heading container">
    <p class="eyebrow">LipePool</p>
    <h1>Serviços</h1>
    <p class="lead">Consulte os serviços disponíveis e solicite um horário.</p>
</section>
<section class="container section">
    <?php if (!$services): ?>
        <div class="empty-state"><h2>Nenhum serviço disponível no momento</h2><p>Entre em contato com a equipe para obter mais informações.</p></div>
    <?php else: ?>
        <div class="service-grid">
            <?php foreach ($services as $service): ?>
                <article class="service-card">
                    <span class="feature-icon">LP</span>
                    <h2><?= View::escape($service['name']) ?></h2>
                    <?php if (!empty($service['description'])): ?><p><?= View::escape($service['description']) ?></p><?php endif; ?>
                    <?php if (!empty($service['duration_minutes'])): ?><p class="muted">Duração estimada: <?= (int) $service['duration_minutes'] ?> min</p><?php endif; ?>
                    <a class="text-link" href="/agendar">Solicitar horário <span aria-hidden="true">→</span></a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
