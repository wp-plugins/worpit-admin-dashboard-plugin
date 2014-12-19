---
slug: 'autoupdates'
properties:
  name: 'Automatic Updates'
  show_feature_menu_item: false
  storage_key: 'autoupdates' # should correspond exactly to that in the plugin.yaml
# Options Sections
sections:
  -
    slug: 'section_non_ui'
    hidden: true

# Define Options and assign to section slug
options:
  -
    key: 'current_plugin_version'
    section: 'section_non_ui'
  -
    key: 'enable_autoupdates'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'enable_autoupdate_disable_all'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'autoupdate_plugin_self'
    section: 'section_non_ui'
    default: 'Y'
  -
    key: 'autoupdate_core'
    section: 'section_non_ui'
    default: 'core_minor'
  -
    key: 'enable_autoupdate_plugins'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'enable_autoupdate_themes'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'enable_autoupdate_translations'
    section: 'section_non_ui'
  -
    key: 'enable_autoupdate_ignore_vcs'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'enable_upgrade_notification_email'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'override_email_address'
    section: 'section_non_ui'
    default: ''
  -
    key: 'auto_update_plugins'
    section: 'section_non_ui'
    default: ''
  -
    key: 'auto_update_themes'
    section: 'section_non_ui'
    default: ''