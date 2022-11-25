# SICC

Simple Item Crate Controller - Backend service

## Why ?

This project was created to help the logistical management of small events through their inventory.

It works by printing QR code generated in-app on physical crates. The SICC Enrollment Protocol allows anyone which have physical access to those crates and owns [SICC-App](https://github.com/XVanilor/sicc-app) to interact with them and managing its content

However, it is not magical: Here's how it handles data and process SEP enrollment.

## Getting Started

First, install a web server such as NGINX or Apache. There are plenty of tutorials on the internet about how to do it.

Second, once your web server is configured, install [Composer for PHP](https://getcomposer.org/download/) and run `composer install` on the project's root.

Third and then, access to `https://sicc.yourdomain.net/install.php` and save the info displayed: Your API token will be used to configure your [SICC App](https://github.com/XVanilor/sicc) and you will give the PIN code to permit other members to join and manage your SICC inventory. It will be otherwise available in the mobile application.

You're now ready to configure your [mobile app (iOS, Android)](https://github.com/XVanilor/sicc-app)!