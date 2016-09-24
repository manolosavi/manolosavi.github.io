---
layout: post
title: "Show hidden files on Finder"
date: 2014-02-26
---

This is a simple one that you probably already know, but I always forget how exactly I’m supposed to write it, so here it is.

Show hidden files:

	defaults write com.apple.finder AppleShowAllFiles 1 && killall Finder

Hide them:

	defaults write com.apple.finder AppleShowAllFiles 0 && killall Finder