# mysqlapi
PHP API for MySql

How to connect to your MySql database through REST API.

## Pre requists ##

You simply need a functionnal PH server 5.5 minimum.

The API uses msqli library to connect to MySql

## Database configuration

Simply change the very beggining of class.ApiDatabse.php file with your own connexion data.

```php
private $host = "databaseserver:serverport";
private $user = "user";
private $password = "password";
private $database = "database";
private $defaultLimit = 100;
private $defaultOffset = 0;
private $defaultSelect = "*";
```
## Feature

### sql

url: /sql
mehtod: POST
body: 
{
  "sql": "Any valid sql query will be executed"
}

