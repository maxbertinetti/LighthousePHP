---
title: Installation
hide: navigation
---

# Installation

To work with LighthousePHP you need:

- PHP
- A database
- Mailpit (for local mail debugging)
- FrankenPHP (as a local server instead of the standard PHP server — optional — and to compile the production executable)
- An editor
- A REST client (optional, for API debugging)

The following instructions are for Linux systems based on Debian or Ubuntu and derivatives. The same instructions apply to Ubuntu or Debian and derivatives running on Windows WSL 2. 

I personally use Linux Mint, PHP 8.5, PostgreSQL, VSCode with Intelephense and Bruno Rest Client.

---

<details>
  <summary>Why PostgreSQL, Bruno etc. and not ... ? (put your preferred tool here)</summary>

  <h3>You can use what you want!</h3>
  
  There's no law telling you what tools to use to build websites or apps!<br>
  And that's expecially true if you are already an experienced programmer.<br>
  Use whatever you prefer and be happy with it.<br>
  In my experience, beginners or developers coming from other languages often waste a lot of time figuring out why their program doesn't work (e.g. nginx or Apache misconfigurations).
  On top of that, once the app is finished, they need to deploy it to production — sometimes with a completely different setup.
  Any tool I list can be swapped for something else (well, maybe not PHP and LighthousePHP!), but if you stick with this setup for now, once you have a bit more experience you can decide for yourself what you like and what you want to change.
  <br>
</details>

## [PHP 8.5](https://www.php.net/releases/8.5/en.php)

PHP 8.5 is not available in the default Ubuntu or Debian repositories. Use the PPA maintained by [Ondřej Surý](https://launchpad.net/~ondrej/+archive/ubuntu/php), which is the de-facto standard source for PHP packages on Debian/Ubuntu-based systems.

### Ubuntu / Mint

```bash
sudo apt install software-properties-common ca-certificates lsb-release -y
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
# Press Enter when prompted
sudo apt update
```

### Debian

```bash
sudo apt-get update
sudo apt-get install -y lsb-release ca-certificates curl

# Download and install the signing key
sudo curl -sSLo /tmp/debsuryorg-archive-keyring.deb \
  https://packages.sury.org/debsuryorg-archive-keyring.deb
sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb

# Add the repository
sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] \
  https://packages.sury.org/php/ $(lsb_release -sc) main" \
  > /etc/apt/sources.list.d/php.list'

sudo apt-get update
```

### Install PHP 8.5

Once the repository is added, install the CLI and the most common extensions:

```bash
sudo apt install php8.5-cli php8.5-common \
  php8.5-{bcmath,bz2,curl,gd,gmp,intl,mbstring,readline,xml,zip}
```

Verify the installation:

```bash
php -v
```

---

## [PostgreSQL](https://www.postgresql.org/)

### Install PostgreSQL

The official [PostgreSQL APT repository](https://wiki.postgresql.org/wiki/Apt) provides up-to-date packages for all supported Debian and Ubuntu releases.

```bash
# Install prerequisites
sudo apt install -y curl ca-certificates

# Add the PostgreSQL signing key
sudo install -d /usr/share/postgresql-common/pgdg
sudo curl -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc --fail \
  https://www.postgresql.org/media/keys/ACCC4CF8.asc

# Add the repository
sudo sh -c 'echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] \
  https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
  > /etc/apt/sources.list.d/pgdg.list'

sudo apt update
sudo apt install -y postgresql
```

Verify the service is running:

```bash
sudo systemctl status postgresql
```

### Create a superuser matching your Linux username

For local development it is convenient to have a PostgreSQL superuser with the same name as your Linux user, so you can connect without specifying credentials.

```bash
# Open the psql shell as the postgres system user
sudo -u postgres psql
```

Inside psql, replace `yourusername` with your actual Linux username:

```sql
CREATE USER yourusername WITH SUPERUSER;
\q
```

You can now connect to PostgreSQL simply by running `psql` from your terminal.

### [pgAdmin](https://www.pgadmin.org/)

pgAdmin 4 is the official GUI administration tool for PostgreSQL.

```bash
# Add the pgAdmin signing key
curl -fsS https://www.pgadmin.org/static/packages_pgadmin_org.pub \
  | sudo gpg --dearmor -o /usr/share/keyrings/packages-pgadmin-org.gpg

# Add the pgAdmin repository
sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/packages-pgadmin-org.gpg] \
  https://ftp.postgresql.org/pub/pgadmin/pgadmin4/apt/$(lsb_release -cs) pgadmin4 main" \
  > /etc/apt/sources.list.d/pgadmin4.list'

sudo apt update

# Install the desktop version
sudo apt install pgadmin4-desktop
```

---

## [Mailpit](https://mailpit.axllent.org/)

Install Mailpit using the official install script:

```bash
sudo sh < <(curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh)
```

Once installed, Mailpit listens for SMTP on port `1025` and exposes a web UI at [http://localhost:8025](http://localhost:8025).

---

## [FrankenPHP](https://frankenphp.dev/)

Use the standalone executable rather than the install script, since the script creates a system service — for local development the executable alone is sufficient.

Download the executable from the [FrankenPHP Releases page](https://github.com/php/frankenphp/releases). Choose the build that matches your system. For Linux x86_64:

```bash
curl -Lo frankenphp \
  https://github.com/php/frankenphp/releases/download/v1.12.1/frankenphp-linux-x86_64
chmod +x frankenphp
sudo mv frankenphp /usr/local/bin/
```

Verify the installation:

```bash
frankenphp version
```

---

## [VSCode](https://code.visualstudio.com/)

Download the `.deb` package for Linux from the [official VS Code download page](https://code.visualstudio.com/sha/download?build=stable&os=linux-deb-x64), then install it:

```bash
sudo apt install ./code_*.deb
```

For PHP IntelliSense, install the [Intelephense](https://intelephense.com/) extension. It is available for free or with a paid licence for advanced features.

---

## [Bruno](https://www.usebruno.com/)

Bruno is an open-source REST client. Secure & Local. 

Install it on Debian/Ubuntu via the official APT repository:

```bash
# Create the keyrings directory
sudo mkdir -p /etc/apt/keyrings

# Install GPG and curl
sudo apt update && sudo apt install gpg curl

# (Optional) List existing keys
sudo gpg --list-keys

# Add the Bruno repository signing key
curl -fsSL "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x9FA6017ECABE0266" \
  | gpg --dearmor \
  | sudo tee /etc/apt/keyrings/bruno.gpg > /dev/null

# Set correct permissions on the key file
sudo chmod 644 /etc/apt/keyrings/bruno.gpg

# Add the Bruno APT repository
echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/bruno.gpg] http://debian.usebruno.com/ bruno stable" \
  | sudo tee /etc/apt/sources.list.d/bruno.list

# Update package lists and install Bruno
sudo apt update && sudo apt install bruno
```

## LighthousePHP

Finally, you can install LighthousePHP with this script:

```bash
curl -fsSL https://raw.githubusercontent.com/maxbertinetti/LighthousePHP/main/scripts/install.sh | sh -s -- maxbertinetti/LighthousePHP
```