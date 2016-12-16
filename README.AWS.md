# Installing Bebras platform on Amazon Web Services

This file is a documentation to get the Beaver Contest working on AWS. The goal
is to have:

 - MySQL RDS as main DB
 - DynamoDB for sessions and as DB for contest (`team` and `team_question` only)
 - S3 for static file hosting
 - Beanstalk for php hosting management

This documentation aims at being a tutorial, it does not expose any requirement.

## IAM

First thing is to create an AWS account, and then an IAM user with admin access,
see [IAM doc](http://docs.aws.amazon.com/IAM/latest/UserGuide/IAMBestPractices.html). Get
the credentials (ID and secret key) for this user, they will be used everywhere.


## S3

Create one S3 bucket.

Set `teacherInterface->generationMode` to 'aws' or 'aws+local' to generate the
contests in AWS S3, or both local ans AWS S3. Set the aws->public config to
the credentials and name of the bucket that will contain contest data (solution,
grader, etc.) will not be publicly accessible.

Add a CORS rule to your bucket (select bucket in console, properties, permission
-> add CORS configuration), and set it to something like:

    <?xml version="1.0" encoding="UTF-8"?>
    <CORSConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
        <CORSRule>
            <AllowedOrigin>*</AllowedOrigin>
            <AllowedMethod>GET</AllowedMethod>
            <MaxAgeSeconds>3000</MaxAgeSeconds>
            <AllowedHeader>*</AllowedHeader>
        </CORSRule>
    </CORSConfiguration>

Also, enable Static Website Hosting.

Before generating a contest, you also need to:
   - set `teacherInterface->sAbsoluteStaticPath` in `config_local.php` to the absolute
     path of your bucket (without the final `contest/` directory)

## CloudFront (advised)

In order to boost access perfomance to your S3 files, you can create a
CloudFront instance pointing to your bucket.


## Pointing a DNS CNAME to your files

If you don't have a CloudFront instance, you can just point a CNAME to
your direct S3 URL. There are some [restrictions about the URL](https://docs.aws.amazon.com/AmazonS3/latest/dev/website-hosting-custom-domain-walkthrough.html#root-domain-walkthrough-s3-tasks) (and thus the
bucket name).

So you must name your bucket like the final host name that will serve it: if you
want to get your files on `static-tmp.castor-informatique.fr`, your bucket must
be named `static-tmp.castor-informatique.fr`.

If you have a CloudFront, this requires a bit of [configuration](http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/CNAMEs.html) at creation time.
In this case, the best is to create a new CF instance pointing to the public
bucket (proposed in the list), with an alternative CNAME from your domain.
Note that you must point your new CNAME to `xxx.cloudfront.net` url corresponding
to your CF instance. You can add an alternative CNAME to an existing CF instance
in the console by clicking "Edit" on the dashboard page.


## DynamoDB

Set `db->use` to *dynamoDB* in `config_local.php` to use DynamoDB for:

- read/write in `team_question` in DynamoDB only
- read/write in `team` in both DynamoDB and MySQL (with checks for sync)
- handling sessions with DynamoDB

It works with the following indexes in DynamoDB:

- team:
   - main hash : ID (number), no range
   - secondary index hash : password (string), no range.
- team_question:
   - main hash: teamID (number), range questionID (number)
- sessions (see [doc](http://aws.amazon.com/fr/blogs/aws/scalable-session-handling-in-php-using-amazon-dynamodb/)):
   - main hash: id (string), no range (note that you must provide reasonable read/write units),

The ID of team is the same as in MySQL; the ID of team_question is a unique
hash built from teamID and questionID (see corresponding function in
tinyORM.php).

The credentials are most often the same as those of S3.

See `shared/tinyORM.php` for an interface working with both MySQL and DynamoDB,
under some precise assumptions.

See `shared/transferTable.php` for a small script to transfer a table from MySQL
to DynamoDB.

## DynamodDB migration to SQL

If you want to migrate your dynamoDB data to SQL:

- first, you don't need to transfer "teams", as it's updated on both sides
- setup your config so that you use dynamoDB
- increase the read capacity of the team_question table, to at least 200
- set the date interval you want the dynamoDB team_questions from in `shared/dumprecentteamquestion.php`
- `cd shared`
- run `php dumprecentteamquestion.php > recentteamquestion.dump`
- the file `recentteamquestion.dump` will contain the team_question extract, one item per line in json format
- run `php dumptosql.php`, adjusting the $sqlTable variable if you need, it will produce `recentteamquestion.sql`
- import the sql file your base
- don't forget to dicrease the capacity of the team_question table

## Elastic Beanstalk (EBS)

In order to set up the php environment, we use AWS Elastic Beanstalk, as it is
very simple, scalable and safe.

First thing is to create a new application with two new environments with PHP as
software: one for admin (`teacherInterface/`) and one for contests
(`contestInterface/`). 

When you will be prompted to add a RDS Database, during the creation of the
first beanstalk, do it with the default options, using MySQL as engine.

Configure the two environments to point to the relevant subdirectory: once the
environment is created, go in the console for one environment, select
*Configuration*, *Software Configuration* and modify *Document root* to
respectively `/contestInterface` and `/teacherInterface`.

Configure your `config_local.php` to access your rds database and upload your files to
the application.

Important: if you use auto-scaling, you'll experience trouble unless you follow
[these instructions](http://docs.aws.amazon.com/AutoScaling/latest/DeveloperGuide/as-add-elb-healthcheck.html).


### Uploading files to EBS

The repository being quite big, upload doesn't seem to work properly (at least
in the author's experience), so the advised method is to use the only other
interface: the git interface. To use it, see the [doc](http://docs.aws.amazon.com/elasticbeanstalk/latest/dg/command-reference-get-started.html).

The files you need to upload are, for both the interfaces:

- all files in the root folder
- `contestInterface/`
- `dataSanitizer/`
- `sync/`
- `modelsManager/`
- `teacherInterface/`
- `schoolsMap/`
- `certificates/`

Or you can simply upload all files on both. It's quite important to protect
`teacherInterface/bever_tasks/`, you can do it by adding a .htpasswd.
These are referenced by an absolute path in .htaccess files (a questionable
design choice), hence there is no such file in the svn. If you didn't do any
extra configuration, the absolute path of the root of your app is in 
`/var/app/current/`.


## Adding CNAME

Final step is to point a CNAME of the same DNS domain as the public S3 bucket
to this EB environment. You can do it by just adding a CNAME to the
`xxx-yyy.elasticbeanstalk.com` URL corresponding to your EB instance.


## RDS

We will use Amazon's own SQL instances, AWS RDS, of which you should already
have an instance (created in previous step).


#### Changing Network access rights to DB

By default, RDS DBs created by EBS cannot be accessed outside EBS. But it would be
quite complex to make a database transfer through EBS, so the best is to open
it widely in order to transfer the data from your local database to RDS.
Doing so is not specific to this platform, but as the console gives confused
advises on this, here is the most simple procedure:

- go in the RDS console, select your DB, and note the security group starting
   by 'rds'
- go in the VPC console (menu "Compute & Networking")
- in left tab "Security Group", select the group you noted before
- in the bottom tabs, select "Inbound Rules"
- create a New rule with type SQL, and source `0.0.0.0/0`
- save

In order to remove access outside EB, remove the rule you added.


#### Transfer bases from/to RDS

The small script `shared/transfer-bases.sh` can be used (after modifications) to
transfer bases to/from AWS RDS.


#### Allow Triggers in Database

By default, you cannot add triggers to the database. To enable this, you must
set `log_bin_trust_function_creators` to 1. To do so, create a new parameter
group in the console, with this parameter to 1, and configure your instance
to use this parameter group. (You'll need to reboot the instances for it to be
taken into account).


## Final Details

It's important that all you instances and RDS have the same time. By default
they are all using ntp, but you can check using the method described in the [doc](http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/set-time.html).


## Stress tests

See [tests/README.md](tests/README.md).


## Deployment and update management

A very important thing is to install [AWS CLI](http://docs.aws.amazon.com/cli/latest/userguide/installing.html).
(TL;DR: `sudo pip install awscli`), which will enable you to control the
deployment of the code. To do so, you can use a small script like the following:

```bash
#!/usr/bin/env bash

echo "svn update..."
svn up svncastor
svn up svncastor/teacherInterface/beaver_tasks
echo "svn export..."
svn export --force svncastor GitAwsEnv
svn export --force svncastor/teacherInterface/beaver_tasks GitAwsEnv/teacherInterface/beaver_tasks
echo "removing 2015 tasks and .htaccess"
rm -f GitAwsEnv/teacherInterface/beaver_tasks/.htaccess
rm -rf GitAwsEnv/teacherInterface/beaver_tasks/*2015*
COMMIT_MSG="r$(svnversion svncastor): $(svn log -r COMMITTED svncastor |sed -n 4p)"
cd GitAwsEnv
echo "composer install..."
composer install --quiet
echo "generating indexes..."
php contestInterface/generateIndexes.php
cp contestInterface/index_fr.html contestInterface/index.html
cp teacherInterface/index_fr.html teacherInterface/index.html
gzip -c9 contestInterface/common.js | aws s3 cp - s3://static-tmp.castor-informatique.fr/assets/common.js --acl public-read --content-encoding 'gzip' --content-type 'application/javascript'
gzip -c9 contestInterface/integrationAPI/task-proxy.js | aws s3 cp - s3://static-tmp.castor-informatique.fr/assets/integrationAPI/task-proxy.js --acl public-read --content-encoding 'gzip' --content-type 'application/javascript'
gzip -c9 contestInterface/integrationAPI/task-123.js | aws s3 cp - s3://static-tmp.castor-informatique.fr/assets/integrationAPI/task-123.js --acl public-read --content-encoding 'gzip' --content-type 'application/javascript'
gzip -c9 contestInterface/jquery-combined.min.js | aws s3 cp - s3://static-tmp.castor-informatique.fr/assets/jquery-combined.min.js --acl public-read --content-encoding 'gzip' --content-type 'application/javascript'
gzip -c9 contestInterface/jquery.xdomainrequest.min.js | aws s3 cp - s3://static-tmp.castor-informatique.fr/assets/jquery.xdomainrequest.min.js --acl public-read --content-encoding 'gzip' --content-type 'application/javascript'
echo "eb deploy..."
eb deploy contestEnv -m "$COMMIT_MSG"
eb deploy adminEnv -m "$COMMIT_MSG"
```


## Enjoy

Once all this is done, everything should work! An optional final step is to
restrict connections to your RDS DB.


## Troubleshooting

##### I experience 301 Permanent redirect error on my S3 connections

S3 has a cumbersome feature: when you create a bucket in a region, it is
supposed to be non region-specific, and you cannot change its region, but
if you try to access it from a php client from another region, it will give
you 301 errors. 

The solution is thus to call a bucket from a client in the same
region.


##### I experience timeouts in all my rds queries

This is possibly due to security groups: if you have enabled rds from an
environment, it won't be available to other environments. To enable it, change
the inbound rules of the security group of your rds instance, and add a rule
to enable all TCP traffic from the group of your environment. You can do so
by selecting "custom IP" and enter the id of the security group corresponding
to your environment.


##### RDS is very slow

Use these queries to see where the slowness are:

    show full processlist;
    show engine innodb status;
    show open tables;
    select * from information_schema.innodb_trx;
    select * from information_schema.innodb_locks;
    select * from information_schema.innodb_lock_waits;

but it might be due to the triggers in `group` and `team` tables that were not removed
before going into production. In this case the php log will indicate deadlocks.