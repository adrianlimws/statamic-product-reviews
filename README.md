# Product Reviews

**Statamic 6 addon** that syncs product reviews from **Yotpo** into a local collection. Reviews stay on your server (no client-side Yotpo widgets), are editable in the Control Panel, and render with Antlers plus Schema.org JSON-LD.

> Provider today: **Yotpo**. Architecture supports additional platforms later.

## Features

- Pull Yotpo reviews via REST API into a `product_reviews` collection
- Automatic CDN fallback when the Core API times out
- Scheduled and on-demand sync (down to every five minutes)
- Manual override so CP edits are not overwritten
- Antlers tags for listing, averages, and counts
- Schema.org Product / AggregateRating / Review markup
- CP utility for connection test, status, and “Sync now”

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
PRODUCT_REVIEWS_SCHEDULE=hourly
PRODUCT_REVIEWS_YOTPO_SOURCE=auto
PRODUCT_REVIEWS_YOTPO_PRODUCT_IDS=sku-123,sku-456
PRODUCT_REVIEWS_YOTPO_INCLUDE_SITE_REVIEWS=true
PRODUCT_REVIEWS_YOTPO_TIMEOUT=60
PRODUCT_REVIEWS_YOTPO_CONNECT_TIMEOUT=15
```

| Variable | Values | Default |
|---|---|---|
| `PRODUCT_REVIEWS_SCHEDULE` | `daily`, `twice_daily`, `hourly`, `every_thirty_minutes`, `every_fifteen_minutes`, `every_five_minutes` | `daily` |
| `PRODUCT_REVIEWS_YOTPO_SOURCE` | `auto` (Core then CDN), `core`, `cdn` | `auto` |
| `PRODUCT_REVIEWS_YOTPO_PRODUCT_IDS` | Comma-separated storefront product IDs / SKUs for CDN sync | _(empty)_ |

Yotpo credentials: **Settings → Store Settings → API Credentials**.

### Product ID matching

`product_id` on synced entries must match the ID you use in Antlers — typically your **storefront SKU / external product ID** (Shopify product/variant ID or SKU), **not** Yotpo’s internal numeric ID.

```antlers
{{ product_reviews product_id="{{ sku }}" }}
```

If Core API syncs fine, SKUs usually land correctly. For CDN-only or CDN fallback syncs, set:

```env
PRODUCT_REVIEWS_YOTPO_PRODUCT_IDS=your-sku-1,your-sku-2
```

Those values should be the same IDs Yotpo knows as the product’s `domain_key` / external ID. After the first sync, existing entry `product_id` values are reused automatically on later CDN runs.

If reviews sync but the frontend shows zero, compare **Collections → Product Reviews → product_id** with the `product_id=` value in your template.

## Sync

```bash
php please product-reviews:sync
# or
php artisan product-reviews:sync
```

Or **Utilities → Product Reviews → Sync Now** in the Control Panel. Use **Test Connection** to verify credentials without a full sync.

Default schedule is daily. Ensure the host runs Laravel’s scheduler:

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

Email: brainjuredstudio@gmail.com

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
