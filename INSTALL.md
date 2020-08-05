# snelSLiM installation instructions

These instructions are meant for a system administrator to install snelSliM on shared webhosting, a VPS or a private server. 

## Requirements

snelSLiM needs to be compiled before use, this can be done either on the final hosting location in case SSH access is available and the build requirements are met. It is also possible to build the application on a local machine and then upload it to the hosting later.

### Build requirements

* Golang 1.8 or higher
* optionally gcc (if you wish to compile your own version of foliafolie)

### Hosting/VPS/Server requirements

* Unix-style Operating system
  * Basic tools: unzip, tar, sed, bash (should be installed by default)
  * xmllint (usually part of libxml, needed for corpora in XML-formats)
* PHP 7.0 or higher (may work with PHP 5.5 and 5.6, but is no longer actively tested)
  * With no restrictions on the use of shell_exec
  * Preferably the option to enlarge upload_max_filesize and post_max_size (for uploading large corpora), for example using .user.ini
  * PHP Pear Mail (Mail.php) should either be pre-installed or installed in the web folder by hand
* MySQL

## Getting the files

Currently snelSLiM has no official release packages, but uses the master branch of its git repository for distribution. 
You can either use the following command to clone the repository using git:
```
git clone https://github.com/bertvandepoel/snelSLiM.git
```

Alternatively, you can use the zip download option on https://github.com/bertvandepoel/snelSLiM

## Building the binaries

snelSLiM comes with a build script that takes care of all the build tasks in Go. It will delete all existing Go binaries and build new ones, it does not build foliafolie since snelSLiM comes with a precompiled version.
```
./build.sh
```

## Installing the files

If you have chosen to build your binaries on a local machine, you will have to transfer them to the hosting you are using, most probably using FTP or SFTP. You do not have to move any files, as soon as they have been uploaded to a working location, you can move on to configuration.

## Configuration

### Hosting configuration

If you are using a domain or subdomein directly for your installation of snelSLiM, it is important not to point the DocumentRoot of your VirtualHost to the snelSLiM folder. Instead, please point it to the web folder which contains all files to do with the web interface. There is no need to have your binaries, data and the source files accessible online.
If you wish to make snelSLiM available as a subfolder, simply use a symbolic link to the web folder and make sure your VirtualHost or .htaccess is configured to follow symlinks.

### Application configuration

There are 2 important configuration files in snelSLiM: mysql.php and config.php

mysql.php contains the login details for the mysql database. After creating a database and, if necessary, a seperate user for snelSLiM, make sure to import db.sql. This will create the tables required by snelSLiM and insert a test account to get started with. 

config.php contains several configuration options:
* timeout: this is the amount of time the analyser will wait for a preparsing corpus to finish.
* max_freqnum: the maximum amount of frequent items that can be anaylysed, available to prevent reports that take hours or days to process.
* max_threads: the maximum amount of threads the analyser can use while crunching all the numbers. By default this is 1 to prevent those using shared hosting from getting banned from the service. Enter 0 or a negative integer to use all cores, enter a specific amount to allocate that amount of the cores.
* demo: boolean value that defines whether or not the enable demo mode (disables most features, only global corpora are available).
* email_from: which email address is displayed as the sender of automated snelSLiM emails.
* email_smtp: set to true if the PHP mail() function is unavailable for sending email and an SMTP server should be used directly
* email_smtp_server: IP or address of the SMTP server (if using SMTP)
* email_smtp_port: port of the SMTP server (if using SMTP)
* email_smtp_auth: set to true of authentication is required for your SMPT server (if using SMTP)
* email_smtp_username: username for the SMTP server (if using SMTP auth)
* email_smtp_password: password for the SMTP server (if using SMTP auth)

By default, snelSLiM uses both .htaccess and .user.ini to allow uploading up to 100MB each request. If your hosting or setup requires this to be set through some other means, make sure to do so. If you wish to increase or decrease the limit to allow users to supply larger corpora or limit them to smaller corpora, you can edit .htaccess and .user.ini in the web folder. Keep in mind that depending on compression rates, the unpacked corpus might be much larger than the uploaded file.

## Accessing snelSLiM

After configuring your installation, snelSLiM should be available on the domain, subdomain or subfolder it was installed on. If there are problems connecting to the database, an error may be shown or written to your webserver error log depending the web server configuration.

You can login for the first time with the account
```
username: test@example.com
password: test
```

Make sure to create new accounts, including an admin account, before removing this test user. 

