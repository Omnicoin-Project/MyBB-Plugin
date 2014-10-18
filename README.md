MyBB-Plugin
===========

A plugin to integrate Omnicoin into MyBB forums.

Project currently under development. The [Omnicoin team](https://github.com/Omnicoin-Project/Omnicoin/wiki/Omnicoin-Team) are working on this project along with [Storm-](http://www.hackforums.net/member.php?action=profile&uid=2206336) who volunteered to provide his experience with MyBB plugins.

An omnicha.in API will be used to verify addresses added to each profile. The address will then be displayed on the profile, with a list of past addresses (which also helps prevent scammers). A "balance" field will be added to the member information on each post.


Once bug and security testing is complete, this plugin may be added to HackForums.net and other major forums will be approached, in order to expand the omnicoin community.

We hope to eventually complete full integration into the forum, including tipping systems, automated upgrade and sticky purchases, etc. But these developments are fully under the disgression of [Omniscient](http://www.hackforums.net/member.php?action=profile&uid=1), and potential security risks related to stored balances/private keys must be considered and solved before these further developments can take place.

Verify Address Ownership API Call: https://omnicha.in/api?method=verifymessage&address=ADDRESS&message=MESSAGE&signature=SIGNATURE

Verify Address API Call: https://omnicha.in/api?method=checkaddress&address=ADDRESS

Get Address Balance Call: https://omnicha.in/api?method=getbalance&address=ADDRESS

We would also like to add some omnicoin address search ability, but that may be developed later

Contributors:
- [MeshCollider]
- [Abraham Lincoln]
- [.Matt](http://www.hackforums.net/member.php?action=profile&uid=1354902)
