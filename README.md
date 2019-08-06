# SmartEdge-Local-Data-Store
**Introduction**

This repository contains PHP code to run on a schedule (cron recommended) to pull data from a Google Pub/Sub subscription and store into a local MySQL database. Only linux and macOS are supported.

The Google Pub/Sub subscription needs to be set up as a Partner in Altair SmartEdge.

See the SmartEdge Public Documentation for details on how to set up a [Partner Pub/Sub pull subscription](https://confluence.prog.altair.com/pages/viewpage.action?pageId=57675267).

The code in this repository needs a valid Google user (not a service account) as a subscriber (for example, a gmail account).

**Installation**

Clone this repository to a linux based machine. If using a public server, then make sure the code is not in a public directory.

**Requirements**

 1. Altair SmartEdge Partner with pull subscription
 2. Local MySQL database
 3. cron

**Configuration**

The configuration file lives in config/config.inc.php. The following variables are required to be filled in:

    $config['projectId']
    $config['subscriptionName']
    $config['db_host']
    $config['db_user']
    $config['db_password']
    $config['db_database']

The projectId and subscriptionName can be found on the Partner details page of the Altair SmartEdge UI.

The database connection parameters need to be for a MySQL database.

**Database Table**

Create the following table in the MySQL database. If you change the table name, make sure the appropriate config variable is updated too.

    CREATE TABLE `SmartEdgeData` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `siteCd` varchar(50) DEFAULT NULL,
      `deviceCd` varchar(50) DEFAULT NULL,
      `label` varchar(200) DEFAULT NULL,
      `value` varchar(2048) DEFAULT NULL,
      `ts` int(11) NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

**Authorization**

Once the config variables have been filled in the application needs to be authorized. Using a terminal, go to the working directory and run the following:

    php authorize.php
Follow the instructions in the terminal (open the specified browser link, authorize, copy the code into the terminal).

*Make sure you use the same Google user (gmail account) as was set up as a subscriber on the Altair SmartEdge Partner.*

**Run**

The application consists of a daemon and a worker. The daemon.php script needs to be run on a schedule (for example, every hour, using cron). 

Only a single instance of the daemon is allowed per installation. The daemon itself will run for one hour, start a new instance, and exit. This will guarantee a continuous running of the application.

**Stop**

To stop the application find the pid of the running daemon.php script and kill it.

**Logs**

The code can log errors, warnings and debug messages to a log file as specified in the config file. By default the log level is set to 'warning'.
