# Product Reviews

**Statamic 6 addon** that syncs product reviews from **Yotpo** into a local collection. Reviews stay on your server (no client-side Yotpo widgets), are editable in the Control Panel, and render with Antlers plus Schema.org JSON-LD.

> Provider today: **Yotpo**. Architecture supports additional platforms later.

## Features

- Pull Yotpo reviews via REST API into a `product_reviews` collection
- Scheduled and on-demand sync
- Manual override so CP edits are not overwritten
- Antlers tags for listing, averages, and counts
- Schema.org Product / AggregateRating / Review markup
- CP utility for connection status and “Sync now”

## Requirements

- PHP 8.3+
- Statamic 6
- A Yotpo account with App Key + API Secret

## Install

```bash
composer require brainjuredstudio/product-reviews
php artisan vendor:publish --tag=product-reviews-config
php artisan vendor:publish --tag=product-reviews-content
```

On `php please statamic:install`, config and content publish automatically.

## Configure

Add to `.env`:

```env
PRODUCT_REVIEWS_YOTPO_APP_KEY=your-app-key
PRODUCT_REVIEWS_YOTPO_SECRET=your-api-secret
```

Optional:

```env
PRODUCT_REVIEWS_PROVIDER=yotpo
PRODUCT_REVIEWS_PAGE_SIZE=100
PRODUCT_REVIEWS_SCHEDULE=daily
```

Yotpo credentials: **Settings → Store Settings → API Credentials**.

## Sync

```bash
php please product-reviews:sync
# or
php artisan product-reviews:sync
```

Or **Utilities → Product Reviews → Sync Now** in the Control Panel.

Default schedule is daily (`PRODUCT_REVIEWS_SCHEDULE=hourly` supported). Ensure the host runs Laravel’s scheduler:

```cron
* * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
```

## Antlers

```antlers
{{ product_reviews:schema product_id="sku-123" product_name="Product Name" }}

<p>
  {{ product_reviews:average product_id="sku-123" }}/5
  ({{ product_reviews:count product_id="sku-123" }} reviews)
</p>

{{ product_reviews product_id="sku-123" limit="10" }}
  <article>
    <strong>{{ rating }}/5</strong> — {{ author_name }}
    <h3>{{ title }}</h3>
    <p>{{ body }}</p>
  </article>
{{ /product_reviews }}
```

Parameters: `product_id`, `min_rating`, `provider`, `limit`, `sort` (default `reviewed_at:desc`).

## Moderation

**Collections → Product Reviews**

- Unpublish to hide on the frontend
- Enable **Manual Override** before editing so the next sync won’t overwrite your changes

## License

Commercial / proprietary. A valid Statamic Marketplace license is required per site. See [LICENSE.md](LICENSE.md).

## Support

Email: adrianlimws@gmail.com

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
