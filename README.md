# OpenContent Cronjobs (occronjobs)

An Exponential extension that brings the site's cronjobs into the administration
interface. From the admin you can:

- read the server's crontab entries that belong to this installation,
- see every `runcronjobs.php` call found in that crontab, and start any of them
  on demand with a single click,
- read the output of the last run while it is still running,
- empty the output and error log files again.

It is meant for site administrators and integrators who want to check or re-run
a cronjob without opening a shell on the server, for example after an import
failed or while testing a new cronjob part.

The extension was written by OpenContent and is maintained by 7x for
Exponential 6.x.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Permissions](#permissions)
- [Security notes](#security-notes)
- [Upgrading](#upgrading)
- [Known limits](#known-limits)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Credits and history](#credits-and-history)

## Requirements

- Exponential 6.x (the legacy kernel)
- PHP 8.0 or later, with `proc_open()` allowed (it starts the PHP command line
  binary)
- A PHP command line binary on the web server (setting `PhpCliPath`)
- The admin siteaccess design chain must contain `admin2`, because the
  extension's templates live in `design/admin2`. Check that the admin
  siteaccess's `site.ini.append.php` lists it:

  ```ini
  [DesignSettings]
  AdditionalSiteDesignList[]=admin2
  ```

- jQuery in the admin pages: the crontab list starts cronjobs and refreshes the
  log with jQuery.

## Installation

### With Composer

From the root of your Exponential installation:

```bash
composer require se7enxweb/occronjobs
```

The Exponential legacy installer places the extension in `extension/occronjobs`.
If Composer cannot find the package, add the repository to the `repositories`
list of your `composer.json` first:

```json
{
    "type": "vcs",
    "url": "https://github.com/se7enxweb/occronjobs"
}
```

### By hand

1. Download a release from https://github.com/se7enxweb/occronjobs/releases, or
   clone the repository:

   ```bash
   git clone https://github.com/se7enxweb/occronjobs.git extension/occronjobs
   ```

   The directory must be called `occronjobs`.

2. Activate the extension in `settings/override/site.ini.append.php`:

   ```ini
   [ExtensionSettings]
   ActiveExtensions[]=occronjobs
   ```

3. Regenerate the autoload array, so the classes `CronTabReader`,
   `OCCronJobProcess` and `OCCronJobProcessManager` are found:

   ```bash
   php bin/php/ezpgenerateautoloads.php -e
   ```

4. Clear the caches:

   ```bash
   php bin/php/ezcache.php --clear-all
   ```

5. Give the crontab viewer a copy of your crontab (see `CrontabPath` below):

   ```bash
   crontab -l > extension/occronjobs/settings/crontab.txt
   ```

## Configuration

All settings are in `extension/occronjobs/settings/occronjobs.ini`. Override them
in `settings/override/occronjobs.ini.append.php` rather than editing the file.

```ini
[ProcessSettings]
# The PHP command line binary that runs runcronjobs.php
PhpCliPath=php
# Seconds to wait after starting each script
SleepTime=2
# Stored with each started script; not enforced (see Known limits)
MaxExecutionTime=3600
# Where the output of a started cronjob is written, relative to the
# document root. Both standard output and errors go to OutputFile.
OutputFile=var/log/process_output.log
# Shown and emptied by the log views
ErrorFile=var/log/process_error.log

[Forbidden]
# Cronjob parts (cronjob.ini [CronjobPart-<name>]) that may not be listed or
# started from the admin. "global" stands for the parts-less run.
Parts[]
Parts[]=cluster_maintenance
Parts[]=unlock
Parts[]=infrequent
Parts[]=global
# Crontab entries (the arguments after runcronjobs.php) that the crontab list
# leaves out
Scripts[]
Scripts[]=check_consistency

[GeneralSettings]
# A text copy of the crontab that the viewer reads. The web server user usually
# cannot read the real crontab, so keep a copy here up to date.
CrontabPath=extension/occronjobs/settings/crontab.txt

[CronOptions]
# Extra fields shown above the crontab list. PreOption[] go before the part as
# --name or --name=value, PostOption[] after it as name=value.
# Types: boolean (a checkbox), date (a text field), anything else (a text field).
PostOption[from]=date
PostOption[to]=date
PreOption[debug]=boolean
```

A crontab entry is shown only when:

- it is not a comment,
- it contains the document root of the installation (`$_SERVER['DOCUMENT_ROOT']`),
- if it names a siteaccess with `-s`, that siteaccess is one of
  `site.ini [SiteAccessSettings] RelatedSiteAccessList[]`.

The crontab list additionally needs `runcronjobs.php` in the entry. An example
crontab copy that both views understand:

```
*/5 * * * * cd /var/www/example/public && php runcronjobs.php -q -s site frequent > /dev/null 2>&1
0 3 * * *   cd /var/www/example/public && php runcronjobs.php -q -s site > /dev/null 2>&1
```

## Usage

### The admin tab

The extension defines an admin tab, **Cronjobs**, with the clock icon in the
admin4 design (`Icon=clock` in `settings/menu.ini.append.php`). The tab is not
added to the top menu by default. To show it, add it in
`settings/override/menu.ini.append.php`:

```ini
[TopAdminMenu]
Tabs[]=occronjobs
```

Without the tab the pages are reached through the **Crontab viewer** link the
extension adds to the Setup menu (`[Leftmenu_setup]`).

### Pages

| URL | Menu entry | What it shows |
| --- | --- | --- |
| `/cronjobs/show_crontab` | Crontab | The crontab entries of this installation, one per line. Add `?full=1` to see the whole crontab file as it is. |
| `/cronjobs/crontab` | Crontab list | Every `runcronjobs.php` entry of the crontab, with a link that starts it, the optional fields from `[CronOptions]` and a console with the running output. |
| `/cronjobs/list` | (not in the menu) | The parts from `cronjob.ini` (`[CronjobPart-<name>]` and the global scripts), each with a link that starts it. |
| `/cronjobs/logs` | Last logs | The contents of `OutputFile` and `ErrorFile`. |
| `/cronjobs/clear` | Clear logs | Empties the output file and/or the error file. |
| `/cronjobs/launch` | (called by the lists) | Starts `runcronjobs.php` with the given part. |

### Running a cronjob from the admin

1. Open **Crontab list** (`/cronjobs/crontab`).
2. Optionally tick **--debug** or fill in **from** / **to**.
3. Click the entry you want to run. A loading image appears and the console
   on the page shows the output file, refreshed every two seconds.
4. When the run ends, the entry shows **Success**.

The request that starts the run is the equivalent of this command, run in the
document root:

```bash
php /path/to/document/root/runcronjobs.php --debug -s site frequent from=2026-01-01
```

The launch view empties the output and error files before it starts the run.

## Permissions

The module `cronjobs` has two functions. Grant them in **Roles and policies**:

| Function | Views |
| --- | --- |
| `cronjobs/list` | `show_crontab`, `crontab`, `list` (and the admin tab) |
| `cronjobs/launch` | `launch`, `logs`, `clear` |

## Security notes

- `cronjobs/launch` runs commands on the server as the web server user. Grant it
  only to administrators you trust with a shell.
- Since 1.3 every word the launch view passes to the command line that holds more
  than letters, digits and `_ . / : = -` is quoted with `escapeshellarg()`.
- The launch view starts a run on a plain GET request. Keep the admin on its own
  host name and sign out when you are done.
- Use `[Forbidden] Parts[]` to keep heavy or dangerous parts out of the admin.

## Upgrading

### To 1.3

- The Composer package is now `se7enxweb/occronjobs`. If you required
  `opencontent/occronjobs-ls`, replace it:

  ```bash
  composer remove opencontent/occronjobs-ls
  composer require se7enxweb/occronjobs
  ```

- Regenerate the autoload array and clear the caches after the update.
- No settings were renamed.

## Known limits

- `MaxExecutionTime` is stored but nothing stops a run that takes longer. The
  launch request waits until the run has ended.
- Errors of a run are written to `OutputFile`; `ErrorFile` is only shown and
  emptied.
- The date fields of `[CronOptions]` are plain text fields. The extension ships
  jQuery UI date picker files, but no template loads them.
- The views use the `admin2` templates; they have no styling of their own for
  the admin4 design.
- The file `modules/cronjobs/show_logs.php` is not registered as a view and does
  not work.
- The **Download output** link under the console points to `logicalogs/download`,
  a view of another extension; without that extension the link does not work.
- Only an Italian translation is included.

## Troubleshooting

**The crontab pages are empty.** Check that `CrontabPath` points to a readable
file, that its entries contain the document root of the installation, and that
any `-s <siteaccess>` is listed in `RelatedSiteAccessList[]`.

**"Script forbidden" in the error log.** The part is listed in
`[Forbidden] Parts[]`.

**Nothing happens when I click an entry.** Check that `proc_open()` is not in
`disable_functions`, that `PhpCliPath` points to a working PHP binary, and that
the web server user may write `OutputFile`.

**The pages are not found (kernel "module not found" or a blank page).** The
extension is not active, or the caches were not cleared after activating it.

## Contributing

Issues and pull requests are welcome at
https://github.com/se7enxweb/occronjobs. Please describe the Exponential and PHP
versions you use.

## License

GNU General Public License v2.0. See [LICENSE](LICENSE).

## Credits and history

occronjobs was written by OpenContent (http://www.opencontent.it) for eZ Publish
4, the content management system eZ Systems developed until its creator
discontinued it. 7x maintains it for Exponential, which continues that code
base. See [CHANGELOG.md](CHANGELOG.md) for the release history.
