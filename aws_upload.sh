#!/bin/bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15 <<EOF
cd ../../var/www/html
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
put arrays.php
put database.php
put names.php
put program.html
cd css
put css/styles.css
chmod 664 styles.css
cd /Libraries/Fleet/data
put fleet_data.csv
chmod 664 fleet_data.csv
cd /../../Squad/data
put squad_data.csv
chmod 664 squad_data.csv
chmod 664 config.php
bye
EOF
