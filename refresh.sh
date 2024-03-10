#! /bin/bash
chmod a+w -R /var/www/html/astro-site/__cache/
cd /var/www/html/astro-site && npm run dump:data

exit 0
