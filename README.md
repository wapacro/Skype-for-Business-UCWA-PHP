# Skype for Business UCWA PHP
## About
### What is Skype for Business?
Skype for Business is a business application, which allows companys to fully support UCC (Unified communication).
Employees are able to chat, phone, do video conferencing or screen sharing with each other and - depending on the configuration - with external contacts.
### What is *(Skype for Business)* UCWA?
UCWA stands for *Unified communications web API* and allows you to use Skype for Business features just by using web requests (RESTful API).
### And why do I need *this* class?
The UCW-API is based on HTTP requests. So basically you can use any "client" you want. But all the examples and helper classes from the official site - Microsoft - are written in client-based Javascript.

That's why you need this class. It allows you to send instant messages to multiple receivers at more or less the same time. And it uses all the standards which are required for the Skype UCWA.

###### [Official UCWA site](http://ucwa.skype.com)

## How and why
### First steps
Copy the *lib/* directory and include the *base.ucwa.class.php* file wherever you want to use it.
