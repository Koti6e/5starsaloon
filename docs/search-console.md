# Google Search Console setup

The public sitemap is generated from active public pages and services at `/sitemap.xml`; `robots.txt` advertises that sitemap. After authorization, the following commands use Google's Search Console APIs:

```sh
php artisan seo:gsc-submit-sitemap
php artisan seo:gsc-performance --days=28
php artisan seo:gsc-inspect https://5star.sushako.in/
```

One-time owner work is required: verify the `https://5star.sushako.in/` property in Search Console (Google may require a DNS TXT record or HTML verification), create an OAuth client, authorize a Google account that has access to the property for the `webmasters` scope, and provide its refresh token through the deployment secret store. Configure `GOOGLE_SEARCH_CONSOLE_SITE_URL`, `GOOGLE_SEARCH_CONSOLE_CLIENT_ID`, `GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET`, and `GOOGLE_SEARCH_CONSOLE_REFRESH_TOKEN`. Do not put these secrets in source control.

Sitemap submission and index inspection require that the authorized account can access the site property. Google controls crawl and index decisions; sitemap submission does not guarantee indexing. Search Analytics data is delayed by Google and may be incomplete. The commands report API responses but do not promise crawl coverage or rankings.
