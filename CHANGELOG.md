# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - TBD

### Added

- Yotpo review sync into a local `product_reviews` collection
- Artisan/Please command `product-reviews:sync` and queued `SyncReviewsJob`
- Scheduled sync (daily by default)
- Control Panel utility for connection status and manual sync
- Antlers tags: `product_reviews`, `:average`, `:count`, `:schema`
- Schema.org Product / AggregateRating / Review JSON-LD helper
- Manual override so CP edits are not overwritten on sync
- Thin `ReviewProvider` interface for future platforms
