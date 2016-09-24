---
layout: post
title: "Fix slow wake from sleep on retina MacBook Pro"
date: 2013-12-23
---

You may have noticed that sometimes after closing the lid on the retina MacBook Pro when you open it back up it takes a bit before it can be used. This is because after a certain amount of time it turns off a bunch of stuff [like the USB bus, RAM, among others] so that the battery can last longer. But when you turn off the RAM it loses everything that was written on it, so before it’s turned off all of its data is stored on the SSD and when you try to wake it up, the SSD has to rewrite everything back on the RAM, which is why it takes a while for it to become responsive. So, to fix this all you need to do is change the delay. You can check your current settings by entering the following command on the Terminal:

	pmset -g

Look at the number next to ‘ standbydelay’, that’s your current delay setting in seconds. To change it, enter the following command followed by your password after hitting enter:

	sudo pmset -a standbydelay 86400

That is the setting that I use, 86400 seconds, meaning 24 hours, but you can choose whatever number you prefer.