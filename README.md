## Sanitizer
Sanitizer is a configurable database sanitiser, built to help developers copy LIVE databases to test environments with personal and sensitive information sanitised and environment specific data configured.

For the responsible, protecting customer data and privacy is critical, I work in e-commerce, mainly using Magento which has loads of personal data within its database.
Moving databases down to test environments has always been a headache, especially when you're dealing with Data Protection, hopefully this will make the task easier.


##The typical workflow for Sanitiser is.

###Single test environments
1. Take a copy of the live database
2. Install the copy of a the database onto a database server (if this is the test environments make sure no-one has access - use a nightly script)
3. Sanitise the database "./sanitizer.phar --configuration full_sanitizer.json" initial sanitisation
4. Once complete either dump and move to the test environments or if it was on test you're environment is ready.


###Multiple test environments
If you have multiple test environments. The initial sanitisation is what takes the time.
1. Do the above.
2. Deploy the sanitised database to all your test environments
3. Run sanitiser again with a configuration script for that environment "./sanitizer.phar --configuration environment_specific_sanitizer.json"

For instance with Magento the environment specific config is mainly in core_config_data. So you'd sanitise the whole database first, you'd then copy the sanitised database to all the test environments.
At this point core_config_data is wrong for the test environments. You'd then run sanitiser with a config file for that particular environments core_config_data.

###Main Features

* **Data Protection** - Remove personal and private information from a database to protect your customers.

* **Sanitisation of EAV and Flat tables** - Row by row or bulk sanitisation of table data.

* **Table Updates** - Change URLs etc for different environments

* **Configurable** - Can be configured to use on any* SQL based database.

* **Compatible** - Support various SQL database, including MySQL, MSSQL, SQLite, MariaDB, Sybase, Oracle, PostgreSQL and more (currently only tested on MySQL).

* **Free** - Under MIT license, you can use it anywhere if you want.

I've built this to use mainly on Magento but it could easily be configured to work with other databases and currently it's only been tested on MySQL.

### Prerequisites

Sanitizer requires PHP 5.3.x or greater.

## Installation

Installation can be done in a couple of ways, either download the sanitize.phar file into your project directory or checkout the source and run the project. When the app is run it will show you the main options.

### Using the phar
Using the phar is the easiest way to use sanitizer. 

```bash
./sanitize.phar
```

### Using the code
All you need to do to the sanitiser is run

```bash
php src/Application.php
```
or if you've used composer

```bash
./bin/sanitizer
```

## Configuration
Sanitizer is configured by json files, sanitizer.json is the default file but this can be overridden on the command line allowing for multiple configuration files for different database types / versions / environments etc. The json file contains the table sanitisation instructions and can also contain the database configuration if you decide this is safe. [sanitizer.json](sanitizer.json) is an example json file for Magento 1.7.0.2 which.

###Basics
Basic configuration starts from the json root. 

```json
{
  "name":"Default Magento CE 1.7.0.2 for Sanitisation",
  "developer_mode":"no",
  "log_path":"sanitizer.log",
  "database":
  {
```
####Name
The name is a string defining what database type this should be used for as well as for which environment: *Default Magento CE 1.7.0.2 for Sanitisation*
####Developer Mode
This mode dumps every piece of output to the terminal to aid in development
####Log path
This specified the path to the log file, typically this is in the same directory as the phar. 

###Database
The database configuration allows you to specify which database this configuration should use. 

```json
"database":
  {
    "host":"localhost",
    "username":"root",
    "password":"password",
    "database":"sanitizer",
    "sanitization_mode":"full"
  },
```

1. _Database_ is the database to use on that server
2. _Host_ Is the server the database resides
3. _Password_ is the password used to access the server
4. _Username_ is the username of the user who has permission to access the database. 
5. _sanitization_mode_ has two values "quick" and "full". quick will make every column specified in the config the same default value for that data type. Full will make every column specified in the config random value for that data type and based on the default values available.

###Tables
There are 3 table types; Flat, EAV and Update. Update is a flat table but rather than sanitising the data, the data can be updated - more later.
Every table definition goes within the 'tables' element of the configuration file. 

```json
{
  "name":"Default Magento CE 1.7.0.2 for Sanitisation",
  "developer_mode":"no",
  "log_path":"sanitizer.log",
  "database":
  {
    "host":"localhost",
    "username":"root",
    "password":"password",
    "database":"sanitizer",
    "sanitization_mode":"full"
  },
  "tables":
  {
   
```

####Configuring a Flat Table
The Flat table configuration looks like this
```json
"customer_address_entity": [
      {
        "column": "created_at",
        "data_type":"timestamp"
      },
      {
        "column": "updated_at",
        "data_type":"timestamp"
      }
    ],
```

Within the tables element you specify the table name, in this case *customer_address_entity*, json is then an array of columns you wish to change. 

* column - specifies the column name you want to sanitize
* data_type specifies the data type for this column, this is mainly so the correct default values can be applied to each column - available options are
   1. timestamp
   2. datetime
   3. text
   4. integer
   5. varchar

###Pre and Post Conditions

#### Pre Conditions

Before sanitizer starts you can instruct it via the config to import a database. It must contain the
source and this can be a relative or an absolute path.

```json
"pre_conditions":
  {
    "import_database":
    {
      "source":"sql/sanitizer.sql"
    }
  },
```

#### Post Conditions

After sanitizer has finished you can instruct it to export the database to a dump file

The only required attribute here is the destination which specifies the location and
name of the file. Sanitizer also provides a couple of patterns whihc get switched out

1. '{date}' which gets replace with 'date_format'
2. '{time}' which gets replace with 'time_format'

The format of 'time_format', and 'date_format' uses the [PHP date format](http://php.net/manual/en/function.date.php)

```json
  "post_conditions":
  {
    "export_database":
    {
      "date_format":"d-m-Y",
      "time_format":"G-i-s-e",
      "destination":"sql/sanitizer_sanitized_{date}-{time}.sql",
      "drop":"true"
    }
  },
```