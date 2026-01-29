# gallery-php

Simple, single-file PHP image gallery (`gallery.php`) for serving a folder of images as a clean, navigable gallery page.

## Deploy
1. Copy `gallery.php` to your web server (any PHP-capable host).
2. Put your images in the same directory (or update the path inside `gallery.php` if it expects a subfolder).
3. Ensure the web server user can read the images.
4. Visit the directory in your browser to see the gallery.

## Sample `.htaccess`
This rewrites requests so the directory loads `gallery.php` without typing the file name.

```apache
DirectoryIndex gallery.php

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ gallery.php [L,QSA]
```
