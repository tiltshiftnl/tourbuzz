# Tour Buzz

Dagelijks het laatste nieuws voor touringcar chauffeurs.

## Deployment

Work in progress

* sudo cp -r -p live_versions/release_1/ live_versions/release_2
* live_versions/release_2/sudo su www-admin
* live_versions/release_2/git pull origin master
* live_versions/release_2/more README.md
* Follow instructions
* live_versions/release_2/tourbuzz/composer install
* live_versions/release_2/api/composer install
* Make sites available
* mv live live_backup
* ln -s live_versions/release_2 live

## Notes

Work in progress

crontab -l
