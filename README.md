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

### Return object

Any API will return the same json object. Results array contains rows field by field according to the sql pharse.
```json
{
    "sql": "sql phrase sent",
    "returnCode": "return code (http standards)",
    "insertedId": "last inserted id when INSERT is used",
    "resultCount": "count of the result",
    "sqlError": "sql error if any",
    "affectedRows": "number of changed rows when UPDATE is used",
    "results": [
        {
            "fieldid": "",
            "field1": "",
            "field2": ""
        }
    ]
 }
 ```

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
### fields
- url: /fields/tablename
- method: GET
- return: json object containg the description of the field of the table according to __SHOW FIELDS FROM tablename__

