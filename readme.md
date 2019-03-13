# LaravelBaseRest

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][https://github.styleci.io/repos/175152077]][link-styleci]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Via Composer

``` bash
$ composer require khancode/laravelbaserest
```

Publish

```
$ php artisan vendor:publish --tag=laravelbaserest.config
$ php artisan vendor:publish --tag=laravelbaserest.lang
```

## Usage

this for base model 
```
use KhanCode\LaravelBaseRest\BaseModel;
```

this for base Repository 
```
use KhanCode\LaravelBaseRest\BaseRepository;
```

this for base Service 
```
use KhanCode\LaravelBaseRest\BaseService;
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email amalsholihan@gmail.com instead of using the issue tracker.

## Credits

- [A'mal Sholihan][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/khancode/laravelbaserest.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/khancode/laravelbaserest.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/khancode/laravelbaserest/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/khancode/laravelbaserest
[link-downloads]: https://packagist.org/packages/khancode/laravelbaserest
[link-travis]: https://travis-ci.org/khancode/laravelbaserest
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/khancode
[link-contributors]: ../../contributors
