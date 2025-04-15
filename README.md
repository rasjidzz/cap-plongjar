# Project Laravel 12 Plotingan Pengajaran Setup Guide

Panduan lengkap untuk meng-clone dan menjalankan proyek Laravel 12 secara lokal.

Cheers ! Salam Hangat, 
Muhammad Risjad Shidqi Febian
Backend Developer
---

## Prasyarat

Pastikan kamu sudah install:

- PHP >= 8.3
- Composer
- MySQL
- Git
- Mampp/Xampp/etc

> Proyek ini menggunakan Laravel 12 berbasis REST API

---

## Clone Repository

```bash
git clone https://github.com/rasjidzz/cap-plongjar.git
cd cap-plongjar
```

## Install Dependencies Laravel (using composer)
```bash
composer install
```

## Duplikat Env dan Setting dotenv

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plongjar
DB_USERNAME=root
DB_PASSWORD=root

* silahkan sesuaikan lagi

## Generate App Key
```bash
php artisan key:generate
```

## Jalankan Migrasi & Seeder
```bash
php artisan migrate:fresh --seed
```

## Jalankan Server Lokal
```bash
php artisan serve
```


