<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$contactEmail = trim((string)envValue('CONTACT_EMAIL', ''));
if ($contactEmail === '') {
    $contactEmail = 'support@libris.local';
}

$mailtoSubject = rawurlencode(t('contact.mail_subject'));
$mailtoHref = 'mailto:' . $contactEmail . '?subject=' . $mailtoSubject;

$pageStyles = ['assets/css/pages/about.css', 'assets/css/pages/contact.css'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="about-page contact-page">
    <section class="about-hero contact-hero" aria-labelledby="contact-heading">
        <div class="about-hero-visual contact-hero-visual" aria-hidden="true">
            <svg class="contact-mail-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2.5" y="5" width="19" height="14" rx="2.5"></rect>
                <path d="M3 7l8.2 5.5a2 2 0 0 0 2.2 0L21 7"></path>
            </svg>
        </div>
        <div>
            <p class="about-hero-eyebrow"><?= htmlspecialchars(t('contact.eyebrow')) ?></p>
            <h1 id="contact-heading"><?= htmlspecialchars(t('contact.title')) ?></h1>
            <div class="about-hero-lead">
                <blockquote><?= htmlspecialchars(t('contact.lead')) ?></blockquote>
            </div>
        </div>
    </section>

    <section class="contact-channels" aria-label="<?= htmlspecialchars(t('contact.channels_aria')) ?>">
        <article class="contact-channel">
            <h3><?= htmlspecialchars(t('contact.card_email_title')) ?></h3>
            <p><?= htmlspecialchars(t('contact.card_email_text')) ?></p>
            <span class="contact-email-value"><?= htmlspecialchars($contactEmail) ?></span>
            <a class="contact-mailto" href="<?= htmlspecialchars($mailtoHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('contact.mail_button')) ?></a>
        </article>
        <article class="contact-channel">
            <h3><?= htmlspecialchars(t('contact.card_response_title')) ?></h3>
            <p><?= htmlspecialchars(t('contact.card_response_text')) ?></p>
        </article>
        <article class="contact-channel">
            <h3><?= htmlspecialchars(t('contact.card_scope_title')) ?></h3>
            <p><?= htmlspecialchars(t('contact.card_scope_text')) ?></p>
        </article>
    </section>

    <section class="contact-prepare" aria-labelledby="prepare-heading">
        <h2 id="prepare-heading"><?= htmlspecialchars(t('contact.section_prepare')) ?></h2>
        <ol>
            <li><?= htmlspecialchars(t('contact.prepare_1')) ?></li>
            <li><?= htmlspecialchars(t('contact.prepare_2')) ?></li>
            <li><?= htmlspecialchars(t('contact.prepare_3')) ?></li>
            <li><?= htmlspecialchars(t('contact.prepare_4')) ?></li>
        </ol>
    </section>

    <section class="contact-topics" aria-labelledby="topics-heading">
        <h2 id="topics-heading"><?= htmlspecialchars(t('contact.section_topics')) ?></h2>
        <div class="contact-topics-grid">
            <article class="contact-topic">
                <h4><?= htmlspecialchars(t('contact.topic_catalog_title')) ?></h4>
                <p><?= htmlspecialchars(t('contact.topic_catalog_text')) ?></p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= htmlspecialchars(sskUrl('home')) ?>"><?= htmlspecialchars(t('footer.link_catalog')) ?> →</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(sskUrl('sign_in')) ?>"><?= htmlspecialchars(t('footer.link_login')) ?> →</a>
                <?php endif; ?>
            </article>
            <article class="contact-topic">
                <h4><?= htmlspecialchars(t('contact.topic_account_title')) ?></h4>
                <p><?= htmlspecialchars(t('contact.topic_account_text')) ?></p>
                <a href="<?= htmlspecialchars(sskUrl('sign_in')) ?>"><?= htmlspecialchars(t('contact.topic_account_link')) ?> →</a>
            </article>
            <article class="contact-topic">
                <h4><?= htmlspecialchars(t('contact.topic_about_title')) ?></h4>
                <p><?= htmlspecialchars(t('contact.topic_about_text')) ?></p>
                <a href="<?= htmlspecialchars(sskUrl('about')) ?>"><?= htmlspecialchars(t('footer.link_about')) ?> →</a>
            </article>
            <article class="contact-topic">
                <h4><?= htmlspecialchars(t('contact.topic_trust_title')) ?></h4>
                <p><?= htmlspecialchars(t('contact.topic_trust_text')) ?></p>
                <span style="font-size:0.88rem;font-weight:600;color:var(--muted-color);"><?= htmlspecialchars(t('contact.topic_trust_hint')) ?></span>
            </article>
        </div>
    </section>

    <section class="about-cta" aria-label="<?= htmlspecialchars(t('contact.cta_aria')) ?>">
        <p><?= htmlspecialchars(t('contact.cta_title')) ?></p>
        <div class="about-cta-actions">
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars(sskUrl('home')) ?>"><?= htmlspecialchars(t('about.cta_browse')) ?></a>
                <a class="btn btn-outline" href="<?= htmlspecialchars(sskUrl('about')) ?>"><?= htmlspecialchars(t('footer.link_about')) ?></a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars(sskUrl('sign_up')) ?>"><?= htmlspecialchars(t('about.cta_create')) ?></a>
                <a class="btn btn-outline" href="<?= htmlspecialchars(sskUrl('sign_in')) ?>"><?= htmlspecialchars(t('about.cta_signin')) ?></a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
