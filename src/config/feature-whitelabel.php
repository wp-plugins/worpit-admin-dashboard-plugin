---
properties:
  slug: 'whitelabel'
  name: 'whitelabel'
  show_feature_menu_item: false
  storage_key: 'whitelabel' # should correspond exactly to that in the plugin.yaml
  auto_enabled: false
# Options Sections
sections:
  -
    slug: 'section_non_ui'
    hidden: true

# Define Options
options:
  -
    key: 'current_plugin_version'
    section: 'section_non_ui'
  -
    key: 'enable_whitelabel'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'service_name'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'tag_line'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'plugin_home_url'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'icon_url_16x16'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'icon_url_32x32'
    section: 'section_non_ui'
    default: 'N'
