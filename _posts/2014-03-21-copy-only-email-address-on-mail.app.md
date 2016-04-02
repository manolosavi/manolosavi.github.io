---
layout: post
title: "Copy only email address on Mail.app"
date: 2014-03-21
---

Copy only email address on Mail.app

I was browsing [Favstar](http://favstar.fm) and I came across a tweet from @[ws](https://twitter.com/ws/) that caught my eye. This is something you’re probably going to love. You know how on Mail.app when you right-click on a sender’s name to copy the address and it copies “Name \<address\>”? Well you can make it so that it copies only the address. Here’s the terminal command:


```
defaults write com.apple.mail AddressesIncludeNameOnPasteboard -bool NO

```


[Source](https://twitter.com/ws/status/208680300807598081).