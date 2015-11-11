#/bin/bash
 
AWS_ACCESS_KEY_ID=`grep '$config->aws->key' ../config_local.php | cut -d "'" -f2`
AWS_SECRET_ACCESS_KEY=`grep '$config->aws->secret' ../config_local.php | cut -d "'" -f2`
AWS_REGION=`grep '$config->aws->region' ../config_local.php | cut -d "'" -f2`
PREFIX=`grep '$config->db->dynamoDBPrefix' ../config_local.php | cut -d "'" -f2`
DATE=`date +%d-%m-%Y-%H:%M`
BUCKETNAME='backup-ddb-fioi'
RATE=100
declare -a TABLES=(team team_question)
for t in ${TABLES[@]}
do
  dynamo-archive --rate=$RATE --region=eu-central-1 --table=$PREFIX$t > $PREFIX$t.json
  aws s3 cp $PREFIX$t.json s3://$BUCKETNAME/$PREFIX$t/$DATE.json
  rm $PREFIX$t.json
done