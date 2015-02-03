## eleme deploy version 2

### requirements
```
npm install -g react-tools
apt-get install extract
apt-get install node
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

