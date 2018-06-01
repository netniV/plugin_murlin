mURLin
------
Cacti URL Monitoring Plugin
v1.0.0
------
- Made plugin Cacti 1.x compatible

v0.2.4
------
- Added 'id' command to get the IDs of a hostname
- Fixed indexing issue with template

v0.2.3
------
- Updated source to mirror Cacti coding style
- Updated to use global log setting to filter when to log
- Updated to use cache for availability value

v0.2.2
------
- Fixed bug with CactiEZ and the jQueryskin plugin which caused the cacti instance to display an error at the top of the page
- Updated the database to support website URLs of greater than 256. The new maximum is 2048 characters. The UI has been updated to stop URLs of greater than this being passed to the functions.

v0.2.1
------
- Fixed a regression which meant that a site which takes longer than 1 second to respond on more than one field may show incorrectly
- Fixed bug with proxy support

v0.2.0
------
- Caching of results for faster lookups
- Stacked Graphs (Detailed download times)
- Availability Graph Template
- Various bugfixes

v0.1.7
------
- Full proxy support inc authenticating proxies (Thanks to Vincent Geannin)
- Automatic Upgrade Procedure now in place

v0.1.6
------
- Fixed an issue where including sites with GET variables didn't display correctly in the previews
- Added new data source - download size
- Added current values to graph templates (last)

v0.1.5
------
- Fixed the issue where the data query was still reporting a mapping where the last mapping was removed

v0.1.4
------
- Properly fixed the scrolling problem now.... :)
- Added client side validation to the user input forms

---

Ported to GitHub by @netniV from the following sites:

Site | Url
--- | ---
Cacti Plugin | https://docs.cacti.net/userplugin:murlin
SourceForge | https://sourceforge.net/projects/murlin/

