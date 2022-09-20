# RelatedResources

Find all related resources linked to the current displayed item.  
Related resources and displayed item can comes from multiple providers:

- Files,
- Deck,
- Talk,
- Calendar.

### related resources

Based on currently displayed item from one of the available provider, the app:

- get all entities that specific item is shared to,
- get all resources from each provider shared to each entity,
- filters results based on current user access rights,
- weight each related resource using different rules:
    - in case of duplicated entry, only one will be kept and have its score improved,
    - compare keywords with the one from the displayed item to improve score,
    - improve score on shares generated in the same period of time,
    - compare the owner of the shares
    - in case of duplicate improvement, apply a diminishing return on the score improvement for each
      entry
    - decrease score in case of not-related shares
    - decrease score on old shares

### building the app

>     $ make

app will be available in `build/artifacts/`

### ocs

front-end will use this endpoint to get related resources to an item:

- `providerId` can be `files`, `deck`, `talk`, `calendar`
- `itemId` will be the unique Id to the current displayed item

>     curl "https://cloud.example.net/ocs/v2.php/apps/related_resources/related/<providerId>/<itemId>?format=json" -u 'user:password' -H "OCS-ApiRequest: true" -H "Accept: application/json"

### occ

A command is available to get related resources from a terminal. The command will returns 2 tables
displaying:

- all shares' recipients to an item
- list of related resources to the item

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
| talk        | ad3jjo1 | Test Convo 001 | Talk Room   | 1     | http://nc24.local/index.php/call/amn2iar4       |
+-------------+---------+----------------+-------------+-------+-------------------------------------------------+
```

### configuration

- set the maximum number of result to be returned by the ocs endpoint

>     ./occ config:app:set related_resources result_max --value 7
