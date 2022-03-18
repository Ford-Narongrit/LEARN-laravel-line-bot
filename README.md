# Laravel Line Bot

## Build Setup

```bash
$ composer install

$ cp .env.example .env

$ php artisan serve
$ ./ngrok http 8000
```

### Config .env
นำ url จาก ngrok ไปใส่ใน webhook ของ line ใน https://developers.line.biz/console
แล้วนำค่าจาก developer ไป config ใน .env
