How to set up GitHub integration
================================

I. 	Setup your vManager installation -------------------------------------------

1) Prepare DB structure by running /dbdump/dbdump.github.sql script
2) Add following lines to your /config/config.neon under parameters section:
	GitHub:
		enabled: true
		
3) Add following lines into your /config/local.neon under parameters section:
	GitHub:
		securityToken: mySuperSecretToken123
		
II. Setup your GitHub repositories ---------------------------------------------	

1) Log into your github account and go to the page of your repository
2) Click on "Admin" button and locate section "Service Hooks"
3) From "Available service hooks" menu select the "Post-Receive URLs"
4) Add new url in format https://<your-domain.tld>/github-push/<your-secret-token>
	(example: https://v3net.vmanager.cz/github-push/mySuperSecretToken123)
	
	HTTPs is advised but not required.
	
Procedure above can be applied to as many repositories as you wish.

III. Notes ---------------------------------------------------------------------

It seems that for some strange reason GitHub won't run their hooks if it
receives push with commits dated in the future. It's untested bug,
but it seems that way. So please keep your dev machine's clock in sync
for best experience.