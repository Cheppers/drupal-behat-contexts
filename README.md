
# Behat contexts for contributed modules for Drupal 8


## Getting started

1. Run `git clone https://github.com/Cheppers/drupal-behat-contexts.git`
2. Run `cd drupal-behat-contexts`
3. Run `composer install`
4. Run `composer run drupal:install`
5. Run `chrome --headless --remote-debugging-port=9222`
6. Run `cd tests/fixtures/project_01/docroot && php -S localhost:8888 .ht.router.php`
   * If the port `8888` is in use, change it to another one. In this case you have to change it in `tests/behat/behat.local.yml:5` as well!
7. Run `cd "$(git rev-parse --show-toplevel)"`
8. Run `cd tests/behat && phpdbg -qrr ../../bin/behat --config=behat.yml --strict --colors --verbose`
