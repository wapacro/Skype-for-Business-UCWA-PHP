# Skype for Business UCWA PHP

## Index
1. About
[1.1 What is Skype for Business?](#11-What-is-Skype-for-Business)<br>
[1.2 What is *(Skype for Business)* UCWA?](#12-what-is-skype-for-business-ucwa)<br>
[1.3 And why do I need *this* class?](#13-and-why-do-i-need-this-class)<br>
2. How and why<br>
[2.1 First steps](#21-first-steps)<br>
[2.2 Initialize `UCWA_init`](#22-initialize-ucwa_init)<br>
[2.3 Get an access token](#23-get-an-access-token)<br>
[2.4 Initialize `UCWA_use`](#24-initialize-ucwa_use)<br>
[2.5 Register application](#25-register-application)<br>
[2.6 Create conversation](#26-create-conversation)<br>
[2.7 Wait until accept](#27-wait-until-accept)<br>
[2.8 Send message](#28-send-message)<br>
[2.9 Terminate conversation](#29-terminate-conversation)<br>
[2.10 Request workflow](#210-request-workflow)<br>

<hr>
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
First of all, you have to set the Autodiscover-URL for your environment. The Autodiscover-URL usually looks like *https://lyncdiscover.yourdomain.com*. Change that URL by editing your local copy of *base.ucwa.class.php*. The variable which stores this URL is located in the upper area of the file.

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
The constructor of `UCWA_init` requires your app's [FQDN (fully qualified domain name)](https://en.wikipedia.org/wiki/Fully_qualified_domain_name) as the first and last parameter. Your FQDN has to be allowed on the Skype for Business- or Lync-Server. **Please note,** that your domain with *http://* **is not** the same as with *https://*. 

Thanks to the constructor, the `UCWA_init` class will automatically discover the required url's for the user-, application- and XFrame-source. 

### 2.3 Get an access token
Once the Autodiscover things are done, you have to request an access token for further use.
Use the method `getAccessToken( $username, $password )` to do the necessary requests.
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
?>
```
Your username depends on the server configuration. Usually, you can either use your SIP address or your internal domain followed by your NT account *(domain\user)*. The user you use for authentication will be the visible sender for your IM receivers.

### 2.4 Initialize `UCWA_use`
Basic things like Autodiscover and authentication are done, so we going to use the advanced sh**. But first of all, we need to initialize these advanced things. For that, we use `UCWA_use`.
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
  
  $im = new UCWA_use();
?>
```
In this short example, the constructor of `UCWA_use` doesn't need any parameters. But if you'd like to use a multi-file solution, you have to export the Autodiscover and authentication data by using the `UCWA_init` method `getUCWAData()`, which will return an array. Then you'll have to pass the array values to the constructor of `UCWA_use`. See the advanced example for more information.

### 2.5 Register application
The first real step with `UCWA_use` besides the constructor is to register your application. If you want to send multiple messages **at the same time** you should register your application for each conversation. This will generate an unique ID which allows you to track the state of conversation and message.
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
  
  $im = new UCWA_use();
  $im->registerApplication( "My Application" );
?>
```
You can specify whatever you want as application name (first parameter of `registerApplication()`). It won't show up in conversations or somewhere else, except for the server log.

### 2.6 Create conversation
After the registration of your application is complete, you are able to start a conversation.
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
  
  $im = new UCWA_use();
  $im->registerApplication( "My Application" );
  $im->createConversation( "sip:another.one@yourdomain.com", "Subject" );
?>
```
Just pass the receiver as first argument and the subject of this conversation as the second argument.

### 2.7 Wait until accept
The method `createConversation` in `UCWA_use` will generate a conversation invitation which the receiver has to accept. If the user ignores the invitation or is offline, the following method `waitForAccept` will return false. If the receiver is available and doesn't click "*Ignore*" within 30 seconds (depends on configuration), the conversation will automatically be accepted.
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
  
  $im = new UCWA_use();
  $im->registerApplication( "My Application" );
  $im->createConversation( "sip:another.one@yourdomain.com", "Subject" );
  
  if ( $im->waitForAccept() ) {
    // ...
  }
?>
```

### 2.8 Send message
Once the conversation has been accepted, we can finally send the message(s).
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
  
  $im = new UCWA_use();
  $im->registerApplication( "My Application" );
  $im->createConversation( "sip:another.one@yourdomain.com", "Subject" );
  
  if ( $im->waitForAccept() ) {
    $im->sendMessage( "First message!" );
  }
?>
```
Just pass your message as parameter for `sendMessage` in `UCWA_use`.
Of course you can send multiple messages by reusing the method `sendMessage`.

### 2.9 Terminate conversation
Nothing more to say? Once you have sent all your messages you should terminate the conversation. But before you are able to send the terminate command, you have to check the event channel first. We do this with the already familiar method `waitForAccept`. But this time, we use this method with an argument: *false*. It makes sure the method doesn't wait for something. Instead it will just check if the conversation has lost connection due connection problems or user interaction.
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
  
  $im = new UCWA_use();
  $im->registerApplication( "My Application" );
  $im->createConversation( "sip:another.one@yourdomain.com", "Subject" );
  
  if ( $im->waitForAccept() ) {
    $im->sendMessage( "First message!" );
    // Send more messages
    
    $im->waitForAccept( false );
  }
?>
```
Only then you're allowed to use the `terminateConversation` method. It will gracefully close the connection and the Skype for Business or Lync client may tell the receiver that the conversation has ended.
```
<?php
  require( "lib/base.ucwa.class.php" );
  $ucwa = new UCWA_init( "http://myapp.example.com" );
  $ucwa->getAccessToken( "some.user@yourdomain.com", "P@ssw0rd!" );
  
  $im = new UCWA_use();
  $im->registerApplication( "My Application" );
  $im->createConversation( "sip:another.one@yourdomain.com", "Subject" );
  
  if ( $im->waitForAccept() ) {
    $im->sendMessage( "First message!" );
    // Send more messages
    
    $im->waitForAccept( false );
    $im->terminateConversation();
  }
?>
```

### 2.10 Request workflow
![UCWA Workflow](https://raw.githubusercontent.com/wapacro/Skype-for-Business-UCWA-PHP/master/docs/img/ucwa_workflow.png)
