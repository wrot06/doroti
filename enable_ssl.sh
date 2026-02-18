#!/bin/bash

# Enable SSL for local development (Doroti)

echo "Generating self-signed certificate..."
mkdir -p /etc/ssl/private /etc/ssl/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/doroti.key \
    -out /etc/ssl/certs/doroti.crt \
    -subj "/C=CO/ST=Valle/L=Cali/O=Doroti/OU=Dev/CN=doroti"

echo "Enabling SSL module..."
a2enmod ssl

echo "Configuring Apache VirtualHost..."
cat <<EOF >> /etc/apache2/sites-enabled/doroti.conf

<VirtualHost *:443>
    ServerName doroti
    DocumentRoot /var/www/doroti

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/doroti.crt
    SSLCertificateKeyFile /etc/ssl/private/doroti.key

    <Directory /var/www/doroti>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/doroti_error.log
    CustomLog \${APACHE_LOG_DIR}/doroti_access.log combined
</VirtualHost>
EOF

echo "Restarting Apache..."
systemctl restart apache2

echo "Done! You can now access https://doroti"
