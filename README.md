
# Behat contexts for contributed modules for Drupal 8


## Getting started

1. Run `git clone gitlab@cheppers.com:drupal/drupal-behat-contexts.git`
1. Run `cd drupal-behat-contexts`
1. Run `composer install`
1. Run `composer run drupal:install`
1. Run `cd docroot && php -S localhost:8888 .ht.router.php`
1. Run `cd tests/behat && phpdbg -qrr ../../bin/behat --config=behat.yml --strict --colors --verbose`
