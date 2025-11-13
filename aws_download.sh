#!/bin/bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15 <<EOF
cd stack/apache/htdocs/config
get boats_availability.csv
get boats_data.csv
get crews_availability.csv
get crews_data.csv
bye
EOF
