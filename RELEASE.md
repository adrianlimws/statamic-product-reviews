# Release v1.2.0

## Pre-flight

- [x] CHANGELOG updated
- [x] README updated
- [x] Tests passing (`17` tests)
- [ ] Commit and push (commands below)
- [ ] Tag `v1.2.0` and push tag
- [ ] Packagist auto-updates from tag (verify at packagist.org)

## Commit & tag

Run from the addon repo root:

```bash
cd addons/brainjuredstudio/product-reviews

git add -A
git status

git commit -m "$(cat <<'EOF'
Release 1.2.0: drop-in partial, photos, pagination, and queued CP sync.

EOF
)"

git push origin main

git tag -a v1.2.0 -m "v1.2.0"
git push origin v1.2.0
```

Optional GitHub release (requires `gh` CLI):

```bash
gh release create v1.2.0 --title "v1.2.0" --notes-file CHANGELOG.md
```

## Marketplace screenshots

Demo URL: `https://statamicaddons.test/reviews` (local) or your deployed `/reviews` route.

| Screenshot | Crop |
|---|---|
| **Hero listing** | Announcement bar → gallery + buy box (shows stars on PDP) |
| **Reviews** | “Customer reviews” section through all review cards |
| **CP utility** | Utilities → Product Reviews (sync status + Test Connection) |
| **Collection** | Collections → Product Reviews entries |

Recommended hero crop: **1440×810** (16:9). Hide “Addon utility” link before capture.

## What’s in 1.2.0

- Publishable `partial:product-reviews/list` (stars, schema, photos, pagination)
- `show_header="false"` when the product page already shows ratings
- Review photos from Yotpo `images_data`
- Tag pagination (`page`, `offset`)
- CP sync queued (no CP timeout on slow Yotpo)
- New tests for tags and Yotpo client
