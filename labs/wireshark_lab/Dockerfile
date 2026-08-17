# Base this image on our custom root image
FROM mlabs-base:latest

# Install Apache, PHP, and OpenSSL
RUN apt-get update && \
    apt-get install -y apache2 php libapache2-mod-php openssl && \
    apt-get clean

# Enable Apache SSL module and activate the default SSL site
RUN a2enmod ssl && a2ensite default-ssl

# Generate a self-signed certificate for the HTTPS part of the lab
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
    -out /etc/ssl/certs/ssl-cert-snakeoil.pem \
    -subj "/C=US/ST=State/L=City/O=Organization/OU=Unit/CN=localhost"

# Remove the default Apache "It works!" page
RUN rm /var/www/html/index.html

# Expose HTTP and HTTPS ports
EXPOSE 80 443

# Start the Apache server in the foreground
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
