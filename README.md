# Skype for Business UCWA PHP
## 1. About
### 1.1 What is Skype for Business?
Skype for Business is a business application, which allows companys to fully support UCC (Unified communication).
Employees are able to chat, phone, do video conferencing or screen sharing with each other and - depending on the configuration - with external contacts.
### 1.2 What is *(Skype for Business)* UCWA?
UCWA stands for *Unified communications web API* and allows you to use Skype for Business features just by using web requests (RESTful API).
### 1.3 And why do I need *this* class?
The UCW-API is based on HTTP requests. So basically you can use any "client" you want. But all the examples and helper classes from the official site - Microsoft - are written in client-based Javascript.

That's why you need this class. It allows you to send instant messages to multiple receivers at more or less the same time. And it uses all the standards which are required for the Skype UCWA.

###### [Official UCWA site](https://ucwa.skype.com)

## 2 How and why
### 2.1 First steps
Copy the *lib/* directory and include the *base.ucwa.class.php* file wherever you want to use the class.
```
<?php
  require( "lib/base.ucwa.class.php" );
?>
```
This will load two classes, which you'll be able to use. On one hand `UCWA_init`, on the other hand `UCWA_use`.

### 2.2 Initialize `UCWA_init`
`UCWA_init` is used to do basic things like Autodiscover and authentification ([More information](https://ucwa.skype.com/documentation/GettingStarted-RootURL)).
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
?>
```
The constructor of `UCWA_init` requires your app's [FQDN (full qualified domain name)](https://en.wikipedia.org/wiki/Fully_qualified_domain_name) as the first and last parameter. Your FQDN has to be allowed on the Skype for Business- or Lync-Server. **Please note,** that your domain with *http://* **is not** the same as with *https://*. 
