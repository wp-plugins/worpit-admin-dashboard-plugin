---
properties:
  slug: 'plugin'
  name: 'Dashboard'
  show_feature_menu_item: true
  storage_key: 'plugin' # should correspond exactly to that in the plugin.yaml
  auto_enabled: true
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
    key: 'key'
    section: 'section_non_ui'
    default: ''
  -
    key: 'pin'
    section: 'section_non_ui'
    default: ''
  -
    key: 'assigned'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'assigned_to'
    section: 'section_non_ui'
    default: ''
  -
    key: 'time_last_check_can_handshake'
    section: 'section_non_ui'
    value: 0
  -
    key: 'can_handshake'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'handshake_verify_test_url'
    section: 'section_non_ui'
    value: 'https://app.icontrolwp.com/system/verification/test'
    immutable: true
  -
    key: 'handshake_verify_url'
    section: 'section_non_ui'
    value: 'https://app.icontrolwp.com/system/verification/check'
    immutable: true
  -
    key: 'remote_add_site_url'
    section: 'section_non_ui'
    value: 'https://app.icontrolwp.com/system/remote/add_site'
    immutable: true
  -
    key: 'reset_site_url'
    section: 'section_non_ui'
    value: 'https://app.icontrolwp.com/system/verification/reset/'
    immutable: true
  -
    key: 'package_retrieve_url'
    section: 'section_non_ui'
    value: 'https://app.icontrolwp.com/system/package/retrieve/'
    immutable: true
  -
    key: 'installation_time'
    section: 'section_non_ui'
  -
    key: 'enable_hide_plugin'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'feedback_admin_notice'
    section: 'section_non_ui'
  -
    key: 'icwp_public_key'
    value: 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0NCk1JSUJJakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBUThBTUlJQkNnS0NBUUVBdWxOM2lKRHZEdURGM2JIcnYrSEYNCjZ3T0RVai9GbGtFY1QvYzB5QWllYXNYTXNUQWRxN3AwWVBPQmtMSy92RFAyTE04b054dHA4MzlVUkI2aGFDa2sNCmRPUUZCdHpwY0UvU0NJZjVDSUJEeWhDVUlhRENtK1JnZDlpWmxISldBbzVGZkRlODlxb3FJTGRodkp2UHlzbTYNCkQ0b3hmcXYzMlF1TTV2VjUyT3ZaU1Q5WG1ydytPcHRCc0Rjbjk5THlOdGhYZ3RweHJEVnlTZGljVzBqelpYUHANCm1xbUE0SEZqMzQ3Z3hMNVB1Q0hXcEgyN3RqMCtYSjE3TFoyWHNSQWtaaE1TdEJtTUtBaW02R25yMkVQTTJBc20NCklCWGtzcEs5M2lHVGZiYUlMZE4vQ0NGTmVaUlh5WGNyV1hNV1Bvd0VFQVN0ZXJHNXN1QWlRSkhjVDBwaW0za2oNCjZ3SURBUUFCDQotLS0tLUVORCBQVUJMSUMgS0VZLS0tLS0='
    immutable: true
  -
    key: 'service_ip_addresses_ipv4'
    section: 'section_non_ui'
    value:
      valid:
        - '198.61.176.9' #wd01
        - '23.253.56.59' #app01
        - '23.253.62.185' #app01
        - '23.253.32.180' #wd02
      old:
        - '198.61.173.69'
  -
    key: 'service_ip_addresses_ipv6'
    section: 'section_non_ui'
    value:
      valid:
        - '2001:4801:7817:0072:ca75:cc9b:ff10:4699' #wd01
        - '2001:4801:7817:72:ca75:cc9b:ff10:4699' #wd01
        - '2001:4801:7824:0101:ca75:cc9b:ff10:a7b2' #app01
        - '2001:4801:7824:101:ca75:cc9b:ff10:a7b2' #app01
        - '2001:4801:7822:0103:be76:4eff:fe10:89a9' #wd02
        - '2001:4801:7822:103:be76:4eff:fe10:89a9' #wd02
  -
    key: 'active_plugin_features'
    section: 'section_non_ui'
    value:
      -
        slug: 'security'
        storage_key: 'security'
        load_priority: 0
      -
        slug: 'compatibility'
        storage_key: 'compatibility'
      -
        slug: 'google_analytics'
        storage_key: 'google_analytics'
      -
        slug: 'autoupdates'
        storage_key: 'autoupdates'
      -
        slug: 'statistics'
        storage_key: 'statistics'
      -
        slug: 'whitelabel'
        storage_key: 'whitelabel'