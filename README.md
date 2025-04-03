<p align="center">
    <a href="" rel="noopener">
        <img width="700" height="400" src="[https://github.com/user-attachments/assets/33d4b656-495a-4bed-92a0-f44e78d03861](https://github.com/user-attachments/assets/ce6b07c6-0d7d-46b1-9b6f-3662b689ddbc)" alt="Project logo">
    </a>
</p>

<h3 align="center">Extra Forum Permissions</h3>

<div align="center">

[![Status](https://img.shields.io/badge/status-active-success.svg)]()
[![GitHub Issues](https://img.shields.io/github/issues/OUGC-Network/MyBB-Extra-Forum-Permissions.svg)](./issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/OUGC-Network/MyBB-Extra-Forum-Permissions.svg)](./pulls)
[![License](https://img.shields.io/badge/license-GPL-blue)](/LICENSE)

</div>

---

<p align="center"> Extra Forum Permission Pack
    <br> 
</p>

## 📜 Table of Contents <a name = "table_of_contents"></a>

- [About](#about)
- [Getting Started](#getting_started)
    - [Dependencies](#dependencies)
    - [File Structure](#file_structure)
    - [Install](#install)
    - [Update](#update)
    - [Template Modifications](#template_modifications)
- [Settings](#settings)
- [Usage](#usage)
- [Built Using](#built_using)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)
- [Support & Feedback](#support)

## 🚀 About <a name = "about"></a>

Extra Forum Permission Pack

[Go up to Table of Contents](#table_of_contents)

## 📍 Getting Started <a name = "getting_started"></a>

The following information will assist you into getting a copy of this plugin up and running on your forum.

### Dependencies <a name = "dependencies"></a>

A setup that meets the following requirements is necessary to use this plugin.

- [MyBB](https://mybb.com/) >= 1.8
- PHP >= 7

### File structure <a name = "file_structure"></a>

  ```
   .
   ├── inc
   │ ├── languages
   │ │ ├── english
   │ │ │ ├── admin
   │ │ │ │ ├── extraforumperm.lang.php
   │ │ │ ├── extraforumperm.lang.php
   │ ├── plugins
   │ │ ├── ougc
   │ │ │ ├── ExtraForumPermissions
   │ │ │ │ ├── hooks
   │ │ │ │ │ ├── admin.php
   │ │ │ │ │ ├── forum.php
   │ │ │ │ │ ├── shared.php
   │ │ │ │ ├── admin.php
   │ │ │ │ ├── core.php
   │ │ ├── extraforumperm.php
   ```

### Installing <a name = "install"></a>

Follow the next steps in order to install a copy of this plugin on your forum.

1. Download the latest package from the [MyBB Extend](https://community.mybb.com/mods.php) site or
   from the [repository releases](https://github.com/OUGC-Network/MyBB-Extra-Forum-Permissions/releases/latest).
2. Upload the contents of the _Upload_ folder to your MyBB root directory.
3. Browse to _Configuration » Plugins_ and install this plugin by clicking _Install & Activate_.
4. Browse to _Settings_ to manage the plugin settings.

### Updating <a name = "update"></a>

Follow the next steps in order to update your copy of this plugin.

1. Browse to _Configuration » Plugins_ and deactivate this plugin by clicking _Deactivate_.
2. Follow step 1 and 2 from the [Install](#install) section.
3. Browse to _Configuration » Plugins_ and activate this plugin by clicking _Activate_.
4. Browse to _Settings_ to manage the plugin settings.

### Template Modifications <a name = "template_modifications"></a>

1. Open the `editpost`, find `maxlength="85"` and replace with `maxlength="{$extra_maximum_subject_length}"`.
2. Open the `newthread`, find `maxlength="85"` and replace with `maxlength="{$extra_maximum_subject_length}"`.
3. Open the `newreply`, find `maxlength="85"` and replace with `maxlength="{$extra_maximum_subject_length}"`.
4. Open the `showthread_quickreply`, find `{$closeoption}` and replace with `<!--EXTRAPERMISSIONS-->`.

[Go up to Table of Contents](#table_of_contents)

## 🛠 Settings <a name = "settings"></a>

Below you can find a description of the plugin settings.

### Main Settings

[Go up to Table of Contents](#table_of_contents)

## 📖 Usage <a name="usage"></a>

The following is a description of additional configuration for this plugin.

[Go up to Table of Contents](#table_of_contents)

## ⛏ Built Using <a name = "built_using"></a>

- [MyBB](https://mybb.com/) - Web Framework
- [MyBB PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) - A collection of useful functions for MyBB
- [PHP](https://www.php.net/) - Server Environment

[Go up to Table of Contents](#table_of_contents)

## ✍️ Authors <a name = "authors"></a>

- [@Omar G](https://github.com/Sama34) - Idea & Initial work

See also the list of [contributors](https://github.com/OUGC-Network/MyBB-Extra-Forum-Permissions/contributors) who
participated in
this
project.

[Go up to Table of Contents](#table_of_contents)

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- [The Documentation Compendium](https://github.com/kylelobo/The-Documentation-Compendium)

[Go up to Table of Contents](#table_of_contents)

## 🎈 Support & Feedback <a name="support"></a>

This is free development and any contribution is welcome. Get support or leave feedback at the
official [MyBB Community](https://community.mybb.com/thread-159249.html).

Thanks for downloading and using our plugins!

[Go up to Table of Contents](#table_of_contents)