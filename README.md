# Laravel Sierra API
A laravel package for querying the Sierra API.

## Installation

1. Add the following to the "repositories" section of your `composer.json` file
```
"repositories":[
        {
            "type":"vcs",
            "url":"https://github.com/UNC-Libraries/laravel-sierra-api.git"
        }
    ],
```

2. Add the following to your "require" section of your `composer.json` file
```
    "cappdev/laravel-sierra-api": "dev-main",
```

3. Run `composer update` to install the package
4. Run the following to instantiate the package
```
php artisan vendor:publish --provider="UncLibrary\SierraApi\SierraApiProvider"
```

5. Set up your `.env` file with the following vars:

```
SIERRA_API_KEY=
SIERRA_API_SECRET=
SIERRA_API_HOST=
```
Note: If your Library's Sierra API is configured with a different path than 
`iii/sierra-api` you can override that default with a `SIERRA_API_PATH` var in the `.env` file.

6. Add the package as a provider in `config/app.php`
```
/*
 * Package Service Providers...
 */
UncLibrary\SierraApi\SierraApiProvider::class,
```

7. Add it as a facade
```
'aliases' => [
    'Sierra' => UncLibrary\SierraApi\SierraApiFacade::class,
]
```


## Usage

In a controller, be sure to include the facade at the top of the file:
```
use Sierra;
```

Or in a view:
```
{!! Sierra::get() !!}

```

## Methods

It's best if you read the [Sierra API documentation](https://sandbox.iii.com/docs/Content/interactive.htm). 
This wrapper will automatically create the API connection using OAuth2.
You have 2 methods to get data from Sierra.

### `::get($resource, $params = array())`

`$resource` must be an available restful resource:
- acquisitions
- agencies
- authorities
- bibs
- branches
- info
- items
- orders
- patrons
- users

`$params` is an array of parameters to pass to the api.

#### Example: All patrons created on March 10, 2016
```
$patrons = Sierra::get('patrons',
    [
        'creadedDate' => ['2016-03-10T00:00:00Z','2016-03-10T23:59:59Z']
    ]
)
```

#### Example: All items of a group of bib record ids, limit return fields to status, location, and call number
```
$patrons = Sierra::get('items',
    [
        'bibIds' => "12345678,23456789,34567890,45678901",
        'fields' => "status,location,callNumber"
    ]
)
```

### `::query($resource, $query = array(), $offset = 0, $limit = 20)`

`$resource` must be an available restful resource that allows a query method
- authorities
- bibs
- items
- orders
- patrons

`$query` is an array of [query parameters](https://sandbox.iii.com/docs/Content/zReference/queryParameters.htm)

`$offset` where to begin the return

`$limit` How many matches to return

