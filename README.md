Download-youtube-video
======================

A script for fetching the link to video files of youtube

Script fetchYoutube.php done all work for getting the links for videos
and output as JSON object

It also support for fetching all video in a playlist
For getting single video pass url parameter with value as youtube link

```
fetchYoutube.php?url=https://www.youtube.com/watch?v=2gLq4Ze0Jq4
```
For getting the the whole playlist  you can use id of playlist with list parameter
```
fetchYoutube.php?url=https://www.youtube.com/playlist?list=PLhBgTdAWkxeCrJj7-ld9RTYBjH7FrClrs
```
or pass video id of any video of playlist , playlist id and all parameter
```
fetchYoutube.php?url=https://www.youtube.com/watch?v=9moAdEslwkg&list=PLhBgTdAWkxeCrJj7-ld9RTYBjH7FrClrs&all=true
```
