# Gov Backup - Wayback Machine

Frontend presenting Polish government websites archived by ePanstwo Foundation.

# Install
## Requirements

- PHP Version: >= 7.0.0
- PHP modules:
  - sudo apt install php-curl php-mbstring php-dom php-memcached

Built on top of[Laravel](https://laravel.com/).

## Steps

1. `composer install`
2. `npm install`
3. `npm run dev` to compile web resources

App assummes the Elastic Search with data is available at `localhost:9200`.
