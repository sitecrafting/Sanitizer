## Sanitizer

### Prerequisites

Sanitizer requires PHP 5.3.x or greater.

## Installation

Installation can be done in a couple of ways, either download the sanitize.phar file into your project directory or checkout the source and run the project.

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

## Configuration
Sanitizer is configured by json files, sanitizer.json is the default file but this can be overridden on the command line. The json file contains the table sanitisation instructions and can also contain the database configuration if you decide this is safe. [sanitizer.json](sanitizer.json) is an example json file for Magento 1.7.0.2 which.

###Basics
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