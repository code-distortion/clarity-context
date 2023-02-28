# Clarity Context - Understand Your Exceptions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/clarity-context.svg?style=flat-square)](https://packagist.org/packages/code-distortion/clarity-context)
![PHP Version](https://img.shields.io/badge/PHP-8.0%20to%208.3-blue?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-8%20to%2010-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/code-distortion/clarity-context/run-tests.yml?branch=master&style=flat-square)](https://github.com/code-distortion/clarity-context/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/clarity-context)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.1%20adopted-ff69b4.svg?style=flat-square)](.github/CODE_OF_CONDUCT.md)

***code-distortion/clarity-context*** is a **Context Tracker** package for Laravel that gives you a birds-eye-view of what your code was doing when an exception occurs.

[Add context](#add-context-to-your-code) to your code. e.g.

``` php
// in a file in your project
Clarity::context('Performing checkout', ['user-id' => $userId, 'order-id' => $orderId]);
…
```

``` php
// in another file
Clarity::context('Sending payment request to gateway');
Clarity::context(['payment-gateway' => 'examplexyz.com', 'card-id' => $cardId, 'amount' => $amount]);
…
```

This information is collected so when an exception occurs, it can be used to [show what your code was doing](#exception-logging) at the time. e.g.

```
app/Domain/Checkout/PerformCheckoutAction.php on line 20 (method "submit")
- "Performing checkout"
- user-id = 5
- order-id = 123

app/Domain/Payments/MakePaymentAction.php on line 19 (method "handle") (last application frame)
- "Sending payment request to gateway"
- payment-gateway = 'examplexyz.com'
- card-id = 456
- amount = '10.99'

vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php on line 856 (closure)
- The exception was thrown
```



<br />



## Clarity Suite

Clarity Context is a part of the ***Clarity Suite***, designed to let you manage exceptions more easily:
- **Clarity Context** - Understand Your Exceptions
- [Clarity Logger](https://github.com/code-distortion/clarity-logger) - Useful Exception Logs
- [Clarity Control](https://github.com/code-distortion/clarity-control) - Handle Your Exceptions



<br />



## Table of Contents

- [Installation](#installation)
  - [Config File](#config-file)
- [Add Context to Your Code](#add-context-to-your-code)
- [Exception Logging](#exception-logging)



## Installation

Install the package via composer:

``` bash
composer require code-distortion/clarity-context
```



### Config File

Use the following command if you would like to publish the `config/code_distortion.clarity_context.php` config file.

It simply gives you the option to turn this package on or off.

``` bash
php artisan vendor:publish --provider="CodeDistortion\ClarityContext\ServiceProvider" --tag="config"
```



## Add Context to Your Code

Clarity Context lets you add context details throughout your code. It keeps track of what's currently in the call stack, ready for when an exception occurs. e.g.

``` php
Clarity::context("A quick description of what's currently happening");
Clarity::context(['some-relevant-id' => 123]);
```

You can add *strings* to explain what's currently happening in a sentence, or *associative arrays* to show specific details about what your code is currently working with.

Add context throughout your code in relevant places. Pick places that will give you the most insight when tracking down a problem. Add as many as you feel necessary.

You can pass multiple values at once:

``` php
Clarity::context("Processing csv file", ['file' => $file, 'category' => $categoryId]);
```

> ***Note:*** Don't add sensitive details that you don't want to be logged!

If you use trace identifiers to identify requests, you can add these as well. A good place to add them would be in a [service provider](https://laravel.com/docs/10.x/providers) or [request middleware](https://laravel.com/docs/10.x/middleware).

``` php
Clarity::traceIdentifier($traceId);
```



## Exception Logging

To log your exceptions, install a package like [Clarity Logger](https://github.com/code-distortion/clarity-logger) that's aware of *Clarity Context*. Follow its installation instructions to add logging to your project.

*Clarity Logger* will automatically include your context details alongside the details it normally logs. e.g.

```
EXCEPTION (UNCAUGHT):

exception     Illuminate\Http\Client\ConnectionException: "cURL error 6: Could not resolve host: api.example-gateway.com (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.example-gateway.com"
- location    app/Http/Controllers/CheckoutController.php on line 50 (method "submit")
- vendor      vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php on line 856 (closure)
request       POST https://my-website.com/checkout
- referrer    https://my-website.com/checkout
- route       cart.checkout
- middleware  web
- action      CheckoutController@submit
- trace-id    1234567890
user          3342 - Bob - bob@example.com (123.123.123.123)
- agent       Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36
date/time     Sunday 2nd April at 7:08pm (Australia/Sydney)  2023-04-02 19:08:23 AEST +10:00

CONTEXT:

app/Domain/Checkout/PerformCheckoutAction.php on line 20 (method "handle")
- "Performing checkout"
- user-id = 5
- order-id = 123

app/Domain/Payments/MakePaymentAction.php on line 19 (method "handle") (last application frame)
- "Sending payment request to gateway"
- payment-gateway = 'examplexyz.com'
- card-id = 456
- amount = '10.99'

vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php on line 856 (closure)
- The exception was thrown
```

<details>
<summary>⚙️ Click for more information.</summary>



## Logging Exceptions (Advanced)

Clarity Context collects and manages the context details you've added to your code.

When an exception occurs, it builds a `CodeDistortion\ClarityContext\Context` object that can be used by the code doing the logging. This `Context` object contains the details you added (that were present in the call stack at the time).

If you'd like to handle the logging yourself, or are building a package to do so - this involves updating Laravel's [exception handler](https://laravel.com/docs/10.x/errors#the-exception-handler) `app/Exceptions/Handler.php` to use these `Context` values.

This section explains how to use this `Context` class.



### Obtaining the Context Object

Use `Clarity::getExceptionContext($e)` to access the `CodeDistortion\ClarityContext\Context` object built for that exception.

Then you can choose how to log the exception based on what's inside the `Context` object.

``` php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use CodeDistortion\ClarityContext\Clarity; // <<<
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    …

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {

            $context = Clarity::getExceptionContext($e); // <<<
            // … perform formatting and logging here
        });
    }
}
```



### The Context Object

The `Context` object includes a variety of details about the exception, including:

- the call stack / stack trace (based on `$e->getTrace()`, but with the file/line numbers shifted by one frame, so they make more sense),
- your context details, that were present in the call stack at the time the exception occurred,
- references to the location where the exception was thrown and caught.

``` php
$context->getException();           // the exception that was caught
$context->getChannels();            // the intended channels to log to
$context->getLevel();               // the intended reporting level (debug, … emergency)
$context->getDefault();             // the default value that will be returned
$context->getTraceIdentifiers();    // the trace identifiers
$context->getKnown();               // "known" issues associated with the exception
$context->hasKnown();               // whether the exception has "known" issues or not
$context->getReport();              // whether to trigger Laravel's report() method or not
$context->getRethrow();             // whether to rethrow, a closure to resolve it, or an exception itself to throw
$context->detailsAreWorthListing(); // whether details (other than those you can get by looking at the exception alone) are available

$stackTrace = $context->getStackTrace(); // the stack trace frames (most recent at the start)
$callStack = $context->getCallStack();   // the same as the stack trace, but in reverse
```



#### Stack Trace / Call Stack, and Frames

You can retrieve details about the call stack frames using `$context->getStackTrace()` or `$context->getCallStack()`. They contain objects representing each frame.

`getStackTrace()` contains the frames in order from most recent to oldest. `getCallStack()` is the same, except ordered from oldest to newest.

They also contain the following methods to help you find particular frames and meta information.

``` php
$stackTrace = $context->getStackTrace(); // or $context->getCallStack();

$stackTrace->getLastApplicationFrame();      // get the last application (i.e. non-vendor) frame
$stackTrace->getLastApplicationFrameIndex(); // get the index of the last application frame
$stackTrace->getExceptionThrownFrame();      // get the frame that threw the exception
$stackTrace->getExceptionThrownFrameIndex(); // get the index of the frame that threw the exception
$stackTrace->getExceptionCaughtFrame();      // get the frame that caught the exception
$stackTrace->getExceptionCaughtFrameIndex(); // get the index of the frame that caught the exception
$stackTrace->getMeta();                      // get the Meta objects - these represent the context details, amongst others
$stackTrace->getGroupedMeta();               // get the Meta objects grouped together in MetaGroups - see below
```

They are iterable, allowing them to be looped through.

You can retrieve the following details from the Frame objects inside:

``` php
$stackTrace = $context->getStackTrace(); // or $context->getCallStack();

foreach ($stackTrace as $frame) {
    $frame->getFile();                // the path to the file containing the code being run
    $frame->getProjectFile();         // the same file, but relative to the project-root's dir
    $frame->getLine();                // the relevant line number
    $frame->getFunction();            // the function or method being run at the time
    $frame->getClass();               // the class being used at the time
    $frame->getObject();              // the object instance being used at the time
    $frame->getType();                // the "type" ("::", "->")
    $frame->getArgs();                // the arguments the function or method was called with
    $frame->getMeta();                // retrieve the Meta objects, see below
    $frame->isApplicationFrame();     // is this an application (i.e. non-vendor) frame?
    $frame->isLastApplicationFrame(); // is this the last application frame (before the exception was thrown)?
    $frame->isVendorFrame();          // is this a vendor frame?
    $frame->isLastFrame();            // is this in the last frame in the (where the exception was thown)?
    $frame->exceptionWasThrownHere(); // was the exception thrown by this frame?
    $frame->exceptionWasCaughtHere(); // was the exception caught by this frame?
}
```

> ***Note:*** Some of the methods like `getFunction()`, `getClass()`, `getObject()` won't always return a value. It depends on the circumstance. See [PHP's debug_backtrace method](https://www.php.net/manual/en/function.debug-backtrace.php) for more details.



#### Meta Objects

There are 5 types of Meta objects:
- `ContextMeta` - when the application called `Clarity::context(…)` to add context details,
- `CallMeta` - when the Control package ran some code for the application (e.g. using `Control::run()`),
- `LastApplicationFrameMeta` - the location of the last application (i.e. non-vendor) frame,
- `ExceptionThrownMeta` - the location the exception was thrown,
- `ExceptionCaughtMeta` - the location the exception was caught.

You can retrieve the following details from the Meta objects:

``` php
// all Meta classes
$meta->getFile();        // the relevant file 
$meta->getProjectFile(); // the same file, but relative to the project-root's dir
$meta->getLine();        // the relevant line number
$meta->getFunction();    // the function or method being run at the time
$meta->getClass();       // the class being used at the time
$meta->getType();        // the "type" ("::", "->")
// ContextMeta only
$meta->getContext();     // the context array or sentence
// CallMeta only
$meta->wasCaughtHere();  // whether the excepton was caught here or not
$meta->getKnown();       // the "known" issues associated to the exception
```

There are several ways of retrieving Meta objects:

``` php
$context->getStackTrace()->getMeta(); // all the Meta objects, in stack trace order
$context->getCallStack()->getMeta();  // all the Meta objects, in call stack order
$frame->getMeta();                    // the Meta objects present in a particular frame
$metaGroup->getMeta();                // related Meta objects, grouped togther (see below)
```

Each of these methods accepts a meta-class string, or several of them, which limit the result. e.g.

``` php
$context->getStackTrace()->getMeta(ContextMeta::class); // only ContextMeta objects will be returned
```



#### MetaGroup Objects

When reporting the exception details, it's useful to group the Meta objects together. `MetaGroup` objects provide a way of grouping the Meta objects in a logical way.

The Meta objects within are related, i.e. in the same frame and on near-by lines.

``` php
$context->getStackTrace()->getMetaGroups();
$context->getCallStack()->getMetaGroups();
```

Each MetaGroup contains similar details to the `Frame` object.

``` php
$metaGroup->getFile();                    // the path to the file containing the code being run 
$metaGroup->getProjectFile();             // the same file, but relative to the project-root's dir
$metaGroup->getLine();                    // the relevant line number
$metaGroup->getFunction();                // the function or method being run at the time
$metaGroup->getClass();                   // the class being used at the time
$metaGroup->getType();                    // the "type" ("::", "->")
$metaGroup->getMeta();                    // the meta objects contained within
$metaGroup->isInApplicationFrame();       // is this in an application (i.e. non-vendor) frame?
$metaGroup->isInLastApplicationFrame();   // is this in the last application frame (before the exception was thrown)?
$metaGroup->isInVendorFrame();            // is this in a vendor frame?
$metaGroup->isInLastFrame();              // is this in the last frame (where the exception was thown)?
$metaGroup->exceptionThrownInThisFrame(); // is this in the frame the exception was thrown from?
$metaGroup->exceptionCaughtInThisFrame(); // is this in the frame the exception was caught in?
```



### Context Objects Without an Exception

You can generate a Context object arbitrarily, without needing an exception.

The Context object returned will contain the current context details, like it normally would.

``` php
$context = Clarity::buildContextHere();
```

</details>



<br />



## Testing This Package

- Clone this package: `git clone https://github.com/code-distortion/clarity-context.git .`
- Run `composer install` to install dependencies
- Run the tests: `composer test`



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.



### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth, it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.



## Treeware

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/code-distortion/clarity-context) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.



## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.



### Code of Conduct

Please see [CODE_OF_CONDUCT](.github/CODE_OF_CONDUCT.md) for details.



### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.



## Credits

- [Tim Chandler](https://github.com/code-distortion)



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
