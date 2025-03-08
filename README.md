## About SHS-Client

ShipSharkLtd is a drop shipping and warehouse management system.

## Setting up the SHS-Client

The ShipSharkLtd interface can be installed using any automation tool such as Docker, Kubernetes, but this guide assumes that you will be doing the setup manually.

Firstly, a bare bones Linux server is required to do the initial setup, preferably running Debian, but feel free to use whichever flavour of Linux you want. Recommended server should have at least **4 vCPUs**, **16GB RAM** and **30GB SSD** to run efficiently. If you are using AWS, then the **t3.medium** is the recommended starting server specifications.

## Provisioning the server

Next, you will need to provision the server with the relevant software required to run the app, these software includes: MariaDB, NGINX, PHP7.4, Laravel 8.x, Certbot, just to name a few. Below you will find step-by-step instructions on how to install and configure each required software in detail.

### Initial setup

Run the following commands to update and upgrade outdated packages, as well as install a few required packages for our application.

```bash
sudo apt -y update && sudo apt -y upgrade
sudo apt -y install apt-transport-https lsb-release ca-certificates wget git gnupg2
```

### Adding your PHP Source:

You will receive a warning after running the first command, but you can ignore and proceed for now. If you wish, you can read the warning and use the recommended approach, but either will work for now until deprecated.

```bash
sudo wget -q https://packages.sury.org/php/apt.gpg -O- | sudo apt-key add -
sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt -y update
```

### Installing PHP 7.4

```bash
sudo apt -y install php7.4-cli php7.4-common php7.4-curl php7.4-mbstring php7.4-mysql php7.4-xml php7.4-readline php7.4-fpm php7.4-gd php7.4-opcache php7.4-json php7.4-xml php7.4-zip php7.4-soap php7.4-bcmath
```

### Modify PHP-FPM Settings

Run the command below to modify the respective settings for the PHP setup.

```bash
sudo sed -i "s/memory_limit = .*/memory_limit = 1G/" /etc/php/7.4/fpm/php.ini
sudo sed -i "s/upload_max_filesize = .*/upload_max_filesize = 128M/" /etc/php/7.4/fpm/php.ini
sudo sed -i "s/zlib.output_compression = .*/zlib.output_compression = on/" /etc/php/7.4/fpm/php.ini
sudo sed -i "s/max_execution_time = .*/max_execution_time = 18000/" /etc/php/7.4/fpm/php.ini
```

Once you have made the necessary modifications to the PHP settings, make a copy of the **www.conf**, which we will use to manage the FPM pool configurations. Make a copy of the existing file:

```bash
sudo mv /etc/php/7.4/fpm/pool.d/www.conf /etc/php/7.4/fpm/pool.d/www.conf.org
```

Open the **www.conf** file and add the follwoing contents:

```bash
[www]
user = www-data
group = www-data
listen = /run/php/php7.4-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0666
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 10s
pm.max_requests = 200
chdir = /
```

Save the file and then restart php-pfm:

```bash
sudo systemctl restart php7.4-fpm
```

### Installing and configuring NGINX

Run the following command to install NGINX and then create a new site configuration file:

```bash
sudo apt -y install nginx
```

Now, create the site config file:

```bash
sudo nano /etc/nginx/sites-available/example.com
```

and now, add the follwoing contents. Be sure to update the **server_name**, **root** and and PHP settings to match your environment:

```bash
server {
  server_name example.com;
  listen 80;
  root /var/www/html/example.com;
  access_log /var/log/nginx/access.log;
  error_log /var/log/nginx/error.log;
  index index.php;

  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }

  location ~* \.(jpg|jpeg|gif|css|png|js|ico|html)$ {
    access_log off;
    expires max;
  }

  location ~ /\.ht {
    deny  all;
  }

  location ~ \.php$ {
    fastcgi_index index.php;
    fastcgi_keep_conn on;
    include /etc/nginx/fastcgi_params;
    fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }
}
```

Next, create a symbolic link to your new site,

```bash
sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/example.com
```

Remove the default site configuration file

```bash
sudo rm /etc/nginx/sites-enabled/default
```

Test the NGINX configuration file and restart the server to refresh the settings:

```bash
sudo nginx -t
sudo systemctl restart nginx
```

## Installing Composer

```bash
sudo curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## Installing Certbot for TLS

Visit the following URL: [how-to-install-certbot-on-debian-10](https://www.linode.com/docs/guides/how-to-install-certbot-on-debian-10/)

## Installing contab and scheduling jobs

Run the following command to install crontab if missing from your distro:

```bash
sudo apt-get install cron
```

Open the crontab interface:

```bash
sudo crontab -e
```

and choose the **nano** editor, which should be option [1]. Next, add the following line to the newly installed crontab:

```bash
# Custom cronjobs for Makai app
* * * * * cd /var/www/example.com && php artisan schedule:run >> /dev/null 2>&1
```

## Installing the web application

Once you are through setting up the server, it's now time to deploy the code from the repository and install the application. Create a new directory:

```bash
sudo mkdir -p /var/www/html/example.com
```

Navigate to the directory and git clone the repository in the root:

```bash
cd /var/www/html/example.com && sudo git clone git@github.com:<username>/nexara-scraper-v2.git
```

### Add the required configuration files

You need to first rename the `.env.example` file to `.env` and add the keys for the Mailer, which uses SendGrid, Firebase and Stripe.

### Install the required project dependencies

Navigate to the project directory and install the dependencies:

```bash
cd /var/www/html/example.com && sudo composer install
```

### Set permissions

Finally, set the correct permissions for the project

```bash
sudo chown www-data: -R /var/www/html/example.com
```

## For Help

Reach out to me at **kareypowell@gmail.com**
