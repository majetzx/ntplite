# ntplite

A basic SNTPv4 PHP implementation

## Description
This class (`NTPLite`) is a very simple, full PHP implementation of the SNTP protocol version 4.

Some features described in RFC 4330 are not available but it can be used to read and write SNTP messages, to implement basic client and server (two sample scripts are provided).

The sample server does not perform any computation nor verification (authentication, delay, etc.).

See [RFC 4330](http://tools.ietf.org/html/rfc4330) and [RFC 5905](http://tools.ietf.org/html/rfc5905) for further details on SNTP and NTP protocols.


https://github.com/majetzx/ntplite