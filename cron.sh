#!/bin/bash
#cron
# 0 3 * * * /var/www/html/crm/report4/cron.sh
 #set location before setup
/bin/php /var/www/html/crm/report4/report4.php
cd  /var/www/html/crm/report4/ ;
/bin/php report4.php ;
echo "sucess" > /tmp/report4.tmp ;

