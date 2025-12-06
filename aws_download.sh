#!/bin/bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15 <<EOF
cd stack/apache/htdocs/Libraries/Fleet/data
get fleet_data.csv
cd /../../Squad/data
get squad_data.csv
bye
EOF
