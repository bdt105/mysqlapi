# mysqlapi
PHP API for MySql CRUD (Create, Read, Update, Delete) features.

How to connect to your MySql database through REST API.

## Pre requists ##

You simply need a functionnal PHP server 5.5 minimum.

The API uses msqli library to connect to MySql.

All api returns are json objects. Therefors the database must be **utf8 encoded only**.

Don't forget to set your .htaccess file so http://serveur:port/apiurl/xxx works properly (for instance):
```php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ api.php?rquest=$1 [QSA,NC,L]
```

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

- description: executes a sql expression and returns its results.
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

- description: returns the fields and properties of a table *tablename*
- url: /fields/tablename
- method: GET
- return: json object containg the description of the field of the table according to "SHOW FIELDS FROM tablename"

### count

- description: returns the number of rows a table *tablename*.
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

- description: returns the rows of a table *tablename*.
- url: /read/tablename?limit=10&offset=3 (records from 3 to 13), only limit and offset parameter are available in GET method
- method: GET, POST
- body:
```json
{
    "__select": "SELECT expression",
    "__where": "Any valid WHERE sql expression",
    "__orderby": "ORDER BY expression",
    "__groupby": "GROUP BY expression",
    "__limit": "LIMIT expression",
    "__offset": "OFFSET exporession"
}
```
- return: json object containg the description of the field of the table according to "SELECT \_\_select FROM tablename WHERE \_\_where GROUP BY \_\_groupby ORDER BY \_\_orderby"

### insert

- description: inserts rows into a table *tablename*.
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

- description: updates rows of a table *tablename* according to a condition.
- url: /update/tablename
- method: POST
- body:
```json
[
    {
        "fieldid": "new value",
        "field1": "new value",
        "field2": "new value",
        "__where": ""
    },
    {
        "fieldid": "new value",
        "field1": "new value",
        "field2": "new value",
        "__where": ""
    }
]
```
- return: json object containg the result of "UPDATE" query according to \_\_where condition.

### delete


- description: deletes some rows of a table *tablename* according to a condition.
- url: /delete/tablename
- method: POST
- body:
```json
[
    {
        "__where": ""
    },
    {
        "__where": ""
    }
]
```
- return: json object containg the result of "DELETE" query according to \_\_where condition.

### fresh

- description: returns an empty row of a table *tablename*.
- url: /fresh/tablename
- method: POST
- body:
```json
[
    {
        "__where": ""
    },
    {
        "__where": ""
    }
]
```
- return: json object containg the result of "DELETE" query according to \_\_where condition.
