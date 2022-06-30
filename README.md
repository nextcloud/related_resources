# RelatedResources App

find all related resources that might be linked to an item. currently, quick support of Files, Deck and
Talk.

### quick overview

- get all shares to the item (providerId + itemId)
- for each entity the item is shared to, get other shared items (from all providers) to that entity.

### occ

```
$ ./occ related:test <userId> <providerId> <itemId>
+-------------+---------+------------------------+-----------------+-------+------+
| Provider Id | Item Id | Title                  | Description     | Range | Link |
+-------------+---------+------------------------+-----------------+-------+------+
| files       | 324     | /ouila                 |                 | 1     |      |
| files       | 272     | /test.txt              |                 | 1     |      |
| files       | 16      | /Templates             |                 | 1     |      |
| files       | 326     | /Collectives           |                 | 1     |      |
| deck        | 2       | sadsajdkl              |                 | 1     |      |
| talk        | 6       | This is my first convo | token: fb8ygpvi | 1     |      |
+-------------+---------+------------------------+-----------------+-------+------+
```


### curl

not tested, but an ocs route is available

