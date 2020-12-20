

## About Adjustfly

Record Laravel model adjustments during updating event.

## Installation

Require the `patrixsmart/adjustfly` package in your `composer.json` and update your dependencies:
```sh
composer require patrixsmart/adjustfly
```

### Publish Config files

You will need to publish the config file for you to update it details:
```sh
php artisan vendor:publish --tag="adjustfly-config"
```

###  Adjustfly routes
Adjustfly exposes this routes to your application using the following route. 
```php

    use Illuminate\Support\Facades\Route;


    /*
    |--------------------------------------------------------------------------
    | Adjustment Routes
    |--------------------------------------------------------------------------
    |
    */
    Route::group([
        'namespace' => 'Patrixsmart\Adjustfly\Http\Controllers',
        'prefix' => 'api'
    ], function () {
        Route::apiResource('adjustments','AdjustmentController')->only(['index','show']);
    });

    // http://yourdomain.com/api/adjustments to get all adjustments with pagination
    // http://yourdomain.com/api/adjustments/{adjustment_id} to see details of an adjustment made
```

## Adjustfly Usage

Adjustfly requires you use this trait in any model of your app that you whiich to track it adjustments 
during updating event.  

```php

use Patrixsmart\Adjustfly\Traits\HasAdjustments;

class ModelClass extends Model
{
    use HasAdjustments; 

    // This exposes this methods to the model 
    // ModelClass->recordAdjustment() and ModelClass->adjustments()
}
```
and uses this trait in your User model see adjustments made by a particular user.

```php

use Patrixsmart\Adjustfly\Traits\OwnedAdjustments;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable 
{
    use OwnedAdjustments;

    // This exposes this method to the model 
    // UserClass->ownedAdjustments()
}
```
Finally call the recordAdjustment method on the model using the HasAdjustments trait in it 
static updating or observer updating event method.

## Adjustfly Sponsors

We would appreciate your sponsorship for the development of Adjustfly. If you are interested in becoming a sponsor, please contact PatriXsmarT LLC. via [package@patrixsmart.com](mailto:package@patrixsmart.com).


## Contributing

Thank you for considering contributing to the PatriXsmart Adjustfly!.

## Security Vulnerabilities

If you discover a security vulnerability within Adjustfly, please send an e-mail to PatriXsmarT LLC. via [package@patrixsmart.com](mailto:package@patrixsmart.com). All security vulnerabilities will be promptly addressed.

## License

PatriXsmarT Adjustfly is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
