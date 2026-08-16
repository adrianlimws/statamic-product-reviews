<?php

namespace Brainjuredstudio\ProductReviews;

use Brainjuredstudio\ProductReviews\Commands\SyncReviewsCommand;
use Brainjuredstudio\ProductReviews\Contracts\ReviewProvider;
use Brainjuredstudio\ProductReviews\Http\Controllers\CP\UtilityController;
use Brainjuredstudio\ProductReviews\Providers\YotpoProvider;
use Brainjuredstudio\ProductReviews\Support\SyncStatus;
use Brainjuredstudio\ProductReviews\Tags\ProductReviews;
use Brainjuredstudio\ProductReviews\Yotpo\YotpoClient;
use Illuminate\Console\Scheduling\Schedule;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        ProductReviews::class,
    ];

    protected $commands = [
        SyncReviewsCommand::class,
    ];

    public function register()
    {
        parent::register();

        $this->app->singleton(YotpoClient::class, fn () => new YotpoClient);

        $this->app->bind(ReviewProvider::class, function ($app) {
            $provider = config('product-reviews.provider', 'yotpo');

            return match ($provider) {
                'yotpo' => $app->make(YotpoProvider::class),
                default => throw new \InvalidArgumentException("Unsupported review provider [{$provider}]."),
            };
        });
    }

    public function bootAddon()
    {
        $this->publishes([
            __DIR__.'/../resources/content/collections/product_reviews.yaml' => base_path('content/collections/product_reviews.yaml'),
            __DIR__.'/../resources/blueprints/collections/product_reviews/review.yaml' => resource_path('blueprints/collections/product_reviews/review.yaml'),
        ], 'product-reviews-content');

        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', [
                '--tag' => 'product-reviews-content',
                '--force' => true,
            ]);
            $command->call('vendor:publish', [
                '--tag' => 'product-reviews-config',
                '--force' => true,
            ]);
        });

        Utility::extend(function () {
            Utility::register('product_reviews')
                ->title('Product Reviews')
                ->navTitle('Reviews')
                ->icon('favorite-stars')
                ->description('Sync product reviews from Yotpo into a local collection.')
                ->view('product-reviews::utility', function () {
                    $provider = app(ReviewProvider::class);

                    return [
                        'title' => 'Product Reviews',
                        'description' => 'Pull reviews into the product_reviews collection.',
                        'provider' => $provider->key(),
                        'configured' => $provider->isConfigured(),
                        'status' => SyncStatus::get(),
                        'syncUrl' => cp_route('utilities.product-reviews.sync'),
                    ];
                })
                ->routes(function ($router) {
                    $router->post('sync', [UtilityController::class, 'sync'])->name('sync');
                });
        });
    }

    protected function schedule(Schedule $schedule)
    {
        $frequency = config('product-reviews.sync.schedule', 'daily');

        $event = $schedule->command('product-reviews:sync');

        match ($frequency) {
            'hourly' => $event->hourly(),
            'every_fifteen_minutes' => $event->everyFifteenMinutes(),
            default => $event->daily(),
        };
    }
}
