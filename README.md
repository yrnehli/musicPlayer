# Henry's Music Player
A music player built on the LAMP stack which can play local MP3 files and stream music from Deezer.

<p align="center">
    <img src="https://user-images.githubusercontent.com/44710606/168400496-d2fd5397-36e7-4ccf-9dd8-94574f0a3334.png" />
</p>

# Requirements
- PHP 7.2
- MySQL 8
- Apache

*Other versions of PHP may work, but is not guaranteed.*

# Installation

1. Clone the repo:
```bash
git clone https://github.com/henryli17/musicPlayer.git
```

2. Install dependencies using Composer:
```bash
cd musicPlayer && composer install
```

3. Import `database.sql` to a schema in your MySQL database.

4. Setup environment variables.
- Make a copy of `.env.example` and rename it to `.env`.
- Populate the environment variables

| Key | Value | Required |
| ----------- | ----------- | ----------- |
| `DB_SERVERNAME` | The server name or IP of your MySQL database. | Yes |
| `DB_USERNAME` | The username for your MySQL database. | Yes |
| `DB_PASSWORD` | The password for the MySQL user defined in `DB_USERNAME`. | Yes |
| `DB_DBNAME` | The name of the schema you imported `database.sql` to. | Yes |
| `MUSIC_DIRECTORY` | The full directory of where your local MP3 files are stored. | No |
| `SPOTIFY_SP_DC` | Your Spotify `sp_dc` cookie obtained from logging into Spotify's web player. | No |
| `DEEZER_ARL` | Your Deezer `arl` cookie obtained from logging into Deezer's web player. | No |
| `LASTFM_API_KEY` | Your Last.fm API key. | No |
| `LASTFM_API_SECRET` | Your Last.fm API secret. | No |
| `LASTFM_SESSION_KEY` | Your Last.fm session key. | No |
| `LASTFM_USERNAME` | Your Last.fm username. | No |

5. Setup an Apache VirtualHost for the repo. Example:
```
<VirtualHost *:8080>
	ServerName localhost
	DocumentRoot /mnt/c/Users/henry/Repos/Personal/musicPlayer

	<Directory /mnt/c/Users/henry/Repos/Personal/musicPlayer>
		Options Indexes FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>

	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

6. Call the update endpoint to populate the database with information about your local MP3 files. Example:
```bash
curl http://localhost:8080/api/update
```

You should receive a JSON response that looks like this:
```json
{
	"success": true,
	"message": "",
	"data": []
}
```

7. You should now be able to access the music player by browsing to `http://localhost:8080`.

# Hotkeys

| Hotkey | Action |
| ----------- | ----------- |
| `Esc` | Home Page |
| `Meta + F` | Search |
| `Meta + D` | Saved Songs |
| `Meta + S` | Shuffle All Songs |
| `Meta + W` | Last.fm “Wrapped” |

Where `Meta` is either `Ctrl` or `Cmd (⌘)`.

# Features
- Search and stream from Deezer by prefixing your search query with `e: `, for example `e: 24K Magic`.
- Save songs from Deezer by pressing the heart icon on the track.
- Export your saved songs to your Spotify liked songs by using the `Spotify Export` button on the `Saved Songs` page `(Ctrl + D)`.
- Automatically scrobbles songs to Last.fm when a track finishes playing.

# PWA Support
This can be installed as a Progressive Web App (PWA). For the best experience, enable the following flags in your Chromium based browser.
- `#enable-desktop-pwas-remove-status-bar`
- `#enable-desktop-pwas-window-controls-overlay`

# Troubleshooting
In the case that images take a long time to load, try turning Apache's `KeepAlive` directive to `Off`.

# Disclaimer
This repository is for educational/research purposes only, the use of this code is your responsibility.

I take no responsibility and/or liability for how you choose to use any of the source code available here. By using any of the files available in this repository, you understand that you are agreeing to use at your own risk. Once again, all files available here are for education and/or research purposes only.
