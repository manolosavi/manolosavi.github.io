---
layout: post
title: "Enable copying text from Quick Look"
date: 2014-12-23
---

Enable copying text from Quick Look

This is a very useful tip that will allow you to copy text from any Quick Look plug-in. Just enter the following commands on the Terminal:

```
defaults write com.apple.finder QLEnableTextSelection -bool TRUE
```

```
killall Finder
```

Hat tip to [Craig Hockenberry](https://twitter.com/chockenberry/) for this one. And since we're talking about Quick Look and Craig already, you should check out his awesome plug-in to see provisioning profile's information:

[A Quick Look plug-in for Provisioning](http://furbo.org/2013/11/02/a-quick-look-plug-in-for-provisioning/).