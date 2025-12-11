#!/bin/bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15 <<EOF
cd /./var/www/html
put account.html
put account_boat.html
put account_boat_availability_form.php
put account_boat_availability_update.php
put account_boat_data_form.php
put account_boat_data_update.php
put account_boat_exists.php
put account_crew.html
put account_crew_availability_form.php
put account_crew_availability_update.php
put account_crew_data_form.php
put account_crew_data_update.php
put account_crew_exists.php
put program.html
put season_update.php
cd css
put css/styles.css
chmod 770 styles.css
cd -R /Libraries
bye
EOF
