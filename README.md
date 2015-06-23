<h1>Sanitizer</h1>

<p>Sanitizer is a configurable database sanitiser, built to help developers copy LIVE databases to test environments with personal and sensitive information sanitised and environment specific data configured.</p>

<p>For the responsible, protecting customer data and privacy is critical, I work in e-commerce, mainly using Magento which has loads of personal data within its database.
Moving databases down to test environments has always been a headache, especially when you're dealing with Data Protection, hopefully this will make the task easier.</p>

<h3>Main Features</h3>

<ul>
<li><p><strong>Data Protection</strong> - Remove personal and private information from a database to protect your customers.</p></li>
<li><p><strong>Sanitisation of EAV and Flat tables</strong> - Row by row or bulk sanitisation of table data.</p></li>
<li><p><strong>Table Updates</strong> - Change URLs etc for different environments</p></li>
<li><p><strong>Configurable</strong> - Can be configured to use on any* SQL based database.</p></li>
<li><p><strong>Compatible</strong> - Support various SQL database, including MySQL, MSSQL, SQLite, MariaDB, Sybase, Oracle, PostgreSQL and more (currently only tested on MySQL).</p></li>
<li><p><strong>Free</strong> - Under MIT license, you can use it anywhere if you want.</p></li>
</ul>

<p>I've built this to use mainly on Magento but it could easily be configured to work with other databases and currently it's only been tested on MySQL.</p>

<h3>Prerequisites</h3>

<p>Sanitizer requires PHP 5.3.x or greater.</p>

<h2>The typical workflow for Sanitiser is.</h2>

<h3>Single test environments</h3>

<ol>
<li>Take a copy of the live database</li>
<li>Install the copy of a the database onto a database server (if this is the test environments make sure no-one has access - use a nightly script)</li>
<li>Sanitise the database "./sanitizer.phar --configuration full_sanitizer.json" (full sanitisation)</li>
<li>Once complete either dump and move to the test environments or if it was on test you're environment is ready.</li>
</ol>

<h3>Multiple test environments</h3>

<p>If you have multiple test environments (DEV, SIT, UAT, PRE-PRODUCTION). The full sanitisation is what takes the time.</p>

<ol>
<li>Do the above.</li>
<li>Deploy the sanitised database to all your test environments</li>
<li>Run sanitiser again with a configuration script for that environment "./sanitizer.phar --configuration environment_specific_sanitizer.json"</li>
</ol>

<p>For instance with Magento the environment specific config is mainly in core<em>config</em>data. So you'd sanitise the whole database first, you'd then copy the sanitised database to all the test environments.
At this point core<em>config</em>data is wrong for the test environments. You'd then run sanitiser with a config file for that particular environments core<em>config</em>data.</p>

<h2>Installation</h2>

<p>Installation can be done in a couple of ways, either download the sanitize.phar file into your project directory or checkout the source and run the project. When the app is run it will show you the main options.</p>

<h3>Using the phar</h3>

<p>Using the phar is the easiest way to use sanitizer. </p>

<p><code>bash
./sanitize.phar
</code></p>

<h3>Using the code</h3>

<p>All you need to do to the sanitiser is run</p>

<p><code>bash
php src/Application.php
</code></p>

<h2>Configuration</h2>

<p>Sanitizer is configured by json files, sanitizer.json is the default file but this can be overridden on the command line allowing for multiple configuration files for different database types / versions / environments etc. The json file contains the table sanitisation instructions and can also contain the database configuration if you decide this is safe. <a href="sanitizer.json">sanitizer.json</a> is an example json file for Magento 1.7.0.2 which.</p>

<h3>Basics</h3>

<p>Basic configuration starts from the json root. </p>

<p><code>json
{
  "name":"Default Magento CE 1.7.0.2 for Sanitisation",
  "developer_mode":"no",
  "log_path":"sanitizer.log",
  "database":
  {
</code></p>

<h4>Name</h4>

<p>The name is a string defining what database type this should be used for as well as for which environment: <em>Default Magento CE 1.7.0.2 for Sanitisation</em></p>

<h4>Developer Mode</h4>

<p>This mode dumps every piece of output to the terminal to aid in development</p>

<h4>Log path</h4>

<p>This specified the path to the log file, typically this is in the same directory as the phar. </p>

<h3>Database</h3>

<p>The database configuration allows you to specify which database this configuration should use. </p>

<p><code>json
"database":
  {
    "host":"localhost",
    "username":"root",
    "password":"password",
    "database":"sanitizer",
    "sanitization_mode":"full"
  },
</code></p>

<ol>
<li><em>Database</em> is the database to use on that server</li>
<li><em>Host</em> Is the server the database resides</li>
<li><em>Password</em> is the password used to access the server</li>
<li><em>Username</em> is the username of the user who has permission to access the database. </li>
<li><em>sanitization</em>mode_ has two values "quick" and "full". quick will make every column specified in the config the same default value for that data type. Full will make every column specified in the config random value for that data type and based on the default values available.</li>
</ol>

<h3>Tables</h3>

<p>There are 3 table types; Flat, EAV and Update. Update is a flat table but rather than sanitising the data, the data can be updated - more later.
Every table definition goes within the 'tables' element of the configuration file. </p>

<p>```json
{
  "name":"Default Magento CE 1.7.0.2 for Sanitisation",
  "developer<em>mode":"no",
  "log</em>path":"sanitizer.log",
  "database":
  {
    "host":"localhost",
    "username":"root",
    "password":"password",
    "database":"sanitizer",
    "sanitization_mode":"full"
  },
  "tables":
  {</p>

<p>```</p>

<h4>Configuring a Flat Table</h4>

<p>The Flat table configuration looks like this
<code>json
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
</code></p>

<p>Within the tables element you specify the table name, in this case <em>customer_address_entity</em>, json is then an array of columns you wish to change. </p>

<ul>
<li>column - specifies the column name you want to sanitize</li>
<li>data_type specifies the data type for this column, this is mainly so the correct default values can be applied to each column - available options are
<ol><li>timestamp</li>
<li>datetime</li>
<li>text</li>
<li>integer</li>
<li>varchar</li></ol></li>
</ul>

