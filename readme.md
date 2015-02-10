## eleme deploy version 2


### requirements
```
apt-get install nodejs nodejs-legacy
apt-get install npm
npm install -g react-tools
apt-get install extract
```

### config

```
cp .env.sample.php .env.php
```

edit `.env.php`


### create tables
```
php artisan migrate
```

