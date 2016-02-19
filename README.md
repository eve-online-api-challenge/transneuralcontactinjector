# transneuralcontactinjector
A contest submission for the EvE Online CREST API Challenge

This tool is hosted at: https://www.wowreports.com/charcopy/index.php?p=main

The code behind this tool (with minor sanitization of IDs/CREST secret key/DB connections, etc.) has been posted to this Git. 
A user intending to run this tool on their own web server would need to create a local MySQL database wuth the database tables
provided in the .sql file. The user would also need to register an application with EvE CREST.

>> This tool is a contact list manager with initial functionality to mirror contacts between characters. <<

The tool works by having a user login with multiple EvE Capsuleers (characters in the EvE Online MMORPG). The contact lists 
for each of the capsuleers are saved to the server-side database. The users are presented with a grid showing their contacts
across their capsuleers. The user then picks which of their capsuleers is their primary one. They can then do an operation that
automatically copies the primary capsuleer's contacts to the other registered contacts. The merge operation currently wipes the
destination capsuleer's contacts and replaces with the primary capsuleer's contacts.

Because of the timeframe given for the API Challenge, some liberties have been taken with the coding to meet the deadline. 
Necessary features to complete this tool include:
- Establishing best practice for connecting to CREST and using paths.
- Tracking cache timers and only calling refresh tokens when necessary.
- Making the copy operation more efficient and have failsafes due to CREST rate limiting / server-side issues.
--- If a contact exists already in two capsuleers, no point in deleting and adding.

Upcoming features to enhance this tool include:
- Fine grain control of contacts- the ability to add/edit/delete single contacts.
- Backup / Restore contacts- the ability to download contacts in a CSV and restore if needed. 
--- (this feature could be fantastic for Corp Leaders distributing required contacts to members)
- Finer control over the merge operation: Merge only Alliances, Corps, or Characters (or any combination)
- Combine contacts operation (Merge two or more contact lists instead of replacing one with primary).

>> In theory this same functionality can be applied to Fittings and some consideration of that will be made.
