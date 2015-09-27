# Potato
Potato is an add-on to FreeRADIUS to provide One-Time-Password (OTP) authentication to all radius clients. The server side provides the following features:

A simple web front end to allow users to register their own tokens
A simple administrative front end for sysadmins
Potato aims to be as small and simple as possible. It does not maintain a user/password database of its own, but instead depends on that functionality through existing directory systems via PAM.

This means that you must have fully functional NSS and auth on the Linux side before Potato can be used in your environment. On RedHat this is most easily achieved via SSSD.

Potato is free software licensed under the Apache Public License v2.

## Database pruning
If the log table gets too full, you can move all old entries to an archive table. Or simply trim old entries.

mysql> create table Log2014 like Log;
mysql> insert into Log2014 select * from Log where `time`<'2015-01-01';
mysql> delete from Log where `time`<'2015-01-01';

