#!/bin/bash

DB=beaver

TS=$(date +%Y%m%d%H%M)
MYSQLDUMP="mysqldump -q -e --single-transaction --skip-triggers ${DB}"

tables_castor_major="contest contestant contest_question group languages question school school_user school_year team user user_user translations"
tables_castor_team_question="team_question"
tables_castor_all="history_contest history_contestant history_contest_question history_group history_languages history_question history_school history_school_user history_school_year history_team history_team_connection history_translations history_user history_user_user certi_queue contest contestant contest_question group languages question school school_user school_year synchro_version team team_connection team_question translations user user_user"

dump_tables() {
	local group="$1"
	local target="$HOME/backups/${TS}_${group}.sql.gz"
	shift

	if $MYSQLDUMP --tables "$@" | gzip > "$target" ; then
		ls -l "$target"
	else
		echo "error dumping ${group}"
	fi
}

for group in "$@" ; do
	tables_var="tables_$group"
	tables="${!tables_var}"
	if test -z "$tables" ; then
		echo "no such group: $group"
	else
		echo "Dumping group: $group"
		dump_tables "$group" ${!tables_var}
	fi
	echo
done

if rsync -a "$HOME/backups/" "bb0067@head.armu.re:backups/" ; then
	echo "rsync ok"
else
	echo "rsync FAILED"
fi
echo

