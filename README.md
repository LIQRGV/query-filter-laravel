# Query Filter Laravel 
[![Build Status](https://api.travis-ci.com/LIQRGV/query-filter-laravel.svg?branch=master)](https://travis-ci.com/LIQRGV/query-filter-laravel)
[![codecov](https://codecov.io/gh/LIQRGV/query-filter-laravel/branch/master/graph/badge.svg)](https://codecov.io/gh/LIQRGV/query-filter-laravel)


Change your Request into Query builder

With this package, we can prevent doing tedious work of composing query builder
This package will turn URL query  
```
book?filter[title][is]=Harry&filter[published_at][>]=2010-10-13
```
into
```
Book::query()
  ->where('title', '=', 'Harry')
  ->where('published_at', '>', '2010-10-13')
``` 

## Getting Started

(TBD)

### Installing

(TBD)

## Running the tests

In order to running the test, you should have `composer` on your system.
Read more on https://getcomposer.org/doc/00-intro.md

You should install dependency for testing with 
```
composer install
```
After that, you can run all test with
```
./phpunit
```

## Deployment

(TBD)

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Septian Hari** - *Initial work* - [LIQRGV](https://github.com/LIQRGV)

See also the list of [contributors](https://github.com/LIQRGV/query-filter-laravel/contributors) who participated in this project.

## License

This project is licensed under the MIT License

## Acknowledgments

* Hat tip to anyone whose code was used
* Inspiration
* etc

