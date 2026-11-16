# Ambiente de execução com Docker (alternativa ao XAMPP). Veja o README.
FROM php:8.2-apache

# pdo_mysql: acesso ao MySQL/MariaDB. rewrite + AllowOverride: os .htaccess do projeto valem,
# como no XAMPP (só a pasta public/ fica acessível pelo navegador).
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && sed -ri 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf \
    && sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf

# http://localhost:8090/ leva direto ao sistema (sem precisar digitar /sistema-contabil/).
RUN echo 'RedirectMatch 302 ^/$ /sistema-contabil/' > /etc/apache2/conf-available/raiz.conf && a2enconf raiz

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-contabil.ini
