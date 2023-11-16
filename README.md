
# Behat contexts for contributed modules for Drupal 8


## Getting started

1. Run `git clone https://github.com/Cheppers/drupal-behat-contexts.git`
1. Run `cd drupal-behat-contexts`
1. Run `composer install`
1. Run `composer run drupal:install`
1. Run `chrome --headless --remote-debugging-port=9222`
1. Run `cd tests/fixtures/project_01/docroot && php -S localhost:8888 .ht.router.php`
1. Run `cd "$(git rev-parse --show-toplevel)"`
1. Run `cd tests/behat && phpdbg -qrr ../../bin/behat --config=behat.yml --strict --colors --verbose`
