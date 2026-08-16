<?php

namespace Brainjuredstudio\ProductReviews\Tests;

use Brainjuredstudio\ProductReviews\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
