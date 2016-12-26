<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-s
RewriteRule ^(.*)$ api.php?rquest=$1 [QSA,NC,L]

RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^(.*)$ api.php [QSA,NC,L]

RewriteCond %{REQUEST_FILENAME} -s
RewriteRule ^(.*)$ api.php [QSA,NC,L] 
</IfModule>