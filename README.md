# LCMS


## Purpose

LCMS is the subsystem, where the users educates themselves about good practices on energy efficiency. LCMS interacts with GSRN. Thus, the latter can provide recommendation services to the user according to the educational content that is mostly keen on watching next based on her/his current educational profile and experiences in both SOCIALENERGY&#39;s real and virtual worlds. The role of the LCMS is important because it provides the user the opportunity to better comprehend the new concepts in the liberalized smart grid markets and inter-relate the &quot;lessons learned&quot; from the GAME with the real-life conditions in order to be able to efficiently interact with her/his electric utility/retailer. The LCMS subsystem is built on top of Moodle ([https://moodle.org/](https://moodle.org/)).

The LCMS is available at [http://socialenergy.it.fmi.uni-sofia.bg](http://socialenergy.it.fmi.uni-sofia.bg).

## Installation steps

The following steps have been tested on Ubuntu 16.04. Please adapt accordingly for other distributions/OSs.

### Install and configure LCMS

Basic Requirements

- You will need a working Apache web server, a database (e.g. MySQL) and have PHP 7 configured.
- Moodle requires a number of PHP extensions. However, Moodle checks early in the installation process and you can fix the problem and re-start the install script if any are missing.

#### Create MySQL Database

Login to the MySQL server as _root_ and create a user and database for the Moodle installation:

    mysql -u root -p
    mysql> CREATE DATABASE moodle;
    mysql> GRANT ALL PRIVILEGES ON moodle.* TO 'moodleuser'@'localhost' IDENTIFIED BY 'PASSWORD';
    mysql> FLUSH PRIVILEGES;

Notes: Don&#39;t forget to replace &#39;PASSWORD&#39; with an actual strong password. Save this password you use for the Moodle user, since you will need it later in the install.

#### Get Moodle from GitHub repository

Change the current working directory and clone Moodle from the official GitHub repository

    cd /var/www/html/
    git clone -b MOODLE\_34\_STABLE git://git.moodle.org/moodle.git

Create a directory for the Moodle data

    mkdir /var/moodledata

Set the correct ownership and permissions

    chown -R www-data:www-data /var/www/html/moodle
    chown www-data:www-data /var/moodledata

#### Configure Apache Web Server

Create Apache virtual host for your domain name with the following content

/etc/apache2/sites-available/yourdomain.com.conf

         ServerAdmin admin@yourdomain.com
         DocumentRoot /var/www/html/moodle
         ServerName yourdomain.com
         ServerAlias www.yourdomain.com
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        allow from all
        ErrorLog /var/log/httpd/yourdomain.com-error\_log
        CustomLog /var/log/httpd/yourdomain.com-access\_log common

Save the file and enable the virtual host

    a2ensite yourdomain.com
	Enabling site yourdomain.com.

To activate the new configuration, you need to run:

      service apache2 reload

Finally, reload the web server as suggested, for the changes to take effect

    service apache2 reload

**Follow the on-screen instructions and complete the installation**

Now, go to http://yourdomain.com and follow the on-screen instructions to complete the Moodle installation. For more information on how to configure and use Moodle, you can check the [official documentation](https://docs.moodle.org/34/en/Main_page).

#### Enabling and configuring OAuth 2 authentication

The OAuth 2 authentication plugin enables users to log in LCMS (Moodle) using their GSRN account via button on the LCMS login page. You will need to obtain OAuth 2.0 credentials (client ID and client secret) from GSRN platform.

Create and configure new OAuth 2 service:

1. Go to &#39;OAuth 2 services&#39; in _Site administration &gt; Server_ and click the button &quot;_Create new custom service_&quot;.
2. Enter the client ID and client secret, make sure _&#39;Show on login page_&#39; is checked, and require email verification is unchecked, and then save changes.
3. Configure the endpoints for the issuer
  - authorization\_endpoint
  - token\_endpoint
  - userinfo\_endpoint

#### Installing SocialEnergy local plugin

This plugin provides Web Service API that enables the integration between GSRN platform and LCMS.

##### Get SocialEnergy plugin from GitHub repository

Change the current working directory and clone the plugin from the project&#39;s official GitHub repository

    cd /var/www/html/moodle/local
    git clone https://github.com/socialenergy-project/moodle-local\_socialenergy.git socialenergy

The remaining installation is taken care of by LCMS by clicking on _Site Administration &gt; Notifications_.

##### Associated plugins

There are currently one plugin that require this integration

1. [Course dedication](https://moodle.org/plugins/block_dedication)

##### Enabling web services

1. Access _Site administration &gt; Advanced features_
2. Check &#39;_Enable web services_&#39; then click &#39;_Save Changes_&#39;

##### Enabling protocols

1. Access _Site administration &gt; Plugins &gt; Web services &gt; Manage protocols_
2. Enable REST protocol

##### Creating a service

1. Access _Site administration &gt; Plugins &gt; Web services &gt; External services_
2. Click Add new custom service
3. Check &#39;_Authorised users only_&#39;
4. Enter a name and check _Enabled_
5. Click the button &#39;_Add service_&#39;

##### Adding functions to the service

The newly created service is currently empty and doesn&#39;t do anything. Web service functions need to be added.

1. Click &#39;Add functions&#39; link
2. Select local\_socialenergy\_user\_create\_user, local\_socialenergy\_competency\_create\_plan, local\_socialenergy\_user\_get\_profile functions and click the &#39;Add functions&#39; button

##### Authorise specific users

1. _Site Administration &gt; Plugins &gt; Web services &gt; External Services_
2. Select _Authorised users_ link (the service must have been set as Authorised users only in the _Edit_ link)
3. Select appropriate user with administrative permissions and click _Add_

##### Create a token

Token Authentication is a standard form of authentication for web services. The LCMS service identifies requests via a unique token and executes requests based on the permissions for that account.

1. _Site Administration &gt; Plugins &gt; Web services &gt; Manage tokens_
2. Click on _Add_
3. Select the created user and service
4. Click on save changes

## Registration and mock data experimentation

In order to use the LCMS platform as a standalone platform it should be imported the competency framework and course data. Please login as administrator and complete the steps below.

### Importing Social Energy Competency Framework

1. Download the csv file from [https://github.com/socialenergy-project/moodle-mock\_data/blob/master/SEFR.csv](https://github.com/socialenergy-project/moodle-mock_data/blob/master/SEFR.csv)
2. _Site administration &gt; Competencies &gt; Import competency framework_
3. Select CSV comma delimited
4. Confirm the column mappings on next screen
5. Finish the import

### Configuring SocialEnergy local plugin

1. _Site Administration &gt; Plugins &gt; Local plugins &gt; Social Energy Custom Services_
2. Select previously imported competency framework
3. Fill-in default ILP&#39;s name
4. Fill-in the URL address to which user should be redirected after log out
5. Click on save changes

### Importing courses

1. Download courses&#39; archives (.mbz files) from [https://github.com/socialenergy-project/moodle-mock\_data](https://github.com/socialenergy-project/moodle-mock_data)
2. _Site administration &gt; Front page settings &gt; Restore_
3. Upload the .mbz file and click Restore
4. Confirm - Check that everything is as required then click the Continue button
5. Destination - Choose whether the course should be restored as a new course or into an existing course then click the Continue button
6. Settings - Select activities, blocks, filters and possibly other items as required then click the Next button
7. Schema - Select/deselect specific items and amend the course name, short name and start date if necessary then click the Next button
8. Review - Check that everything is as required, using the Previous button if necessary, then click the &#39;Perform restore&#39; button
9. Complete - Click the continue button
10. Repeat steps above for the other courses

## Navigation and visualization

[![SOCIALENERGY LCMS DEMO](https://img.youtube.com/vi/DeF-VgEPRCo/0.jpg)](https://www.youtube.com/watch?v=DeF-VgEPRCo)

### ACKNOWLEDGMENT

Funding for this work was provided by the EC H2020 project SOCIALENERGY [http://socialenergy-project.eu/](http://socialenergy-project.eu/) Grant  agreement  No.  731767.