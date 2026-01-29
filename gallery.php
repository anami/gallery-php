<?php
session_start();

// Load .env if it exists
$env_path = __DIR__ . '/.env';
$auth_required = false;
$env_password = null;

if (file_exists($env_path)) {
  $auth_required = true;
  $env_lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($env_lines as $line) {
    if (strpos($line, '=') !== false) {
      list($key, $val) = explode('=', $line, 2);
      if (trim($key) === 'PASSWORD') {
        $env_password = trim($val);
      }
    }
  }
}

// Handle login if auth is required
if ($auth_required && !isset($_SESSION['logged_in'])) {
  if (isset($_POST['password'])) {
    if ($_POST['password'] === $env_password) {
      $_SESSION['logged_in'] = true;
    } else {
      $error = "Incorrect password!";
    }
  }

  if (!isset($_SESSION['logged_in'])): ?>
    <!DOCTYPE html>
    <html>

    <head>
      <title>Login</title>
      <style>
        body {
          font-family: sans-serif;
          background: #222;
          color: white;
          display: flex;
          height: 100vh;
          justify-content: center;
          align-items: center;
          margin: 0;
        }

        form {
          background: #333;
          padding: 2em;
          border-radius: 10px;
          box-shadow: 0 0 10px black;
          width: 100%;
          max-width: 300px;
        }

        input[type="password"] {
          padding: 0.5em;
          font-size: 1em;
          width: 100%;
          box-sizing: border-box;
        }

        button {
          padding: 0.5em 1em;
          margin-top: 1em;
          font-size: 1em;
          width: 100%;
        }

        .error {
          color: red;
          margin-top: 1em;
        }
      </style>
    </head>

    <body>
      <form method="POST">
        <h2>🔐 Enter Password</h2>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
        <?php if (isset($error))
          echo "<div class='error'>$error</div>"; ?>
      </form>
    </body>

    </html>
    <?php exit; endif;
} ?>
<?php
$baseDir = __DIR__;
$path = isset($_GET['path']) ? $_GET['path'] : '';
$fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $path);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
  die("Invalid path.");
}

$items = scandir($fullPath);
$images = [];
$folders = [];

foreach ($items as $item) {
  if ($item === '.' || $item === '..')
    continue;
  $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
  $relPath = ltrim($path . '/' . $item, '/');

  if (is_dir($itemPath)) {
    $folders[] = $relPath;
  } elseif (preg_match('/\.(jpg|jpeg|png|gif)$/i', $item)) {
    $images[] = $relPath;
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Photo Gallery</title>
  <style>
    :root {
      --thumb-gap: 10px;
      --thumb-size: 150px;
      --lightbox-padding: 20px;
      --bg-color: #f0f0f0;
      --text-color: #111;
      --thumb-bg: #ccc;
      --thumb-pattern: #ddd;
    }

    [data-theme="dark"] {
      --bg-color: #121212;
      --text-color: #eee;
      --thumb-bg: #444;
      --thumb-pattern: #555;
    }

    @media (max-width: 600px) {
      :root {
        --thumb-gap: 6px;
        --thumb-size: 110px;
        --lightbox-padding: 12px;
      }
    }


    body {
      font-family: sans-serif;
      margin: 0;
      padding: 20px;
      background: var(--bg-color);
      color: var(--text-color);
    }

    a {
      text-decoration: none;
      color: #333;
    }

    .grid {
      display: grid;
      gap: var(--thumb-gap);
      grid-template-columns: repeat(auto-fit, minmax(var(--thumb-size), 1fr));
    }


    .grid img {
      width: 150px;
      height: auto;
      cursor: pointer;
      border-radius: 5px;
    }

    .thumb-wrapper {
      width: 100%;
      aspect-ratio: 1 / 1;
      background-color: var(--thumb-bg);
      background-image:
        linear-gradient(45deg, var(--thumb-pattern) 25%, transparent 25%),
        linear-gradient(-45deg, var(--thumb-pattern) 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, var(--thumb-pattern) 75%),
        linear-gradient(-45deg, transparent 75%, var(--thumb-pattern) 75%);
      background-size: 20px 20px;
      background-position: 0 0, 0 10px, 10px -10px, -10px 0px;

      border-radius: 5px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }



    .thumb-wrapper img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      cursor: pointer;
    }


    .thumb-wrapper img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      cursor: pointer;
    }

    .folder {
      margin-bottom: 15px;
    }

    .folder a {
      font-weight: bold;
    }

    .lightbox {
      display: none;
      align-items: center;
      justify-content: center;
      flex-direction: row;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      padding: var(--lightbox-padding);
      background: rgba(0, 0, 0, 0.8);
      z-index: 9999;
      box-sizing: border-box;
    }

    .lightbox {
      animation: fadeIn 0.2s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }


    .lightbox img {
      width: 100%;
      height: 100%;
      max-width: 90vw;
      max-height: 90vh;
      object-fit: contain;
      border-radius: 10px;
    }


    .lightbox-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      font-size: 50px;
      color: white;
      cursor: pointer;
      user-select: none;
      font-size: 40px;
    }

    @media (max-width: 600px) {
      .lightbox-nav {
        font-size: 30px;
      }

      .lightbox-close {
        font-size: 30px;
        right: 20px;
      }
    }

    .lightbox-close {
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 40px;
      color: white;
      cursor: pointer;
      z-index: 10003;
    }

    .lightbox-prev,
    .lightbox-next {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      font-size: 40px;
      color: white;
      cursor: pointer;
      user-select: none;
      z-index: 10000;
    }

    .lightbox-prev {
      left: 20px;
    }

    .lightbox-next {
      right: 20px;
    }

    .lightbox-fullscreen {
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 30px;
      color: white;
      cursor: pointer;
      z-index: 10000;
    }

    .top-controls {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10001;
      display: flex;
      gap: 10px;
    }

    .icon-btn {
      border: none;
      background: transparent;
      font-size: 24px;
      cursor: pointer;
      color: inherit;
    }

    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 10002;
      align-items: center;
      justify-content: center;
      padding: 20px;
      box-sizing: border-box;
    }

    .modal-content {
      background: var(--bg-color);
      color: var(--text-color);
      border-radius: 10px;
      padding: 16px 18px;
      max-width: 420px;
      width: 100%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .modal-content h3 {
      margin: 0 0 10px 0;
      font-size: 18px;
    }

    .shortcut-list {
      margin: 0;
      padding-left: 18px;
    }

    .shortcut-list li {
      margin: 6px 0;
    }

    kbd {
      display: inline-block;
      padding: 2px 6px;
      border: 1px solid rgba(0, 0, 0, 0.25);
      border-bottom-width: 2px;
      border-radius: 4px;
      background: #f7f7f7;
      color: #111;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size: 0.9em;
      box-shadow: 0 1px 0 rgba(0, 0, 0, 0.15);
    }

    [data-theme="dark"] kbd {
      background: #2a2a2a;
      color: #f0f0f0;
      border-color: rgba(255, 255, 255, 0.2);
      box-shadow: 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    .modal-close {
      border: none;
      background: transparent;
      font-size: 22px;
      cursor: pointer;
      float: right;
      color: inherit;
    }


    @media (max-width: 600px) {

      .lightbox-close,
      .lightbox-prev,
      .lightbox-next {
        font-size: 30px;
      }
    }


    .lazy {
      filter: blur(10px);
      transition: filter 0.4s ease, opacity 0.4s ease;
      opacity: 0.6;
    }

    .lazy.loaded {
      filter: blur(0);
      opacity: 1;
    }
  </style>
</head>

<body>
  <h1>📁 Gallery: /<?= htmlspecialchars($path) ?></h1>

  <?php if ($path): ?>
    <p><a href="?path=<?= urlencode(dirname($path)) ?>">⬅️ Back to parent</a></p>
  <?php endif; ?>

  <div class="folder">
    <?php foreach ($folders as $f): ?>
      <div>📁 <a href="?path=<?= urlencode($f) ?>"><?= basename($f) ?></a></div>
    <?php endforeach; ?>
  </div>

  <?php if (count($images) > 0): ?>
    <p style="margin: 0 0 12px 0; opacity: 0.8;">Click a thumbnail to view the full image.</p>
  <?php endif; ?>

  <div class="grid">
    <?php foreach ($images as $i): ?>
      <div class="thumb-wrapper">
        <img src="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='150' height='150'></svg>"
          data-src="<?= htmlspecialchars($i) ?>" data-index="<?= array_search($i, $images) ?>" class="lazy">

      </div>
      <!-- <img src="<?= htmlspecialchars($i) ?>" data-index="<?= array_search($i, $images) ?>" data-src="<?= htmlspecialchars($i) ?>"> -->
    <?php endforeach; ?>
  </div>

  <div class="lightbox" id="lightbox">
    <span class="lightbox-close" onclick="hideLightbox()">&times;</span>
    <span class="lightbox-fullscreen" onclick="toggleFullscreen()">⛶</span>
    <span class="lightbox-nav lightbox-prev" onclick="prevImage()">&larr;</span>
    <img id="lightbox-img">
    <span class="lightbox-nav lightbox-next" onclick="nextImage()">&rarr;</span>
  </div>
  <div class="top-controls">
    <button id="shortcuts-toggle" class="icon-btn" aria-label="Keyboard shortcuts">⌨️</button>
    <button id="theme-toggle" class="icon-btn" aria-label="Toggle theme">🌓</button>
  </div>

  <div class="modal" id="shortcuts-modal" role="dialog" aria-modal="true" aria-labelledby="shortcuts-title">
    <div class="modal-content">
      <button class="modal-close" id="shortcuts-close" aria-label="Close">&times;</button>
      <h3 id="shortcuts-title">Keyboard shortcuts</h3>
      <ul class="shortcut-list">
        <li><kbd>←</kbd> Previous image (when lightbox open)</li>
        <li><kbd>→</kbd> Next image (when lightbox open)</li>
        <li><kbd>Esc</kbd> Close lightbox</li>
      </ul>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const lazyImages = document.querySelectorAll('img.lazy');

      if ('IntersectionObserver' in window) {
        let observer = new IntersectionObserver((entries, obs) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              let img = entry.target;
              img.src = img.dataset.src;
              img.onload = () => img.classList.add('loaded');
              observer.unobserve(img);
            }
          });
        });

        lazyImages.forEach(img => observer.observe(img));
      } else {
        // Fallback: just load them all at once
        lazyImages.forEach(img => {
          img.src = img.dataset.src;
          img.onload = () => img.classList.add('loaded');
        });
      }
    });
  </script>

  <script>
    const images = Array.from(document.querySelectorAll('.grid img'));
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    let currentIndex = -1;

    function showLightbox(index) {
      if (index < 0 || index >= images.length) return;
      currentIndex = index;
      lightboxImg.src = images[currentIndex].dataset.src;
      lightbox.style.display = 'flex';

      // preload next image
      if (images[index + 1]) {
        const preload = new Image();
        preload.src = images[index + 1].dataset.src;
      }

    }

    function hideLightbox() {
      lightbox.style.display = 'none';
    }

    function prevImage() {
      if (currentIndex > 0) showLightbox(currentIndex - 1);
    }

    function nextImage() {
      if (currentIndex < images.length - 1) showLightbox(currentIndex + 1);
    }

    images.forEach((img, i) => {
      img.addEventListener('click', () => showLightbox(i));
    });

    document.addEventListener('keydown', function (e) {
      if (lightbox.style.display === 'flex') {
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'Escape') hideLightbox();
      }
    });
  </script>
  <script>
    // swipe support
    let startX = 0;
    lightbox.addEventListener('touchstart', function (e) {
      startX = e.touches[0].clientX;
    });

    lightbox.addEventListener('touchend', function (e) {
      let endX = e.changedTouches[0].clientX;
      let diff = startX - endX;

      if (Math.abs(diff) > 50) {
        if (diff > 0) {
          nextImage(); // swipe left
        } else {
          prevImage(); // swipe right
        }
      }
    });
  </script>
  <script>
    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        lightbox.requestFullscreen().catch(err => {
          alert("Fullscreen failed: " + err.message);
        });
      } else {
        document.exitFullscreen();
      }
    }
  </script>
  <script>
    const themeToggle = document.getElementById('theme-toggle');
    const root = document.documentElement;
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme) root.setAttribute('data-theme', savedTheme);

    themeToggle.addEventListener('click', () => {
      let current = root.getAttribute('data-theme');
      let newTheme = (current === 'dark') ? '' : 'dark';
      root.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
    });
  </script>
  <script>
    const shortcutsToggle = document.getElementById('shortcuts-toggle');
    const shortcutsModal = document.getElementById('shortcuts-modal');
    const shortcutsClose = document.getElementById('shortcuts-close');

    function openShortcuts() {
      shortcutsModal.style.display = 'flex';
    }

    function closeShortcuts() {
      shortcutsModal.style.display = 'none';
    }

    shortcutsToggle.addEventListener('click', openShortcuts);
    shortcutsClose.addEventListener('click', closeShortcuts);
    shortcutsModal.addEventListener('click', (e) => {
      if (e.target === shortcutsModal) closeShortcuts();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && shortcutsModal.style.display === 'flex') {
        closeShortcuts();
      }
    });
  </script>



</body>

</html>
