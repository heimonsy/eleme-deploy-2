## eleme deploy version 2

### requirements
```
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

