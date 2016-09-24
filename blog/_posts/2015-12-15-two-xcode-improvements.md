---
layout: post
title: "Two Xcode improvements"
date: 2015-12-15
---

Here's two quick settings you can change on Xcode, one's pretty minor, but the second one could be a big time saver. This first one will show you the build time on the activity bar, to enable it just enter this into the Terminal.

	defaults write com.apple.dt.Xcode ShowBuildOperationDuration YES

To disable it, just change `YES` to `NO`.

The second one is to enable Xcode to use all cores available to your machine.

	defaults write com.apple.dt.Xcode IDEBuildOperationMaxNumberOfConcurrentCompileTasks `sysctl -n hw.ncpu` 

That last part specifies how many cores your machine has, for example, in my 15" Retina MacBook Pro [quad core i7], since it has hyperthreading it, it returns 8.

Thanks to @[merowing_](http://twitter.com/merowing_) for [both of](https://twitter.com/merowing_/status/675756835908009984) [these tips](https://twitter.com/merowing_/status/675756972340322304)!