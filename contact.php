<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $country  = trim($_POST['country']  ?? '');
    $interest = trim($_POST['interest'] ?? '');
    $message  = trim($_POST['message']  ?? '');

    if (!$fullName || !$email || !$message) {
        $error = 'contactPage.errorRequired';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'contactPage.errorInvalidEmail';
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO ContactMessage (id, fullName, email, country, interest, message, isRead, createdAt)
                 VALUES (?, ?, ?, ?, ?, ?, 0, NOW(3))'
            );
            $stmt->execute([generate_id(), $fullName, $email, $country, $interest, $message]);
            $success = true;
        } catch (Exception $e) {
            $error = 'contactPage.errorSendFailed';
        }
    }
}

$errorText = [
    'contactPage.errorRequired'      => 'Full name, email, and message are required.',
    'contactPage.errorInvalidEmail'  => 'Please enter a valid email address.',
    'contactPage.errorSendFailed'    => 'Could not send your message. Please try again.',
][$error] ?? '';

$pageTitle = 'Contact Us | Tafakari Digital Hub';
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<body class="antialiased min-h-screen flex flex-col bg-slate-50 font-inter">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-6 py-20">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">

    <!-- Left: Info -->
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest mb-6" data-i18n="footer.contact">Contact Us</div>
      <h1 class="font-outfit text-4xl font-black mb-6 text-slate-900" data-i18n="contactPage.heading">Let's Start a Conversation</h1>
      <p class="text-slate-500 text-base leading-relaxed mb-10" data-i18n="contactPage.desc">
        Whether you want to contribute research, partner with our network, or simply learn more about our work in Kenya, Ethiopia, and DRC — we'd love to hear from you.
      </p>
      <div class="space-y-4">
        <div class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-slate-100">
          <span class="text-2xl">📧</span>
          <div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-400 mb-1" data-i18n="contactPage.email">Email</div>
            <div class="font-bold text-slate-800">info@tafakari.co.ke</div>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-slate-100">
          <span class="text-2xl">🌍</span>
          <div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-400 mb-1" data-i18n="contactPage.coverage">Coverage</div>
            <div class="font-bold text-slate-800">Kenya · Ethiopia · DR Congo</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="bg-white rounded-4xl border border-slate-100 shadow-sm p-10">
      <?php if ($success): ?>
        <div class="text-center py-12">
          <div class="text-5xl mb-4">✅</div>
          <h2 class="font-outfit font-bold text-2xl mb-2 text-slate-900" data-i18n="contactPage.messageSent">Message Sent!</h2>
          <p class="text-slate-500 mb-8" data-i18n="contactPage.thankYou">Thank you for reaching out. We'll get back to you soon.</p>
          <a href="/" class="btn-primary" data-i18n="contactPage.backToHome">Back to Home</a>
        </div>
      <?php else: ?>
        <h2 class="font-outfit font-bold text-2xl mb-8 text-slate-900" data-i18n="contactPage.sendMessage">Send a Message</h2>
        <?php if ($error): ?>
          <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm" data-i18n="<?= h($error) ?>"><?= h($errorText) ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-5">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2" data-i18n="contactPage.fullName">Full Name *</label>
            <input type="text" name="fullName" required value="<?= h($_POST['fullName'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:border-transparent text-sm"
                   style="--tw-ring-color:#750B25" placeholder="Your full name" data-i18n-placeholder="contactPage.fullNamePlaceholder">
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2" data-i18n="contactPage.emailAddress">Email Address *</label>
            <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:border-transparent text-sm"
                   placeholder="you@example.com">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2" data-i18n="heatmapPage.country">Country</label>
              <select name="country" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm">
                <option value="" data-i18n="contactPage.selectCountry">Select country</option>
                <?php foreach (['Kenya','Ethiopia','DR Congo','Other'] as $c): ?>
                  <option value="<?= h($c) ?>" <?= (($_POST['country'] ?? '') === $c) ? 'selected' : '' ?>><?= h($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2" data-i18n="contactPage.interest">Interest</label>
              <select name="interest" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm">
                <option value="" data-i18n="contactPage.selectInterest">Select interest</option>
                <?php foreach (['Health','Security','Policy','Media/Press','Other'] as $i): ?>
                  <option value="<?= h($i) ?>" <?= (($_POST['interest'] ?? '') === $i) ? 'selected' : '' ?>><?= h($i) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2" data-i18n="contactPage.message">Message *</label>
            <textarea name="message" required rows="5"
                      class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none text-sm resize-none"
                      placeholder="Tell us about yourself or your inquiry..." data-i18n-placeholder="contactPage.messagePlaceholder"><?= h($_POST['message'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn-primary w-full py-4 text-base" data-i18n="contactPage.sendMessageBtn">Send Message</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
