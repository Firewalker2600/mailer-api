# mailer-api
Symfony build REST API for asynchronous email handling following the coding challenge

# Task

Write REST API that:

1.  Accepts following parameters

    *   key – name of mail template

    *   subject – email subject

    *   id – identificator of order

    *   date – in format of YYYY-MM-DD

    *   link – formatted link object (label and url)

    *   email – target email address

    *   bcc – hidden copy email address

    *   delayed_send – false or date time

2.  Logs incoming request (either DB or log)

3.  Adds request to queue or sends immediately

4.  Fills predetermined email template with data above

5.  Sends Mail to address and bcc to second email address

6.  Responds with status code (200/202)

Example of input data

{

["key": "expiration", "delayed_send": "2022-12-22", "email": [ "](mailto:jan.samek2@icewarp.com)jan.samek2@icewarp.com"

],

["bcc": [ "](mailto:jan.samek2@icewarp.com)jan.samek2@icewarp.com"

],

"body_data": {

"id": "ABC-2022-XGF",

"date": "2022-12-24",

"link": {

"label": "icewarp.com", "url": "Go to our site"

}

}

}

Example of mail html:
![image](https://github.com/Firewalker2600/mailer-api/assets/70068719/a4437f72-b5d7-4122-b9a4-0937f9cb69b4)

EmailController handles /api/v1/send-email and validates POST requests using APIRequest.php validator
If successful, it proceeds on storing the email in the DB and returns 202 accepted messages.

In the background, a cronjob is calling MailerDispatchCommand.php that checks for any unsent messages and builds an email with the twig template, and attempts to send it.
In case of an exception, it is logged in var/log.
