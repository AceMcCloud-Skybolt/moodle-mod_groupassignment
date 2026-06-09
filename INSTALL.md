# Installing the prototype

This plugin's Moodle component is `mod_groupassign`, so the directory under Moodle must be named `groupassign`.

## Recommended developer install

Clone the repository into the correct Moodle activity folder:

```bash
git clone https://github.com/AceMcCloud-Skybolt/moodle-mod_groupassignment.git /path/to/moodle/public/mod/groupassign
```

Then visit Moodle site administration or run Moodle's upgrade script.

## Important

Do not install a GitHub source ZIP directly if Moodle expands it as `groupassignment`, `moodle-mod_groupassignment`, or `moodle-mod_groupassignment-main`.

Moodle validates the folder name against `$plugin->component` in `version.php`. If the folder is not `groupassign`, Moodle will report:

```text
Plugin mod_groupassignment does not declare valid $plugin->component in its version.php.
```

If using a ZIP, make sure the ZIP contains a top-level folder named `groupassign`.

## Build an installable ZIP

From the repository root on Windows:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build-package.ps1
```

This creates `dist/groupassign.zip`, which is safe to install through Moodle's plugin installer.
