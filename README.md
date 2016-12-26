# mysqlapi
PHP API for MySql

How to connect to your MySql database through REST API.

## Pre requists ##

You simply need a functionnal PH server 5.5 minimum.

The API uses msqli library to connect to MySql.

All api return are json objects. There for the database must be in **utf8 format only**.

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

- url: /sql
- mehtod: POST
- body:
```json
{
    "sql": "Any valid sql query will be executed"
}
```
- return: json object
```json
{
    "sql": "", // sql phrase sent
    "returnCode": 200, // return code (http standard)
    "insertedId": 0, // last inserted id when INSERT is used
    "resultCount": 12, // count of the result
    "sqlError": "", // sql error if any
    "affectedRows": 12, // number of changed rows when UPDATE is used
    "results": [ // json objects describing the database rows
        {
            "fieldid": "",
            "field1": "",
            "field2": "",
            etc.
        }
    ]
 }
 ```
