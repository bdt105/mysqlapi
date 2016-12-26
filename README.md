# mysqlapi
PHP API for MySql CRUD (Create, Read, Update, Delete) features.

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
            "field2": "",
            "__idFieldName": "name of the id (PRIMARY key) field",
            "__idValue": "id of the record (value of __idFieldName)"
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
- return: json object containg the description of the field of the table according to "SHOW FIELDS FROM tablename"

### count

- url: /count/tablename
- method: GET, POST
- body:
```json
{
    "__where": "Any valid WHERE sql expression"
}
```
- return: json object containg the description of the field of the table according to "SELECT count(\*) FROM tablename WHERE \_\_where"

### read

- url: /read/tablename
- method: GET, POST
- body:
```json
{
    "__select": "SELECT expression",
    "__where": "Any valid WHERE sql expression",
    "__orderby": "ORDER BY expression",
    "__groupby": "GROUP BY expression"
}
```
- return: json object containg the description of the field of the table according to "SELECT \_\_select FROM tablename WHERE \_\_where GROUP BY \_\_groupby ORDER BY \_\_orderby"

### insert

- url: /insert/tablename
- method: POST
- body:
```json
[
    {
        "fieldid": "",
        "field1": "",
        "field2": ""
    },
    {
        "fieldid": "",
        "field1": "",
        "field2": ""
    }
]
```
- return: json object containg the result of "UPDATE" query.

### update

- url: /insert/tablename
- method: POST
- body:
```json
[
    {
        "fieldid": "",
        "field1": "",
        "field2": "",
        "__where": ""
    },
    {
        "fieldid": "",
        "field1": "",
        "field2": "",
        "__where": ""
    }
]
```
- return: json object containg the result of "UPDATE" query according to \_\_where condition.
