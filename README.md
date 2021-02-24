# TITO-CMS

Tito-CMS adalah markdown editor untuk HUGO. Tito-CMS berfungsi sebagai editor online, di mana file hasil editing akan digunakan sebagai content dalam HUGO (digenerate ke direktori konten). Ketika sebuah berkas diedit, maka HUGO-CLI akan dijalankan untuk menggenare menjadi sebuah webstatis.

## Instalasi

- clone degan perintah git clone git@github.com:titounnes/tito-cms.git
- masuk ke folder tito-cms:  cd tito-cms 
- edit berkas config.app (opsional untuk mengubah pengaturan user administrator)
- jalankan perintah php serve (Aplikasi akan melakukan instalasi sesuai dengan konfigurasi yang kita tentukan)
- aplikasi ini menggunakan markdown.js CDN sehingga diperlukan koneksi internet

## Credit
- [Markdown.js](https://github.com/evilstreak/markdown-js/) 