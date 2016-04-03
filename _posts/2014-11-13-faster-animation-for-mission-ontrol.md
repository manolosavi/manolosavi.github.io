---
layout: post
title: "Faster animation for Mission Control"
date: 2014-11-13
---

Faster animation for Mission Control

If you feel like you're wasting too much time thanks to Mission Control's animations, use these commands on the Terminal to speed it up:

	defaults write com.apple.dock expose-animation-duration -float 0.2

<!---->

	killall Dock

You can change the 0.2 value to whatever you like best, and if you want to return it to the original speed, just use these commands instead:

	defaults delete com.apple.dock expose-animation-duration

<!---->

	killall Dock

Thanks to @[bdc](http://twitter.com/bdc) for the tip!