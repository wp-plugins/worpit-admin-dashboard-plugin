---
properties:
  slug: 'google_analytics'
  name: 'Google Analytics'
  show_feature_menu_item: false
  storage_key: 'google_analytics' # should correspond exactly to that in the plugin.yaml
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
    key: 'enable_google_analytics'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'enable_universal_analytics'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'tracking_id'
    section: 'section_non_ui'
    default: ''
  -
    key: 'ignore_logged_in_user'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'ignore_from_user_level'
    section: 'section_non_ui'
    default: 11
  -
    key: 'in_footer'
    section: 'section_non_ui'
    default: 'N'