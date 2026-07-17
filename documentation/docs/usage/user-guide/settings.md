---
sidebar_position: 6
title: Settings
---

# Settings

The Settings page is where you can adjust preferences for your account. You can access it by selecting "Settings" from the top right user menu.

## General tab
From here you can select a language, adjust the display settings, change the keyboard shortcuts, modify the PDF settings, select a different text editor and set the default permission settings.

## Account tab
This page allows you to modify your email/password, enable multi-factor authentication and change your name or add your `ORCID <https://orcid.org/>`_ if you have one.

## What is two factor authentication?
Multi-factor authentication, MFA (or Two-factor authentication, 2FA) is a mechanism to further protect your account. After logging in with your password, you will need to enter a 6 digits code that changes every 30 seconds. This code will be displayed by a special application on your cellphone. If you have never used such a mechanism, you need to first install a 2FA application on your phone.

* For Android phones, the recommended application is `Aegis <https://getaegis.app/>`_ (Open Source).
* For iPhone, you can use `Authy <https://authy.com/download/>`_ (Proprietary but with good features).

If you already have a 2FA application, eLabFTW can work with it: you don't need to install another application.

Once this application is installed, on the eLabFTW page, select YES to "Use two-factor authentication?" and click Save. You will then be presented with a QR code, scan it with the application on your phone and enter the code. That's it, now your account is secured with multi-factor authentication.

**Note**: it is highly recommended to enable 2FA wherever you can.

## Api keys tab
Create an API key for your account from this page. An API key is like a Username+password for your account. It allows you to interact with eLabFTW programmatically, through the REST API. See [API documentation](../api).
