# EditWarning
The EditWarning extension displays a warning if user edits a page/section which is currently being edited.

On pages which are being edited by many users simultaneously, edit conflicts can easily happen. This extension tries to avoid this problem by showing a warning message if the user edits a page that is being edited by other users at the same time. 

Supported editing modes:
* Source edititing
* Editing via Page Forms
* Editing via VisualEditor 

This extension was inspired by https://github.com/nemphis/mw-editwarning/ which has not been maintained for a long time. 

## Installation
Download and place the file(s) in a directory called RegexFunctions in your extensions/ folder.

Add the following code at the bottom of your LocalSettings.php:

```php
 wfLoadExtension("EditWarning");
```
Run the "update.php" script from the base directory of your MediaWiki installation:

```shell
 php maintenance/update.php
```
Done – Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

## Known Issues
The extension can only recognize the cancel of page editing if the user uses the "Cancel" button. Otherwise the warning will be showed until the timeout (default 10 minutes).

## Configuration
Option | Default | Description |
--- | --- | --- |
$wgEditWarning_ShowInfoBox | false | Displays a info box for the first editor. |
$wgEditWarning_Timeout | 10 | How long should a warning box be displayed |
