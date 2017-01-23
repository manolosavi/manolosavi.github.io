---
layout: post
title: "Delete unavailable Xcode Simulators"
date: 2017-02-23
---

Doing this will have varied results depending on your luck, but it's definitely worth a try, it might give you back a couple of GBs of space.

	xcrun simctl delete unavailable

Thanks to @[aaronash](https://twitter.com/aaronash/status/832593421546188800) for the tip.