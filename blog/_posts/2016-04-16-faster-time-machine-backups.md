---
layout: post
title: "Faster Time Machine Backups"
date: 2016-04-16
---

Recently came across [this post](http://www.mackungfu.org/massively-speed-up-time-capsule-time-machine-backups) on twitter and I thought it was a pretty solid tip, so here it is. To disable the default throttling that causes backups to be slow use this terminal command:

	sudo sysctl debug.lowpri_throttle_enabled=0

The problem is that this setting is forgotten every time you reboot, so to fix that you gotta add a script that runs when you boot up your computer, so with this command you'll make the script file and open it in an editor on your terminal:

	sudo nano /Library/LaunchDaemons/nothrottle.plist

Then paste the following text into that file:

	<?xml version="1.0" encoding="UTF-8"?>
	<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
	<plist version="1.0">
	<dict>
		<key>Label</key>
		<string>nothrottle</string>
		<key>ProgramArguments</key>
		<array>
			<string>/usr/sbin/sysctl</string>
			<string>debug.lowpri_throttle_enabled=0</string>
		</array>
		<key>RunAtLoad</key>
		<true/>
	</dict>
	</plist>

After you've pasted the text into the file, to exit back to the terminal use ``⌃O`` then hit ``enter`` and finally ``⌃X``. Then use the following command to enable the script to run when your computer boots:

	sudo chown root /Library/LaunchDaemons/nothrottle.plist;sudo launchctl load /Library/LaunchDaemons/nothrottle.plist

If in the future you want to undo these changes, all you need to do is use the following command and reboot afterwards.

	sudo launchctl unload -w /Library/LaunchDaemons/nothrottle.plist