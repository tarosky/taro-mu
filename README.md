# taro-mu

mu-plugins for Tarosky. Put `taro-mu-setting.json` in your wp-content/mu-plugins folder.

## How to make backup file.

See gist [example](https://gist.github.com/fumikito/2a2c7bbaa103e83ef7965b507ab748b5).

## Setting file

Setting file example below:

```
{
  "sync": {
    "home": "https://example.com",
    "url": "https://example.com/wp-content/backup.tar.gz",
    "auth": {
      "user": "basic_auth_user",
      "password": "basic_auth_pass"
    },
    "theme_excluded": [
      "akismet",
      "jetpac"
    ],
    "plugin_excluded": [
      "my-super-plugin"
    ]
  }
}
```
