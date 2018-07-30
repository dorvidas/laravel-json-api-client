# Laravel {json:api} client

Client for easy access of data from [{json:api}](http://jsonapi.org/) API. 
Making requests to API by using PHP HTTP Clients like Guzzle or CURL requires to much code.
This package tries to simplify this process, by allowing to get data from API as simply as:
```php
$users = JsonApiClient::get('users');
//Let's return this data to view
return view('users', compact('users'));
```

## Installation
* Install package `composer require dorvidas\laravel-json-api-client`.
* Add service provider `Dorvidas\JsonApiClient\Providers\JsonApiClientServiceProvider::class` to `config/app.php`
* Publish vendor config `php artisan vendor:publish`.

## Usage
### Getting the client
You can get client in several ways:
* Via Facade `\Dorvidas\JsonApiClient\Facades\JsonAPIClient`
* Resolving from service container `Dorvidas\JsonApiClient\JsonApiClient::class`
### Making requests
* get($endpoint)
```php
JsonApiClient::get('users'); //get users
```
* post($endpoint)
```php
JsonApiClient::post('users');//store user
```
#### Request options
* `JsonApiClient::get('users')->withIncludes(['posts'])` - adds query param `include=posts` to request URL. See http://jsonapi.org/format/#fetching-includes 
* `JsonApiClient::get('users')->withFields(['user'=> ['id','name']])` - adds query param `fields[users]=id,name`. See http://jsonapi.org/format/#fetching-sparse-fieldsets
* `JsonApiClient::get('users')->withFilters(['users'=>['id'=>['eq'=>1]]])` - adds query param `filter[users][id][eq]=1`. {json:api} is agnostic about filtering, so you can choose your filtering strategy and pass what ever array you want. See http://jsonapi.org/format/#fetching-filtering.
* `JsonApiClient::limit($limit, $offset)->get('users')` - add result constraints to query param `page[limit]=x&page[offet]=y. See http://jsonapi.org/format/#fetching-pagination
* `JsonApiClient::formData(['name'=>'John'])->post('users')` - define post form data.
* `JsonApiClient::jsonData(['name'=>'John'])->post('users')` - define post JSON data.
* `JsonApiClient::token($accessToken)->get('users')` - define access token which will be added to `Authorization:Bearer {$accessToken}` header.
Note, if there is `jwt` session variable there is no need to set token here, because access token will be added to request automatically.

### Handling response
Requests will return `JsonApiResponse` object. It will contain public variables:
* `$resopnse->data` - contains response data.
* `$resopnse->meta` - contains meta data of a response.
* `$resopnse->errors` - contains errors data of a response.
Also it has helper functions
* `$resopnse->meta('count')` - return meta data with key `count`.

### Helper functions

* `JsonApiClient::authenticate($username, $password)` - will send a POST request to `/oauth/token` endpoint.
Use only if API uses [Laravel Passport](https://laravel.com/docs/5.6/passport) or any other OAuth2 implementation.
Data posted:
```php
[
  "grant_type": "password",
  "client_id": "changeit",//this is defined in config
  "client_secret": "changeit",//this is taken from config
  "username": "$username",
  "password": "$password",
  "scope": ""
]
```
Method returns `Dorvidas\JsonApiClient\User` object that extends `Illuminate\Foundation\Auth\User` class so it can be used when making [custom authentication user provider](https://laravel.com/docs/5.6/authentication#adding-custom-user-providers).



