# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-08-17

### Added

- Yotpo CDN widget fallback when Core API times out (`PRODUCT_REVIEWS_YOTPO_SOURCE=auto|core|cdn`)
- Optional CDN product ID list (`PRODUCT_REVIEWS_YOTPO_PRODUCT_IDS`) and site-reviews sync
- More sync schedules: `twice_daily`, `every_thirty_minutes`, `every_five_minutes`
- CP **Test Connection** button (auth + lightweight API check)
- Docs for matching Antlers `product_id` to Yotpo / Shopify SKUs

### Improved

- Last sync counts always visible in the CP utility
- Utility shows configured schedule and API source

## [1.0.1] - 2026-08-16

### Improved

- CP utility layout: status table, collection link, Sync Now alignment, success/error banners at the bottom
- Friendlier Yotpo timeout and gateway error messages
- Configurable HTTP timeout / connect timeout (`PRODUCT_REVIEWS_YOTPO_TIMEOUT`, `PRODUCT_REVIEWS_YOTPO_CONNECT_TIMEOUT`)
- Retry briefly on connection failures; redact app key and utoken from error output

## [1.0.0] - 2026-08-16

### Added

- Yotpo review sync into a local `product_reviews` collection
- Artisan/Please command `product-reviews:sync` and queued `SyncReviewsJob`
- Scheduled sync (daily by default)
- Control Panel utility for connection status and manual sync
- Antlers tags: `product_reviews`, `:average`, `:count`, `:schema`
- Schema.org Product / AggregateRating / Review JSON-LD helper
- Manual override so CP edits are not overwritten on sync
- Thin `ReviewProvider` interface for future platforms
