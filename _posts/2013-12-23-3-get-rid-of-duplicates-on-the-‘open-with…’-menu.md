---
layout: post
title: "Get rid of duplicates on the ‘Open With…’ menu"
date: 2014-12-23
---

Getting rid of duplicates on the ‘Open With…’ menu is pretty simple, all you need to do is open the Terminal and enter the following commands:

	/System/Library/Frameworks/CoreServices.framework/Frameworks/LaunchServices.framework/Support/lsregister -kill -r -domain local -domain system -domain user

<!---->

	killall Finder

That’s all you need to do…although the duplicates might reappear after a while, so you’ll have to repeat the process.