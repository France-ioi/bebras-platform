#! /bin/bash

CUR_DIR="$( cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Test if the script is already running
nb=$(ps aux | grep -E "bgScript.sh" | grep -v "grep" | wc -l)

# 2 : on the first call, 3 : when called after nohup
if [[ $nb != "2" && $nb != "3" ]]
then
   echo "The script is already running " $nb
   exit 0
fi

cd $CUR_DIR

# Check if the reference exists in the crontab
grep bgScript.sh <(crontab -l) > /dev/null
if [[ $? -ne 0 ]]
then
    BASH=$(which bash)
    echo "Please add this line to the crontab, with 'crontab -e'"
    echo "It will restart the script (if needed) every 5 minutes "
    echo "*/5 * * * * $BASH $CUR_DIR/bgScript.sh"
    exit 1
fi

# Verification about the current configuration
checkIns ()
{
   which $1 > /dev/null 2>&1;
   if [[ $? -ne 0 ]]; then
      echo "'$1' is not installed : ERROR";
      exit 1
   fi       
}

checkExec ()
{
   if [[ $(ls -l -d "$1" | cut -d ' ' -f 1) !=  "drwxrwxrwx" ]];then
      echo "The directory '$1' must be in access mode 0777";
      exit 1
   fi
}

checkExec export

# Put itself in the background
if [[ "$_myBackGround" != "_backGroundFlag" ]]; then
   nohup /bin/bash -c "_myBackGround=_backGroundFlag; export _myBackGround;$0 $*"  > /tmp/bgScript.$$.stdout 2> /tmp/bgScript.$$.stderr &
   echo "The script is now running in the background with 'nohup'."
   echo "You can close your session."
   exit 0
fi


# Everything is alright, we can start
while true
do
   d=$(date +%Y-%m-%d-%H:%M:%S-%N)
   if [[ -e "wait" ]] ; then
      echo "$d : OK" >> wait
      (echo ""; echo $d; echo "Waiting...") >> log/generation.log
   else
      (echo ""; echo $d) >> log/generation.log
      php $CUR_DIR/processNextRequest.php 2>&1 >> log/generation.log
   fi;
   sleep 15
done
