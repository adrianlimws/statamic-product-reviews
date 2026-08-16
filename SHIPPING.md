# Shipping checklist — Product Reviews

Do these in order. Core addon code is ready; the steps below are publish plumbing.

## 1. Own GitHub repo (addon only)

Create a repo (e.g. `brainjuredstudio/statamic-product-reviews`) containing **only** this addon directory.

From this folder:

```bash
cd addons/brainjuredstudio/product-reviews
git init
git add .
git commit -m "Initial release of Product Reviews for Statamic 6"
git branch -M main
git remote add origin git@github.com:YOUR_ORG/statamic-product-reviews.git
git push -u origin main
```

Do **not** push the parent Statamic site, `.env`, or this addon's `vendor/`.

## 2. Packagist

1. https://packagist.org → Submit package
2. Paste the GitHub repo URL for `brainjuredstudio/product-reviews`
3. Enable GitHub Service Hook / auto-update
4. Confirm package name matches: `brainjuredstudio/product-reviews`

## 3. First release tag

```bash
git tag v1.0.0
git push origin v1.0.0
```

Update `CHANGELOG.md`: set `[1.0.0]` date, clear Unreleased if empty.

## 4. Statamic seller / Marketplace

Per https://statamic.dev/addons/building-an-addon and https://statamic.com/creator/begin:

1. Create / open seller shop
2. Connect GitHub + Stripe (required to charge)
3. Create product → link Packagist package
4. Set edition handle to `standard` (matches `composer.json` `extra.statamic.editions`)
5. Price (research target: ~$49–99 one-time)
6. Screenshots: CP utility, collection entries, frontend Antlers output
7. Marketplace description + support email
8. Keep as **draft**, preview, then publish

## 5. Smoke test as a customer

On a clean Statamic 6 site (not this path repo):

```bash
composer require brainjuredstudio/product-reviews
php artisan vendor:publish --tag=product-reviews-config
php artisan vendor:publish --tag=product-reviews-content
```

Add Yotpo env keys → sync → render tags.

## Done when

- [ ] Public GitHub repo
- [ ] Packagist listing installs cleanly
- [ ] `v1.0.0` tagged
- [ ] Marketplace product published (or draft approved for launch)
- [ ] Fresh-site install smoke test passed
