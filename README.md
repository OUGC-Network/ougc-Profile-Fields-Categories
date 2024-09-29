<p align="center">
    <a href="" rel="noopener">
        <img width="700" height="400" src="https://github.com/user-attachments/assets/a5ce44cc-4bd5-4df3-ae4e-e040cda5cc22" alt="Project logo">
    </a>
</p>

<h3 align="center">ougc Profile Fields Categories</h3>

<div align="center">

[![Status](https://img.shields.io/badge/status-active-success.svg)]()
[![GitHub Issues](https://img.shields.io/github/issues/OUGC-Network/ougc-Profile-Fields-Categories.svg)](./issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/OUGC-Network/ougc-Profile-Fields-Categories.svg)](./pulls)
[![License](https://img.shields.io/badge/license-GPL-blue)](/LICENSE)

</div>

---

<p align="center"> Allow administrators to create custom profile fields categories.
    <br> 
</p>

## 📜 Table of Contents <a name = "table_of_contents"></a>

- [About](#about)
- [Getting Started](#getting_started)
    - [Dependencies](#dependencies)
    - [File Structure](#file_structure)
    - [Install](#install)
    - [Update](#update)
- [Usage](#usage)
- [Built Using](#built_using)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)
- [Support & Feedback](#support)

## 🚀 About <a name = "about"></a>

[Go up to Table of Contents](#table_of_contents)

## 📍 Getting Started <a name = "getting_started"></a>

The following information will assist you into getting a copy of this plugin up and running on your forum.

### Dependencies <a name = "dependencies"></a>

A setup that meets the following requirements is necessary to use this plugin.

- [MyBB](https://mybb.com/) >= 1.8
- PHP >= 7.0
- [PluginLibrary for MyBB](https://github.com/frostschutz/MyBB-PluginLibrary) >= 13

### File structure <a name = "file_structure"></a>

  ```
   .
   ├── inc
   │ ├── languages
   │ │ ├── english
   │ │ │ ├── admin
   │ │ │ │ ├── ougc_profiecats.lang.php
   │ │ │ ├── ougc_profiecats.lang.php
   │ ├── plugins
   │ │ ├── ougc
   │ │ │ ├── ProfileFieldsCategories
   │ │ │ │ ├── templates
   │ │ │ │ │ ├── forumRules
   │ │ │ │ │ ├── forumRulesLink
   │ │ │ │ │ ├── memberList
   │ │ │ │ │ ├── memberListField
   │ │ │ │ │ ├── memberListFieldMultiSelect
   │ │ │ │ │ ├── memberListFieldMultiSelectValue
   │ │ │ │ │ ├── postBit
   │ │ │ │ │ ├── postBitField
   │ │ │ │ │ ├── postBitFieldMultiSelect
   │ │ │ │ │ ├── postBitFieldMultiSelectValue
   │ │ │ │ │ ├── profile
   │ │ │ │ │ ├── profileField
   │ │ │ │ │ ├── profileMultiSelect
   │ │ │ │ │ ├── profileMultiSelectValue
   │ │ │ │ │ ├── userControlPanelField
   │ │ │ │ │ ├── userControlPanelFieldCheckBox
   │ │ │ │ │ ├── userControlPanelFieldMultiSelect
   │ │ │ │ │ ├── userControlPanelFieldRadio
   │ │ │ │ │ ├── userControlPanelFieldSelect
   │ │ │ │ │ ├── userControlPanelFieldSelectOption
   │ │ │ │ │ ├── userControlPanelFieldText
   │ │ │ │ │ ├── userControlPanelFieldTextArea
   │ │ │ │ │ ├── userControlPanelOptionalFields
   │ │ │ │ │ ├── userControlPanelRequiredFields
   │ │ │ │ ├── admin.php
   │ │ │ │ ├── admin_hooks.php
   │ │ │ │ ├── core.php
   │ │ │ │ ├── forum_hooks.php
   │ │ ├── ougc_profiecats.php
   ```

### Installing <a name = "install"></a>

Follow the next steps in order to install a copy of this plugin on your forum.

1. Download the latest package from the [MyBB Extend](https://community.mybb.com/mods.php?action=view&pid=1400) site or
   from the [repository releases](https://github.com/OUGC-Network/ougc-Profile-Fields-Categories/releases/latest).
2. Upload the contents of the _Upload_ folder to your MyBB root directory.
3. Browse to _Configuration » Plugins_ and install this plugin by clicking _Install & Activate_.

### Updating <a name = "update"></a>

Follow the next steps in order to update your copy of this plugin.

1. Browse to _Configuration » Plugins_ and deactivate this plugin by clicking _Deactivate_.
2. Follow step 1 and 2 from the [Install](#install) section.
3. Browse to _Configuration » Plugins_ and activate this plugin by clicking _Activate_.

[Go up to Table of Contents](#table_of_contents)

## 📖 Usage <a name="usage"></a>

This plugin has no additional configurations; after activating make sure to modify the global settings in order to get
this plugin working.

[Go up to Table of Contents](#table_of_contents)

## ⛏ Built Using <a name = "built_using"></a>

- [MyBB](https://mybb.com/) - Web Framework
- [MyBB PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) - A collection of useful functions for MyBB
- [PHP](https://www.php.net/) - Server Environment

[Go up to Table of Contents](#table_of_contents)

## ✍️ Authors <a name = "authors"></a>

- [@Omar G](https://github.com/Sama34) - Idea & Initial work

See also the list of [contributors](https://github.com/OUGC-Network/ougc-Profile-Fields-Categories/contributors) who
participated in this project.

[Go up to Table of Contents](#table_of_contents)

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- [The Documentation Compendium](https://github.com/kylelobo/The-Documentation-Compendium)

[Go up to Table of Contents](#table_of_contents)

## 🎈 Support & Feedback <a name="support"></a>

This is free development and any contribution is welcome. Get support or leave feedback at the
official [MyBB Community](https://community.mybb.com/thread-221815.html).

Thanks for downloading and using our plugins!

[Go up to Table of Contents](#table_of_contents)