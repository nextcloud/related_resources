# RelatedResources App

find all related resources that might be linked to an item. currently, quick support of Files, Deck and
Talk.

### building the app

- `make appstore` should build the app in `build/artifacts/`

### quick overview

- get all shares to the item (providerId + itemId)
- for each entity the item is shared to, get other shared items (from all providers) to that entity.

### occ

an occ command will return all recipient to an item, and list of related resource based on that
recipients list

```
$ ./occ related:test <userId> <providerId> <itemId>
+---------------------------------+-----------+----------+--------+
| Single Id                       | User Type | User Id  | Source |
+---------------------------------+-----------+----------+--------+
| D2k2QudMQcwRl6s6Jv5XOviWGGmPnhQ | 16        | Test 001 | 16     |
| PSAM2DI1GwmyDydJSKdQxsaGPaNlVDD | 1         | test4    | 1      |
| v3RpXpyExROScJAEZvJLyHRt7Jsfk9J | 1         | test5    | 1      |
| IJCtfbJgIIMP96spf77lHPvLbWu6MZu | 1         | test6    | 1      |
+---------------------------------+-----------+----------+--------+

+-------------+---------+----------------+-------------+-------+-------------------------------------------------+
| Provider Id | Item Id | Title          | Description | Score | Link                                            |
+-------------+---------+----------------+-------------+-------+-------------------------------------------------+
| files       | 207     | /Test 001      | Files       | 3.528 | /index.php/f/207                                |
| files       | 16      | /Templates     | Files       | 1.1   | /index.php/f/16                                 |
| deck        | 3       | ouila          | Deck board  | 1     | http://nc24.local/index.php/apps/deck/#/board/3 |
| talk        | 3       | Test Convo 001 | Talk Room   | 1     | http://nc24.local/index.php/call/amn2iar4       |
+-------------+---------+----------------+-------------+-------+-------------------------------------------------+
```

### curl

using `providerId` and `itemId`

>     curl "https://cloud.example.net/ocs/v2.php/apps/related_resources/related/<providerId>/<itemId>?format=json" -u 'admin:admin' -H "OCS-ApiRequest: true" -H "Accept: application/json"

