OLD_HOST=localhost
OLD_DB=castor
OLD_USER=castor
OLD_PASSWORD=castor
NEW_HOST=xxx
NEW_DB=xxx
NEW_USER=xxx
NEW_PASSWORD=xxx
TABLES="cert_queue contest contestant contest_question group languages question school school_user school_year synchro_version team team_connection team_question team_view tm_platforms translations user user_user cert_queue history_contest history_contestant history_contest_question history_group history_languages history_question history_school history_school_user history_school_year synchro_version history_team history_team_connection history_translations history_user history_user_user"

# see http://stackoverflow.com/a/25638401/2560906
mysqldump -h $OLD_HOST -u $OLD_USER -p$OLD_PASSWORD $OLD_DB | perl -pe 's/\sDEFINER=`[^`]+`@`[^`]+`//' | mysql -h $NEW_HOST -u $NEW_USER -p$NEW_PASSWORD $NEW_DB
