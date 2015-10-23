# wc-attendees-command

WP-CLI command to prepare a CSV of all attendees to a given WordCamp.

Paranoid? The command uses public data from Gravatar. Try it yourself:

```bash
wp --require=wc-attendees-command.php wc-attendees https://portland.wordcamp.org/2015/attendees/ --format=csv > attendees.csv
```
